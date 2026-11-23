<?php
/**
 * Diagnóstico del correo (pedido del 15-11: "los correos no llegan"). Solo rol admin.
 * Muestra la configuración SMTP sin la contraseña (solo dice si está cargada), envía un correo de
 * prueba y muestra el error exacto del servidor de correo, y reintenta los avisos de stock por correo
 * que quedaron pendientes de repuestos que ya tienen stock. Con CSRF.
 */
require_once '../backend/auth.php';
requerirRolAdmin('../');
require_once '../backend/admin_ui.php';
require_once '../backend/email_helpers.php';

/** "ventas@gmgrepuestos.cl" -> "ve***@gmgrepuestos.cl" */
function gmgCorreoOculto($correo) {
    $partes = explode('@', (string)$correo, 2);
    return count($partes) === 2 ? mb_substr($partes[0], 0, 2) . '***@' . $partes[1] : ($correo === '' ? '' : '***');
}

$mensaje = '';
$tipoMensaje = '';
$detalle = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = isset($_POST['accion']) && is_string($_POST['accion']) ? $_POST['accion'] : '';
    if (!gmgCsrfValido()) {
        $mensaje = 'No se pudo comprobar la seguridad del formulario. Recarga la página e inténtalo de nuevo.';
        $tipoMensaje = 'error';
    } elseif ($accion === 'probar') {
        $destino = isset($_POST['destino']) && is_string($_POST['destino']) ? trim($_POST['destino']) : '';
        if (!filter_var($destino, FILTER_VALIDATE_EMAIL)) {
            $mensaje = 'Escribe un correo válido para la prueba.';
            $tipoMensaje = 'error';
        } else {
            $inicio = microtime(true);
            $r = emailEnviar($destino, '', 'Prueba de correo - GMG Repuestos',
                emailPlantillaBase('<h2 style="margin-top:0;">Prueba de correo</h2><p>Si lees esto, el sitio puede enviar correos (pedidos, cambios de estado y avisos de stock).</p><p style="color:#888;">Enviado el ' . date('d-m-Y H:i') . '.</p>'));
            $segundos = round(microtime(true) - $inicio, 1);
            if (!empty($r['success'])) {
                $mensaje = "Correo enviado a $destino en $segundos s. Revisa la bandeja de entrada y la carpeta de spam.";
                $tipoMensaje = 'exito';
            } else {
                $mensaje = 'El servidor de correo rechazó el envío. Mira el detalle y la guía de abajo.';
                $tipoMensaje = 'error';
                $detalle = (string)($r['error'] ?? 'sin detalle');
            }
        }
    } elseif ($accion === 'reintentar') {
        $enviados = 0;
        $r = $conexion->query("SELECT DISTINCT a.id_repuesto FROM avisos_stock a JOIN repuestos r ON r.id_repuesto = a.id_repuesto
                                WHERE a.notificado = 0 AND a.contacto LIKE '%@%' AND r.stock > 0 AND r.activo = 1");
        foreach ($r ? $r->fetch_all() : [] as $fila) {
            $enviados += gmgNotificarReposicion($conexion, (int)$fila[0]);
        }
        $mensaje = $enviados > 0 ? "Se enviaron $enviados avisos de stock." : 'No se envió ningún aviso.';
        $tipoMensaje = gmgErrorCorreo() === '' ? 'exito' : 'error';
        $detalle = gmgErrorCorreo();
    }
}

