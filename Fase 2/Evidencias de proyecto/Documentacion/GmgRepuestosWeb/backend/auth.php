<?php
/**
 * Autenticación de administradores - GMG Repuestos
 * Maneja la sesión de inicio de sesión para el panel /admin.
 * Este login es SOLO para el equipo (gestión de productos y pedidos);
 * los clientes de la tienda nunca necesitan iniciar sesión para comprar.
 */

require_once __DIR__ . '/config.php';
gmgIniciarSesion(); // cookie HttpOnly, Secure y SameSite (F3-1)

// Sin actividad por 30 minutos, la sesión del panel se cierra (F3-1, RNF-003).
const GMG_ADMIN_INACTIVIDAD = 30 * 60;

/**
 * ¿Hay un administrador con sesión iniciada?
 */
function adminLogueado() {
    return isset($_SESSION['admin_id']);
}

/**
 * Corta la ejecución y redirige a login.php si no hay sesión activa.
 * Debe llamarse al inicio de cada página dentro de /admin.
 * $prefijo es la ruta relativa hacia la raíz del sitio (ej: '../' dentro de /admin).
 */
function requerirAdmin($prefijo = '') {
    if (adminLogueado() && time() - (int)($_SESSION['admin_ultimo'] ?? 0) > GMG_ADMIN_INACTIVIDAD) {
        gmgCerrarSesion();
        header('Location: ' . $prefijo . 'login.php?expirada=1');
        exit;
    }
    if (!adminLogueado()) {
        header('Location: ' . $prefijo . 'login.php');
        exit;
    }
    // Cuenta desactivada o borrada: sale de inmediato. El rol se lee de la base en cada página (F4-10).
    global $conexion;
    $idAdmin = (int)$_SESSION['admin_id'];
    $stmt = $conexion->prepare("SELECT * FROM administradores WHERE id_admin = ?");
    if ($stmt) {
        $stmt->bind_param('i', $idAdmin);
        $stmt->execute();
        $cuenta = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$cuenta || (int)$cuenta['activo'] !== 1) {
            gmgCerrarSesion();
            header('Location: ' . $prefijo . 'login.php');
            exit;
        }
        $_SESSION['admin_rol'] = $cuenta['rol'] ?? 'admin'; // sin la columna (antes de BD_v2/09) todos son admin
    }
    $_SESSION['admin_ultimo'] = time();
}

/** ¿El usuario del panel tiene rol "admin" (todo el panel)? El rol "vendedor" ve productos, pedidos y avisos (F4-10). */
function gmgEsAdminCompleto() {
    return ($_SESSION['admin_rol'] ?? 'admin') === 'admin';
}

/** Para las pantallas solo de administradores: un vendedor ve un aviso y no la pantalla. */
function requerirRolAdmin($prefijo = '../') {
    requerirAdmin($prefijo);
    if (!gmgEsAdminCompleto()) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="es-CL"><head><meta charset="UTF-8"><title>Sin permiso</title></head><body style="font-family:sans-serif;padding:40px">'
           . '<h1>Esta sección es solo para administradores</h1><p>Tu cuenta es de vendedor. Pídele acceso a un administrador.</p>'
           . '<p><a href="index.php">Volver al panel</a></p></body></html>';
        exit;
    }
}

/** Cierra la sesión y borra su cookie del navegador. */
function gmgCerrarSesion() {
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'domain' => $p['domain'],
                                   'secure' => $p['secure'], 'httponly' => $p['httponly'], 'samesite' => $p['samesite'] ?? 'Lax']);
    session_destroy();
}

/**
 * Nombre del administrador con sesión iniciada (para mostrar en el panel).
 */
function nombreAdminActual() {
    return isset($_SESSION['admin_nombre']) ? $_SESSION['admin_nombre'] : 'Administrador';
}
