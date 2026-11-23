<?php
/**
 * Avisos de stock (historia E6-H10).
 *
 * Lista a los clientes que pidieron que les avisen cuando vuelva el stock de
 * un repuesto (desde el chat o desde la tienda) y permite marcar cada aviso
 * como notificado, para no avisar dos veces a la misma persona.
 */
require_once '../backend/auth.php';
requerirAdmin('../');
require_once '../backend/config.php';
require_once '../backend/admin_ui.php';

$mensajeFlash = '';
$tipoMensajeFlash = '';

// --- ¿Existe la tabla de avisos? (por si no se ha corrido el script de estructura) ---
$tablaLista = true;
$chk = $conexion->query("SHOW TABLES LIKE 'avisos_stock'");
if (!$chk || $chk->num_rows === 0) {
    $tablaLista = false;
}

// --- Marcar un aviso de stock como notificado ---
// Solo se aceptan los formularios del propio panel (código de seguridad CSRF, INC-04).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !gmgCsrfValido()) {
    $mensajeFlash = 'No se pudo comprobar la seguridad del formulario. Recarga la página e inténtalo de nuevo.';
    $tipoMensajeFlash = 'error';
} elseif ($tablaLista && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'marcar_notificado') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conexion->prepare("UPDATE avisos_stock SET notificado = 1 WHERE id_aviso = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $mensajeFlash = 'Aviso #' . $id . ' marcado como notificado.';
            $tipoMensajeFlash = 'exito';
        } else {
            $mensajeFlash = 'No se pudo actualizar el aviso.';
            $tipoMensajeFlash = 'error';
        }
        $stmt->close();
    }
}

$avisosPendientes = 0;
$avisos = [];

if ($tablaLista) {
    $r = $conexion->query("SELECT COUNT(*) AS c FROM avisos_stock WHERE notificado = 0");
    if ($r) { $avisosPendientes = (int)$r->fetch_assoc()['c']; }

    // --- Últimos 50 avisos (primero los pendientes) ---
    $r = $conexion->query("
        SELECT a.id_aviso AS id, a.contacto, a.creado_en, a.notificado, p.nombre AS producto_nombre, p.stock
        FROM avisos_stock a
        LEFT JOIN repuestos p ON p.id_repuesto = a.id_repuesto
        ORDER BY a.notificado ASC, a.creado_en DESC
        LIMIT 50
    ");
    if ($r) {
        while ($fila = $r->fetch_assoc()) {
            $avisos[] = $fila;
        }
    }
}

// Lo que buscaron en la tienda y no encontraron (F3-10, E2-H10): los 20 términos más pedidos de los últimos 90 días.
$busquedasVacias = [];
$r = $conexion->query("SELECT termino, modelo, categoria, veces, ultima_vez FROM busquedas_sin_resultado
                        WHERE ultima_vez >= NOW() - INTERVAL 90 DAY ORDER BY veces DESC, ultima_vez DESC LIMIT 20");
if ($r) {
    $busquedasVacias = $r->fetch_all(MYSQLI_ASSOC);
}

gmgAdminCabecera('Avisos de stock', 'avisos');
?>

    <main class="admin-main">
        <div class="container">
            <div class="admin-heading">
                <h1>Avisos de stock</h1>
            </div>

            <?php if ($mensajeFlash): ?>
                <div class="flash-msg flash-<?php echo gmgAdminH($tipoMensajeFlash); ?>"><?php echo gmgAdminH($mensajeFlash); ?></div>
            <?php endif; ?>

            <?php if (!$tablaLista): ?>
                <div class="flash-msg flash-error">
                    Todavía no existe la tabla de avisos de stock.
                    Corre el archivo <strong>BD_v2/01_estructura.sql</strong> en phpMyAdmin
                    para activar esta pantalla.
                </div>
            <?php else: ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Avisos de stock pendientes</div>
                    <div class="stat-value"><?php echo $avisosPendientes; ?></div>
                </div>
            </div>

            <div class="admin-panel">
                <div class="admin-table-wrap">
                    <?php if (count($avisos) === 0): ?>
                        <p class="admin-empty">No hay clientes esperando aviso de stock.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Stock actual</th>
                                    <th>Contacto</th>
                                    <th>Solicitado</th>
                                    <th>Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($avisos as $a): ?>
                                <tr>
                                    <td>#<?php echo (int)$a['id']; ?></td>
                                    <td><?php echo gmgAdminH($a['producto_nombre'] ?? '(producto eliminado)'); ?></td>
                                    <td><?php echo isset($a['stock']) ? (int)$a['stock'] : '—'; ?></td>
                                    <td><?php echo gmgAdminH($a['contacto']); ?></td>
                                    <td><?php echo gmgAdminH(date('d-m-Y H:i', strtotime($a['creado_en']))); ?></td>
                                    <td>
                                        <?php if ($a['notificado']): ?>
                                            <span class="badge badge-aprobado">Notificado</span>
                                        <?php else: ?>
                                            <span class="badge badge-pendiente">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$a['notificado']): ?>
                                        <form method="POST" action="avisos.php" style="display:inline;">
                                            <?php echo gmgCsrfCampo(); ?>
                                            <input type="hidden" name="accion" value="marcar_notificado">
                                            <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
                                            <button type="submit" class="btn-admin btn-secundario">Marcar notificado</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <?php endif; ?>

            <h2 class="admin-subtitulo">Lo que buscaron y no encontraron</h2>
            <p class="form-ayuda">Búsquedas de la tienda sin resultados en los últimos 90 días (las 20 más repetidas). Sirve para decidir qué repuestos agregar al catálogo.</p>
            <div class="admin-panel">
                <div class="admin-table-wrap">
                    <?php if (!$busquedasVacias): ?>
                        <p class="admin-empty">Todavía no hay búsquedas sin resultados.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead><tr><th>Buscaron</th><th>Modelo</th><th>Categoría</th><th>Veces</th><th>Última vez</th></tr></thead>
                            <tbody>
                                <?php foreach ($busquedasVacias as $b): ?>
                                <tr>
                                    <td><?php echo gmgAdminH($b['termino']); ?></td>
                                    <td><?php echo gmgAdminH($b['modelo'] !== '' ? $b['modelo'] : '—'); ?></td>
                                    <td><?php echo gmgAdminH($b['categoria'] !== '' ? $b['categoria'] : '—'); ?></td>
                                    <td><?php echo (int)$b['veces']; ?></td>
                                    <td><?php echo gmgAdminH(date('d-m-Y H:i', strtotime($b['ultima_vez']))); ?></td>
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