$pendientes = 0;
$r = $conexion->query("SELECT COUNT(*) FROM avisos_stock a JOIN repuestos r ON r.id_repuesto = a.id_repuesto
                        WHERE a.notificado = 0 AND a.contacto LIKE '%@%' AND r.stock > 0 AND r.activo = 1");
if ($r) {
    $pendientes = (int)$r->fetch_row()[0];
}
$remitente = filter_var(SMTP_USER, FILTER_VALIDATE_EMAIL) ? SMTP_USER : SMTP_FROM_EMAIL;
$config = [
    'Envío de correos' => EMAIL_ENABLED ? 'Activado' : 'DESACTIVADO (EMAIL_ENABLED en backend/email_config.php)',
    'Servidor' => SMTP_HOST . ':' . SMTP_PORT . ' (' . strtoupper(SMTP_SECURE) . ')',
    'Casilla (SMTP_USER)' => SMTP_USER !== '' ? gmgCorreoOculto(SMTP_USER) : 'FALTA: completa SMTP_USER en el .env',
    'Contraseña (SMTP_PASS)' => SMTP_PASS !== '' ? 'Cargada' : 'FALTA: completa SMTP_PASS en el .env',
    'Los correos salen desde' => gmgCorreoOculto($remitente),
    'Avisos internos (pedidos) llegan a' => gmgCorreoOculto(ADMIN_NOTIFICATION_EMAIL),
];

gmgAdminCabecera('Correo', 'correo');
?>
    <main class="admin-main">
        <div class="container">
            <div class="admin-heading"><h1>Correo</h1></div>
            <?php if ($mensaje): ?>
                <div class="flash-msg flash-<?php echo gmgAdminH($tipoMensaje); ?>"><?php echo gmgAdminH($mensaje); ?></div>
            <?php endif; ?>
            <?php if ($detalle !== ''): ?>
                <div class="admin-panel"><p style="padding:14px 18px; margin:0;"><strong>Respuesta del servidor de correo:</strong><br><code><?php echo gmgAdminH($detalle); ?></code></p></div>
            <?php endif; ?>

            <h2 class="admin-subtitulo">Configuración actual</h2>
            <div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table"><tbody>
                <?php foreach ($config as $nombre => $valor): ?>
                    <tr><th style="width:280px;"><?php echo gmgAdminH($nombre); ?></th><td><?php echo gmgAdminH($valor); ?></td></tr>
                <?php endforeach; ?>
            </tbody></table></div></div>

            <h2 class="admin-subtitulo">Enviar un correo de prueba</h2>
            <form class="admin-form" method="POST" action="correo_prueba.php">
                <?php echo gmgCsrfCampo(); ?>
                <input type="hidden" name="accion" value="probar">
                <div class="form-grid">
                    <div><label for="destino">Enviar a</label><input type="email" id="destino" name="destino" required placeholder="tu@correo.cl"></div>
                    <div class="form-actions"><button type="submit" class="btn-admin">Enviar prueba</button></div>
                </div>
            </form>

            <h2 class="admin-subtitulo">Avisos de stock por correo pendientes</h2>
            <p class="form-ayuda"><?php echo $pendientes; ?> aviso<?php echo $pendientes === 1 ? '' : 's'; ?> por correo de repuestos que ya tienen stock y no se pudieron enviar.</p>
            <?php if ($pendientes > 0): ?>
                <form method="POST" action="correo_prueba.php">
                    <?php echo gmgCsrfCampo(); ?>
                    <input type="hidden" name="accion" value="reintentar">
                    <button type="submit" class="btn-admin">Reintentar avisos pendientes</button>
                </form>
            <?php endif; ?>

            <h2 class="admin-subtitulo">Si la prueba falla</h2>
            <ul class="form-ayuda" style="line-height:1.7;">
                <li><strong>"535" o "Authentication failed":</strong> usuario o contraseña de la casilla incorrectos. En hPanel > Correos, cambia la contraseña de la casilla y ponla en <code>SMTP_PASS</code> del <code>.env</code>; <code>SMTP_USER</code> debe ser la casilla completa (ej. ventas@gmgrepuestos.cl).</li>
                <li><strong>"No se pudo conectar":</strong> revisa <code>SMTP_HOST=smtp.hostinger.com</code>. Si sigue, prueba con <code>SMTP_PORT=587</code> y <code>SMTP_SECURE=tls</code> en el <code>.env</code>.</li>
                <li><strong>"553" o "Sender address rejected":</strong> el remitente no es la casilla de <code>SMTP_USER</code>. Desde hoy el sitio usa esa misma casilla; si persiste, revisa que la casilla exista en hPanel.</li>
                <li><strong>Dice "enviado" pero no llega:</strong> revisa spam y activa SPF, DKIM y DMARC en hPanel > Correos (guía de Hostinger, parte 9.3).</li>
            </ul>
        </div>
    </main>
<?php gmgAdminPie(); ?>
