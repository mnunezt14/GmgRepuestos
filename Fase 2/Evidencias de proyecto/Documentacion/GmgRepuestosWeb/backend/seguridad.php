<?php
/**
 * Protecciones de conexión - GMG Repuestos (historia E10-H1)
 *
 * El archivo .htaccess de la raíz ya obliga a usar HTTPS y agrega las
 * cabeceras de seguridad. Este archivo hace lo mismo desde PHP como segundo
 * seguro: si por cualquier razón el hosting no lee el .htaccess, las páginas
 * PHP igual salen protegidas.
 *
 * Qué hace:
 *  1. Si alguien entra por http:// redirige a https:// con código 301.
 *     (No se aplica en el computador local de desarrollo.)
 *  2. Envía las cabeceras:
 *       Strict-Transport-Security  -> el navegador recuerda usar siempre HTTPS (HSTS)
 *       X-Content-Type-Options     -> nosniff
 *       X-Frame-Options            -> SAMEORIGIN
 *       Referrer-Policy            -> strict-origin-when-cross-origin
 */

/** ¿La visita actual llegó por una conexión cifrada (HTTPS)? */
function gmgEsHttps() {
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    // Hostinger / proxies: avisan por esta cabecera que el visitante usó HTTPS.
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    return false;
}

/**
 * Dirección pública base del sitio, sin barra final. Usa SITE_URL del .env
 * si existe; si no, la deduce de la visita (https://dominio/subcarpeta).
 */
function gmgUrlBase() {
    $configurada = trim((string)gmgEnv('SITE_URL', ''));
    if ($configurada !== '') {
        return rtrim($configurada, '/');
    }
    $esquema = gmgEsHttps() ? 'https' : 'http';
    $host = gmgHostValido() ? $_SERVER['HTTP_HOST'] : 'localhost';
    $carpeta = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $esquema . '://' . $host . $carpeta;
}

/** ¿La dirección con que visitan el sitio tiene un formato normal (letras, números, puntos, guiones y puerto)? */
function gmgHostValido() {
    return !empty($_SERVER['HTTP_HOST']) && preg_match('/^[A-Za-z0-9.\-]+(:[0-9]{1,5})?$/', $_SERVER['HTTP_HOST']) === 1;
}

/** Redirige a HTTPS (301) y envía las cabeceras de seguridad. */
function gmgAplicarSeguridadConexion() {
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }

    if (!gmgEsHttps() && !gmgEsLocal() && gmgHostValido()) {
        // Solo se conserva el camino de la dirección (sin saltos de línea ni caracteres raros).
        $camino = preg_replace('/[\x00-\x1F\x7F]/', '', $_SERVER['REQUEST_URI'] ?? '/');
        $destino = 'https://' . $_SERVER['HTTP_HOST'] . $camino;
        header('Location: ' . $destino, true, 301);
        exit;
    }

    if (gmgEsHttps()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // CSP básica (F3-5, RNF-031, RNF-038): solo recursos del propio sitio, más Google Fonts,
    // el mapa de Google (Contacto) e imágenes por HTTPS. 'unsafe-inline' se mantiene por los
    // onerror/onsubmit y el script del dashboard; sacarlos permitiría una política más estricta.
    // Mercado Pago y WhatsApp son navegaciones (no las limita esta política).
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com; "
         . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; "
         . "img-src 'self' data: blob: https:; connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com; frame-src https://www.google.com; "
         . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
    // El micrófono se usa para dictar en el chat; cámara, ubicación y pagos del navegador, no.
    header('Permissions-Policy: camera=(), geolocation=(), payment=(), microphone=(self)');
}

/**
 * Abre la sesión con la cookie protegida (F3-1, INC-08, RNF-002): no la lee JavaScript
 * (HttpOnly), solo viaja por HTTPS cuando el sitio está en HTTPS (Secure), no se envía
 * desde otros sitios en formularios (SameSite=Lax) y no acepta ids de sesión inventados.
 */
function gmgIniciarSesion() {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => gmgEsHttps(), 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/** Clave del contador: acción + hash del identificador (la IP si no se indica). Nunca se guarda la IP. */
function gmgClaveLimite(string $accion, ?string $identificador = null): string {
    return substr($accion, 0, 30) . ':' . hash('sha256', $identificador ?? ($_SERVER['REMOTE_ADDR'] ?? 'sin-ip'));
}

/** ¿El contador ya llegó al máximo dentro de la ventana? Solo lee, no suma (bloqueo del login, F3-1). */
function gmgLimiteAlcanzado(mysqli $conexion, string $accion, int $maximo, int $segundos, ?string $identificador = null): bool {
    $clave = gmgClaveLimite($accion, $identificador);
    $desde = time() - $segundos;
    $stmt = $conexion->prepare("SELECT contador FROM limites_solicitudes WHERE clave = ? AND ventana_inicio > ?");
    if (!$stmt) {
        error_log('[GMG Límites] No se pudo leer el contador: ' . $conexion->error);
        return false;
    }
    $stmt->bind_param('si', $clave, $desde);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_row();
    $stmt->close();
    return (int)($fila[0] ?? 0) >= $maximo;
}

/** Borra un contador (por ejemplo, los intentos fallidos después de entrar bien). */
function gmgLimiteReiniciar(mysqli $conexion, string $accion, ?string $identificador = null): void {
    $clave = gmgClaveLimite($accion, $identificador);
    $stmt = $conexion->prepare("DELETE FROM limites_solicitudes WHERE clave = ?");
    if ($stmt) {
        $stmt->bind_param('s', $clave);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Límite de solicitudes por IP con ventana fija (RNF-035, E10-H3).
 * Requiere la tabla limites_solicitudes (BD_v2/05_tablas_panel.sql).
 * Devuelve true si la IP ya superó el máximo en la ventana actual.
 */
function gmgLimiteSuperado(mysqli $conexion, string $accion, int $maximo, int $segundos, ?string $identificador = null): bool {
    $clave = gmgClaveLimite($accion, $identificador);
    $ahora = time();
    $limiteVentana = $ahora - $segundos;
    $stmt = $conexion->prepare("INSERT INTO limites_solicitudes (clave, ventana_inicio, contador) VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE
            contador = IF(ventana_inicio <= ?, 1, contador + 1),
            ventana_inicio = IF(ventana_inicio <= ?, ?, ventana_inicio)");
    if (!$stmt) {
        // Sin la tabla no se puede contar: se deja pasar y se avisa en el log.
        error_log('[GMG Límites] No se pudo registrar la solicitud: ' . $conexion->error);
        return false;
    }
    $stmt->bind_param('siiii', $clave, $ahora, $limiteVentana, $limiteVentana, $ahora);
    $stmt->execute();
    $stmt->close();

    $stmt = $conexion->prepare("SELECT contador FROM limites_solicitudes WHERE clave = ?");
    $stmt->bind_param('s', $clave);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_row();
    $contador = (int)($fila[0] ?? 0);
    $stmt->close();
    return $contador > $maximo;
}

gmgAplicarSeguridadConexion();
