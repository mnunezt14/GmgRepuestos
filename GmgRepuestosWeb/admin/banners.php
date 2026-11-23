<?php
/**
 * Banners de la tienda (F4-10, E6-H11). Solo rol admin.
 * Avisos cortos que se muestran en una franja arriba de todas las páginas públicas (por ejemplo
 * "Llegaron pastillas para Swift"). Opcionalmente con enlace a una página del propio sitio y con
 * fecha de término. Con CSRF y auditoría. Tabla banners (script BD_v2/09).
 */
require_once '../backend/auth.php';
requerirRolAdmin('../');
require_once '../backend/admin_ui.php';
require_once '../backend/auditoria.php';

$mensaje = '';
$tipoMensaje = '';

/** Enlace del banner: vacío o una dirección del propio sitio (nunca otro dominio ni javascript:). */
function gmgEnlaceBannerValido($enlace) {
    return $enlace === '' || (preg_match('#^[a-zA-Z0-9_\-./?=&%\#]+$#', $enlace) && strpos($enlace, '//') === false && strpos($enlace, ':') === false);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = isset($_POST['accion']) && is_string($_POST['accion']) ? $_POST['accion'] : '';
    if (!gmgCsrfValido()) {
        $mensaje = 'No se pudo comprobar la seguridad del formulario. Recarga la página e inténtalo de nuevo.';
        $tipoMensaje = 'error';
    } elseif ($accion === 'crear') {
        $texto = isset($_POST['mensaje']) && is_string($_POST['mensaje']) ? trim($_POST['mensaje']) : '';
        $enlace = isset($_POST['enlace']) && is_string($_POST['enlace']) ? trim($_POST['enlace']) : '';
        $hasta = isset($_POST['vigente_hasta']) && is_string($_POST['vigente_hasta']) ? trim($_POST['vigente_hasta']) : '';
        if ($texto === '' || mb_strlen($texto) > 200) {
            $mensaje = 'Escribe el mensaje del banner (hasta 200 caracteres).';
            $tipoMensaje = 'error';
        } elseif (!gmgEnlaceBannerValido($enlace)) {
            $mensaje = 'El enlace debe ser una página de este sitio, por ejemplo index.php?modelo=swift o producto.php?id=12.';
            $tipoMensaje = 'error';
        } elseif ($hasta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $mensaje = 'La fecha de término no es válida.';
            $tipoMensaje = 'error';
        } else {
            $enlaceGuardar = $enlace !== '' ? $enlace : null;
            $hastaGuardar = $hasta !== '' ? $hasta : null;
            $stmt = $conexion->prepare("INSERT INTO banners (mensaje, enlace, vigente_hasta) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $texto, $enlaceGuardar, $hastaGuardar);
            $stmt->execute();
            gmgAuditar($conexion, 'banner_creado', 'banner', (int)$conexion->insert_id, 'Banner creado: ' . mb_substr($texto, 0, 120));
            $stmt->close();
            $mensaje = 'Banner publicado en la tienda.';
            $tipoMensaje = 'exito';
        }
    } elseif ($accion === 'activar' || $accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($accion === 'eliminar') {
            $stmt = $conexion->prepare("DELETE FROM banners WHERE id_banner = ?");
            $stmt->bind_param('i', $id);
        } else {
            $activo = (int)($_POST['activo'] ?? 0) === 1 ? 1 : 0;
            $stmt = $conexion->prepare("UPDATE banners SET activo = ? WHERE id_banner = ?");
            $stmt->bind_param('ii', $activo, $id);
        }
        $stmt->execute();
        $cambio = $stmt->affected_rows > 0;
        $stmt->close();
        if ($cambio) {
            gmgAuditar($conexion, 'banner_' . ($accion === 'eliminar' ? 'eliminado' : 'estado'), 'banner', $id, 'Banner #' . $id . ' ' . ($accion === 'eliminar' ? 'eliminado' : (((int)($_POST['activo'] ?? 0) === 1) ? 'activado' : 'pausado')));
        }
        $mensaje = $cambio ? 'Banner actualizado.' : 'No hubo cambios.';
        $tipoMensaje = 'exito';
    }
}

$banners = [];
$r = $conexion->query("SELECT id_banner, mensaje, enlace, activo, vigente_hasta, creado_en FROM banners ORDER BY activo DESC, creado_en DESC");
if ($r) {
    $banners = $r->fetch_all(MYSQLI_ASSOC);
} else {
    $mensaje = 'Falta ejecutar el script BD_v2/09_cuentas_banners.sql en la base de datos.';
    $tipoMensaje = 'error';
}

gmgAdminCabecera('Banners', 'banners');
?>
    <main class="admin-main">
        <div class="container">
            <div class="admin-heading"><h1>Banners de la tienda</h1></div>
            <?php if ($mensaje): ?>
                <div class="flash-msg flash-<?php echo gmgAdminH($tipoMensaje); ?>"><?php echo gmgAdminH($mensaje); ?></div>
            <?php endif; ?>
            <p class="form-ayuda">Se muestran en una franja arriba de todas las páginas de la tienda (los activos y vigentes, el más nuevo primero, máximo 2).</p>

            <form class="admin-form" method="POST" action="banners.php">
                <?php echo gmgCsrfCampo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-grid">
                    <div class="form-full"><label for="b_mensaje">Mensaje (hasta 200 caracteres)</label><input id="b_mensaje" name="mensaje" maxlength="200" required placeholder="Ej: Llegaron pastillas de freno para Swift 2017-2024"></div>
                    <div><label for="b_enlace">Enlace (opcional, una página de este sitio)</label><input id="b_enlace" name="enlace" maxlength="255" placeholder="index.php?modelo=swift"></div>
                    <div><label for="b_hasta">Mostrar hasta (opcional)</label><input id="b_hasta" type="date" name="vigente_hasta"></div>
                    <div class="form-actions"><button type="submit" class="btn-admin">Publicar banner</button></div>
                </div>
            </form>

            <h2 class="admin-subtitulo">Banners creados</h2>
            <div class="admin-panel">
                <div class="admin-table-wrap">
                    <?php if (!$banners): ?>
                        <p class="admin-empty">Todavía no hay banners.</p>
                    <?php else: ?>
                    <table class="admin-table">
                        <thead><tr><th>Mensaje</th><th>Enlace</th><th>Hasta</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($banners as $b): $vencido = $b['vigente_hasta'] && $b['vigente_hasta'] < date('Y-m-d'); ?>
                            <tr>
                                <td><?php echo gmgAdminH($b['mensaje']); ?></td>
                                <td><?php echo gmgAdminH($b['enlace'] ?: '—'); ?></td>
                                <td><?php echo $b['vigente_hasta'] ? gmgAdminH(date('d-m-Y', strtotime($b['vigente_hasta']))) : 'Sin fecha'; ?></td>
                                <td><?php echo $vencido ? '<span class="badge badge-error">Vencido</span>' : ((int)$b['activo'] ? '<span class="badge badge-aprobado">Visible</span>' : '<span class="badge badge-pendiente">Pausado</span>'); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <form method="POST" action="banners.php">
                                            <?php echo gmgCsrfCampo(); ?>
                                            <input type="hidden" name="accion" value="activar">
                                            <input type="hidden" name="id" value="<?php echo (int)$b['id_banner']; ?>">
                                            <input type="hidden" name="activo" value="<?php echo (int)$b['activo'] ? 0 : 1; ?>">
                                            <button type="submit" class="link-editar"><?php echo (int)$b['activo'] ? 'Pausar' : 'Activar'; ?></button>
                                        </form>
                                        <form method="POST" action="banners.php" onsubmit="return confirm('¿Eliminar este banner?');">
                                            <?php echo gmgCsrfCampo(); ?>
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?php echo (int)$b['id_banner']; ?>">
                                            <button type="submit" class="link-eliminar">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
<?php gmgAdminPie(); ?>
