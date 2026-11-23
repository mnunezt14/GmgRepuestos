<?php
/**
 * Restablecer la contraseña del panel (F3-2, E1-H5, RF-005).
 *   Paso 1 (sin token): el administrador escribe su usuario o correo y recibe un enlace por correo.
 *     La respuesta es siempre la misma, exista o no la cuenta (no revela quién existe).
 *   Paso 2 (?token=...): con un enlace válido (un solo uso, 30 minutos) elige la clave nueva.
 * Se guarda solo el hash SHA-256 del enlace (tabla restablecer_clave, script BD_v2/09).
 */
require_once 'backend/auth.php';
require_once 'backend/admin_ui.php';
require_once 'backend/email_helpers.php';
require_once 'backend/auditoria.php';

const GMG_RESTABLECER_MINUTOS = 30;
const GMG_CLAVE_MINIMO = 10;

$token = isset($_GET['token']) && is_string($_GET['token']) && preg_match('/^[0-9a-f]{64}$/', $_GET['token']) ? $_GET['token'] : '';
$mensaje = '';
$tipo = '';
$solicitud = null;

/** Solicitud vigente para el token (no usada y no vencida), o null. */
function gmgSolicitudRestablecer($conexion, $token) {
    $hash = hash('sha256', $token);
    $stmt = $conexion->prepare("SELECT r.id_restablecer, r.id_admin, a.usuario FROM restablecer_clave r
                                  JOIN administradores a ON a.id_admin = r.id_admin
                                 WHERE r.token_hash = ? AND r.usado_en IS NULL AND r.expira_en > NOW() AND a.activo = 1");
    if (!$stmt) {
        error_log('[GMG Restablecer] Falta la tabla restablecer_clave (BD_v2/09): ' . $conexion->error);
        return null;
    }
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $fila ?: null;
}

if ($token !== '') {
    $solicitud = gmgSolicitudRestablecer($conexion, $token);
    if (!$solicitud) {
        $mensaje = 'El enlace no es válido o ya venció (dura 30 minutos y sirve una sola vez). Pide uno nuevo.';
        $tipo = 'error';
        $token = '';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $clave = isset($_POST['clave']) && is_string($_POST['clave']) ? $_POST['clave'] : '';
        $repetida = isset($_POST['clave2']) && is_string($_POST['clave2']) ? $_POST['clave2'] : '';
        if (!gmgCsrfValido()) {
            $mensaje = 'No se pudo comprobar la seguridad del formulario. Recarga la página e inténtalo de nuevo.';
            $tipo = 'error';
        } elseif (mb_strlen($clave) < GMG_CLAVE_MINIMO) {
            $mensaje = 'La contraseña debe tener al menos ' . GMG_CLAVE_MINIMO . ' caracteres.';
            $tipo = 'error';
        } elseif ($clave !== $repetida) {
            $mensaje = 'Las dos contraseñas no coinciden.';
            $tipo = 'error';
        } else {
            $hashClave = password_hash($clave, PASSWORD_DEFAULT);
            $idAdmin = (int)$solicitud['id_admin'];
            $conexion->begin_transaction();
            $stmt = $conexion->prepare("UPDATE administradores SET password_hash = ? WHERE id_admin = ?");
            $stmt->bind_param('si', $hashClave, $idAdmin);
            $ok = $stmt->execute();
            $stmt->close();
            // Este enlace y cualquier otro pendiente de la misma cuenta quedan usados.
            $stmt = $conexion->prepare("UPDATE restablecer_clave SET usado_en = NOW() WHERE id_admin = ? AND usado_en IS NULL");
            $stmt->bind_param('i', $idAdmin);
            $ok = $stmt->execute() && $ok;
            $stmt->close();
            if ($ok) {
                $conexion->commit();
                gmgLimiteReiniciar($conexion, 'login_usuario', 'usuario:' . mb_strtolower($solicitud['usuario']));
                gmgAuditar($conexion, 'clave_restablecida', 'administrador', $idAdmin, 'Contraseña de "' . $solicitud['usuario'] . '" restablecida con el enlace por correo');
                $mensaje = 'Listo, tu contraseña cambió. Ya puedes iniciar sesión.';
                $tipo = 'exito';
                $token = '';
                $solicitud = null;
            } else {
                $conexion->rollback();
                $mensaje = 'No se pudo cambiar la contraseña. Inténtalo de nuevo.';
                $tipo = 'error';
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dato = isset($_POST['usuario']) && is_string($_POST['usuario']) ? mb_substr(trim($_POST['usuario']), 0, 150) : '';
    if (!gmgCsrfValido()) {
        $mensaje = 'No se pudo comprobar la seguridad del formulario. Recarga la página e inténtalo de nuevo.';
        $tipo = 'error';
    } elseif ($dato === '') {
        $mensaje = 'Escribe tu usuario o tu correo.';
        $tipo = 'error';
    } else {
        // Máximo 5 pedidos por IP cada 15 minutos; pasado el límite, la respuesta es la misma pero no se envía nada.
        if (!gmgLimiteSuperado($conexion, 'recuperar', 5, 900)) {
            $stmt = $conexion->prepare("SELECT id_admin, nombre, email FROM administradores WHERE (usuario = ? OR email = ?) AND activo = 1 AND email IS NOT NULL AND email <> '' LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('ss', $dato, $dato);
                $stmt->execute();
                $admin = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($admin) {
                    $nuevoToken = bin2hex(random_bytes(32));
                    $hash = hash('sha256', $nuevoToken);
                    $idAdmin = (int)$admin['id_admin'];
                    $stmt = $conexion->prepare("UPDATE restablecer_clave SET usado_en = NOW() WHERE id_admin = ? AND usado_en IS NULL");
                    $stmt->bind_param('i', $idAdmin);
                    $stmt->execute();
                    $stmt->close();
                    $stmt = $conexion->prepare("INSERT INTO restablecer_clave (id_admin, token_hash, expira_en) VALUES (?, ?, NOW() + INTERVAL " . GMG_RESTABLECER_MINUTOS . " MINUTE)");
                    $stmt->bind_param('is', $idAdmin, $hash);
                    $stmt->execute();
                    $stmt->close();
                    $enlace = gmgUrlBase() . '/recuperar.php?token=' . $nuevoToken;
                    $html = emailPlantillaBase('<h2 style="color:#111; margin-top:0;">Cambiar tu contraseña</h2>'
                        . '<p>Hola ' . htmlspecialchars($admin['nombre'], ENT_QUOTES, 'UTF-8') . ', pediste cambiar la contraseña del panel de GMG Repuestos.</p>'
                        . '<p style="margin:24px 0;"><a href="' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block; background:#D32F2F; color:#fff; padding:10px 20px; border-radius:4px; text-decoration:none; font-weight:bold;">Elegir contraseña nueva</a></p>'
                        . '<p style="color:#666; font-size:0.85rem;">El enlace sirve una sola vez y vence en 30 minutos. Si no lo pediste, ignora este correo: tu contraseña no cambia.</p>');
                    $envio = emailEnviar($admin['email'], $admin['nombre'], 'Cambiar tu contraseña del panel', $html);
                    if (empty($envio['success'])) {
                        error_log('[GMG Restablecer] No se pudo enviar el correo: ' . ($envio['error'] ?? 'sin detalle'));
                    }
                }
            } else {
                error_log('[GMG Restablecer] No se pudo buscar la cuenta (¿falta BD_v2/09?): ' . $conexion->error);
            }
        }
        $mensaje = 'Si la cuenta existe y tiene correo registrado, te enviamos un enlace para cambiar la contraseña. Revisa tu bandeja (y la carpeta de spam).';
        $tipo = 'exito';
    }
}
?>
<!DOCTYPE html>
<html lang="es-CL">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - GMG Repuestos</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="css/style.css?v=<?php echo gmgVersionArchivo('css/style.css'); ?>">
    <link rel="stylesheet" href="css/admin.css?v=<?php echo gmgVersionArchivo('css/admin.css'); ?>">
</head>
<body class="admin-body">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-logo">GMG <span>Repuestos</span></div>
            <p class="login-subtitle"><?php echo $token !== '' ? 'Elige tu contraseña nueva' : 'Recuperar el acceso al panel'; ?></p>

            <?php if ($mensaje): ?>
                <div class="<?php echo $tipo === 'error' ? 'login-error' : 'login-ok'; ?>" role="status"><?php echo gmgAdminH($mensaje); ?></div>
            <?php endif; ?>

            <?php if ($token !== ''): ?>
                <form method="POST" action="recuperar.php?token=<?php echo gmgAdminH($token); ?>">
                    <?php echo gmgCsrfCampo(); ?>
                    <div class="login-field">
                        <label for="clave">Contraseña nueva (mínimo <?php echo GMG_CLAVE_MINIMO; ?> caracteres)</label>
                        <input type="password" id="clave" name="clave" minlength="<?php echo GMG_CLAVE_MINIMO; ?>" autocomplete="new-password" required autofocus>
                    </div>
                    <div class="login-field">
                        <label for="clave2">Repítela</label>
                        <input type="password" id="clave2" name="clave2" minlength="<?php echo GMG_CLAVE_MINIMO; ?>" autocomplete="new-password" required>
                    </div>
                    <button type="submit" class="btn-login">Guardar contraseña</button>
                </form>
            <?php elseif ($tipo !== 'exito'): ?>
                <form method="POST" action="recuperar.php">
                    <?php echo gmgCsrfCampo(); ?>
                    <div class="login-field">
                        <label for="usuario">Usuario o correo</label>
                        <input type="text" id="usuario" name="usuario" autocomplete="username" maxlength="150" required autofocus>
                    </div>
                    <button type="submit" class="btn-login">Enviarme un enlace</button>
                </form>
            <?php endif; ?>

            <a href="login.php" class="login-back">← Volver a iniciar sesión</a>
        </div>
    </div>
</body>
</html>
