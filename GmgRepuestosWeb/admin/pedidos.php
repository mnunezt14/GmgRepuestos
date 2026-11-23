<?php
require_once '../backend/auth.php';
requerirAdmin('../');
require_once '../backend/config.php';
require_once '../backend/admin_ui.php';
require_once '../backend/datos.php';
require_once '../backend/email_helpers.php';
require_once '../backend/auditoria.php';
require_once '../backend/pedido_estado.php';

$mensaje = '';
$tipoMensaje = '';

// --- Actualizar estado manualmente ---
// Solo se aceptan los formularios del propio panel (código de seguridad CSRF, INC-04).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !gmgCsrfValido()) {
    $mensaje = 'No se pudo comprobar la seguridad del formulario. Recarga la página e inténtalo de nuevo.';
    $tipoMensaje = 'error';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'actualizar_estado') {
    $id = (int)($_POST['id'] ?? 0);
    $nuevoEstado = $_POST['estado'] ?? '';
    $estadosValidos = ['pendiente', 'aprobado', 'rechazado', 'error'];

    if (in_array($nuevoEstado, $estadosValidos, true) && $id > 0) {
        // Cambia el estado, descuenta el stock al aprobar (una sola vez) y lo audita (F2-3).
        $resultado = gmgPedidoCambiarEstado($conexion, $id, $nuevoEstado);
        $mensaje = $resultado['mensaje'];
        $tipoMensaje = $resultado['ok'] ? 'exito' : 'error';
        if ($resultado['cambio']) {
            // Aviso al cliente: el administrador cambió el estado de su
            // pedido manualmente. Si el correo falla, no debe interrumpir
            // el panel, por eso se ignora silenciosamente cualquier error.
            $correo = enviarCorreoActualizacionPedido($conexion, $id, $nuevoEstado);
            if (!empty($correo['success'])) {
                $mensaje .= ' Se avisó al cliente por correo.';
            }
            if ($nuevoEstado === 'aprobado') {
                enviarCorreoPedidoAprobadoInterno($conexion, $id); // aviso interno para coordinar la entrega (F4-6)
            }
            if (gmgErrorCorreo() !== '') { // antes el error se callaba y "no pasaba nada"
                $mensaje .= ' Ojo: no se pudo enviar el correo (revisa Panel > Correo).';
            }
        }
    }
}

// --- Filtro por estado, pago y búsqueda (número de pedido, cliente, RUT o teléfono) ---
$filtro = $_GET['estado'] ?? '';
$filtroPago = $_GET['pago'] ?? '';
$busqueda = trim($_GET['q'] ?? '');
$estadosValidos = ['pendiente', 'aprobado', 'rechazado', 'error'];
$pagosValidos = ['recibido', 'pendiente', 'rechazado'];

// Filtros con sentencia preparada (F3-4, RNF-030).
$condiciones = [];
$tipos = '';
$params = [];
if (in_array($filtro, $estadosValidos, true)) {
    $condiciones[] = "estado = ?";
    $tipos .= 's';
    $params[] = $filtro;
}
if (in_array($filtroPago, $pagosValidos, true)) {
    $condiciones[] = "pago_estado = ?";
    $tipos .= 's';
    $params[] = $filtroPago;
}
if ($busqueda !== '') {
    $busquedaLike = '%' . addcslashes($busqueda, '%_\\') . '%';
    $condicionesBusqueda = [
        "CONCAT(nombre_cliente, ' ', apellido_cliente) LIKE ?",
        "rut_cliente LIKE ?",
        "telefono_cliente LIKE ?",
        "email_cliente LIKE ?",
    ];
    $tipos .= 'ssss';
    array_push($params, $busquedaLike, $busquedaLike, $busquedaLike, $busquedaLike);
    // Base v2: el RUT se guarda sin puntos, así que también se compara sin
    // puntos ni guion (ej: buscar "21.740.488" encuentra "21740488-6").
    $rutBuscado = preg_replace('/[^0-9K]/', '', strtoupper($busqueda));
    if (strlen($rutBuscado) >= 6) {
        $condicionesBusqueda[] = "REPLACE(rut_cliente, '-', '') LIKE ?";
        $tipos .= 's';
        $params[] = '%' . $rutBuscado . '%';
    }
    // Si la búsqueda trae dígitos (ej. "#5", "5", "pedido 5"), también se
    // compara directo contra el número de pedido.
    $soloNumeros = preg_replace('/[^0-9]/', '', $busqueda);
    if ($soloNumeros !== '') {
        $condicionesBusqueda[] = "id = ?";
        $tipos .= 'i';
        $params[] = (int)$soloNumeros;
    }
    $condiciones[] = '(' . implode(' OR ', $condicionesBusqueda) . ')';
}

$sql = "SELECT * FROM " . gmgSqlPedidos() . " pv";
if (!empty($condiciones)) {
    $sql .= " WHERE " . implode(' AND ', $condiciones);
}
$sql .= " ORDER BY fecha DESC";

$pedidos = [];
$stmt = $conexion->prepare($sql);
if ($stmt) {
    if ($tipos !== '') {
        $stmt->bind_param($tipos, ...$params);
    }
    $stmt->execute();
    $pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    error_log('[GMG Pedidos] No se pudo preparar el listado: ' . $conexion->error);
}

// --- Items de cada pedido ---
$itemsPorPedido = [];
if (count($pedidos) > 0) {
    $ids = array_map(fn($p) => (int)$p['id'], $pedidos);
    $idsList = implode(',', $ids);
    $r = $conexion->query("SELECT * FROM " . gmgSqlPedidoItems() . " pi WHERE pi.pedido_id IN ($idsList)");
    if ($r) {
        while ($fila = $r->fetch_assoc()) {
            $itemsPorPedido[$fila['pedido_id']][] = $fila;
        }
    }
}

// --- Método de entrega de cada pedido (columna creada por BD_v2/06_sprint1a4_tablas_nuevas.sql) ---
$entregaPorPedido = [];
if (count($pedidos) > 0) {
    $rEnt = $conexion->query("SELECT id_pedido, metodo_entrega FROM pedidos WHERE id_pedido IN (" . implode(',', array_map(fn($p) => (int)$p['id'], $pedidos)) . ")");
    if ($rEnt) {
        while ($fEnt = $rEnt->fetch_assoc()) {
            $entregaPorPedido[(int)$fEnt['id_pedido']] = $fEnt['metodo_entrega'];
        }
    }
}

function badgeEstado($estado) {
    $clases = [
        'aprobado' => 'badge-aprobado',
        'pendiente' => 'badge-pendiente',
        'rechazado' => 'badge-rechazado',
        'error' => 'badge-error',
    ];
    $clase = $clases[$estado] ?? 'badge-pendiente';
    return '<span class="badge ' . $clase . '">' . htmlspecialchars($estado) . '</span>';
}

// Resultado del pago informado por Mercado Pago (independiente del Estado,
// que es la aprobación manual del administrador — ver mercadopago_return.php).
function badgePago($pagoEstado) {
    $clases = [
        'recibido' => 'badge-aprobado',
        'pendiente' => 'badge-pendiente',
        'rechazado' => 'badge-rechazado',
    ];
    $etiquetas = [
        'recibido' => 'Pago recibido',
        'pendiente' => 'Sin pago',
        'rechazado' => 'Pago rechazado',
    ];
    $pagoEstado = $pagoEstado ?: 'pendiente';
    $clase = $clases[$pagoEstado] ?? 'badge-pendiente';
    $etiqueta = $etiquetas[$pagoEstado] ?? ucfirst($pagoEstado);
    return '<span class="badge ' . $clase . '">' . htmlspecialchars($etiqueta) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos - Panel administrador - GMG Repuestos</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo gmgVersionArchivo('css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo gmgVersionArchivo('css/admin.css'); ?>">
</head>
<body class="admin-body">

    <div class="admin-topbar">
        <div class="container">
            <div class="admin-brand">GMG <span>Admin</span></div>
            <nav class="admin-nav">
<?php require_once '../backend/admin_ui.php'; gmgAdminEnlaces('pedidos'); ?>
                <span class="admin-user">Hola, <?php echo htmlspecialchars(nombreAdminActual()); ?></span>
                <a href="../logout.php" class="admin-logout">Cerrar sesión</a>
            </nav>
        </div>
    </div>

    <main class="admin-main">
        <div class="container">
            <div class="admin-heading">
                <h1>Pedidos</h1>
                <div class="table-actions">
                    <a href="pedidos.php" class="btn-admin btn-secundario<?php echo ($filtro === '' && $filtroPago === '') ? ' active' : ''; ?>">Todos</a>
                    <a href="pedidos.php?estado=pendiente" class="btn-admin btn-secundario">Pendientes</a>
                    <a href="pedidos.php?estado=aprobado" class="btn-admin btn-secundario">Aprobados</a>
                    <a href="pedidos.php?estado=rechazado" class="btn-admin btn-secundario">Rechazados</a>
                </div>
            </div>

            <?php if (in_array($filtroPago, $pagosValidos, true)): ?>
                <p style="margin:-10px 0 14px; font-size:0.85rem; color:#888;">
                    Filtrando por pago: <strong><?php echo htmlspecialchars(ucfirst($filtroPago)); ?></strong>
                    &nbsp;·&nbsp;<a href="pedidos.php<?php echo in_array($filtro, $estadosValidos, true) ? '?estado=' . urlencode($filtro) : ''; ?>" style="color:var(--color-acento);">Quitar filtro</a>
                </p>
            <?php endif; ?>

            <form method="GET" action="pedidos.php" style="margin-bottom:18px; display:flex; gap:10px; align-items:center;">
                <?php if (in_array($filtro, $estadosValidos, true)): ?>
                    <input type="hidden" name="estado" value="<?php echo htmlspecialchars($filtro); ?>">
                <?php endif; ?>
                <?php if (in_array($filtroPago, $pagosValidos, true)): ?>
                    <input type="hidden" name="pago" value="<?php echo htmlspecialchars($filtroPago); ?>">
                <?php endif; ?>
                <input type="text" name="q" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar por N° de pedido, cliente, RUT, teléfono o correo..." style="flex:1; max-width:420px; padding:9px 12px; border:1px solid #ddd; border-radius:4px; font-size:0.9rem;">
                <button type="submit" class="btn-admin btn-secundario">Buscar</button>
                <?php if ($busqueda !== ''): ?>
                    <a href="pedidos.php?<?php echo http_build_query(array_filter(['estado' => $filtro, 'pago' => $filtroPago])); ?>" class="btn-admin btn-secundario">Limpiar</a>
                <?php endif; ?>
            </form>

            <?php if ($mensaje): ?>
                <div class="flash-msg flash-<?php echo $tipoMensaje; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <div class="admin-panel">
                <div class="admin-table-wrap">
                    <?php if (count($pedidos) === 0): ?>
                        <p class="admin-empty">No hay pedidos para mostrar.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Productos</th>
                                    <th>Estado</th>
                                    <th>Pago</th>
                                    <th>Método</th>
                                    <th>Descuento</th>
                                    <th>Total</th>
                                    <th>Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidos as $p): ?>
                                <tr>
                                    <td>#<?php echo (int)$p['id']; ?></td>
                                    <td><?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($p['fecha']))); ?></td>
                                    <td>
                                        <?php if (!empty($p['nombre_cliente'])): ?>
                                            <strong><?php echo htmlspecialchars($p['nombre_cliente'] . ' ' . $p['apellido_cliente']); ?></strong><br>
                                            <span style="color:#888; font-size:0.8rem;">
                                                <?php echo htmlspecialchars($p['rut_cliente'] ?: '—'); ?> · <?php echo htmlspecialchars($p['telefono_cliente'] ?: '—'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#bbb;">Sin datos</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            $itemsPedido = $itemsPorPedido[$p['id']] ?? [];
                                            $totalUnidades = array_sum(array_column($itemsPedido, 'cantidad'));
                                            $numProductos = count($itemsPedido);
                                        ?>
                                        <strong><?php echo (int)$totalUnidades; ?></strong> unidad<?php echo $totalUnidades == 1 ? '' : 'es'; ?><br>
                                        <span style="color:#888; font-size:0.78rem;"><?php echo $numProductos; ?> producto<?php echo $numProductos == 1 ? '' : 's'; ?></span>
                                    </td>
                                    <td>
                                        <?php echo badgeEstado($p['estado']); ?>
                                        <form method="POST" action="pedidos.php" class="estado-select-form" style="margin-top:6px;">
                                            <?php echo gmgCsrfCampo(); ?>
                                            <input type="hidden" name="accion" value="actualizar_estado">
                                            <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                            <select name="estado" onchange="this.form.submit()">
                                                <?php foreach ($estadosValidos as $e): ?>
                                                    <option value="<?php echo $e; ?>" <?php echo $p['estado'] === $e ? 'selected' : ''; ?>><?php echo ucfirst($e); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                    <td><?php echo badgePago($p['pago_estado'] ?? 'pendiente'); ?></td>
                                    <td><?php echo htmlspecialchars($p['metodo_pago']); ?><br><span style="color:#888; font-size:0.78rem;"><?php echo htmlspecialchars($p['referencia_pago'] ?: '-'); ?></span><?php if (!empty($entregaPorPedido[(int)$p['id']])): ?><br><span style="color:#888; font-size:0.78rem;">Entrega: <?php echo htmlspecialchars($entregaPorPedido[(int)$p['id']]); ?></span><?php endif; ?></td>
                                    <td>
                                        <?php if (!empty($p['codigo_descuento'])): ?>
                                            <span class="badge badge-aprobado"><?php echo htmlspecialchars($p['codigo_descuento']); ?></span><br>
                                            <span style="color:#888; font-size:0.78rem;">-$<?php echo number_format($p['descuento_monto'], 0, ',', '.'); ?></span>
                                        <?php else: ?>
                                            <span style="color:#bbb;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($p['total'], 0, ',', '.'); ?></td>
                                    <td>
                                        <button type="button" class="link-editar toggle-detalle" data-id="<?php echo (int)$p['id']; ?>">Ver items</button>
                                    </td>
                                </tr>
                                <tr id="detalle-<?php echo (int)$p['id']; ?>" class="pedido-items-row" style="display:none;">
                                    <td colspan="10">
                                        <ul class="pedido-items-list">
                                            <?php foreach (($itemsPorPedido[$p['id']] ?? []) as $item): ?>
                                                <li><?php echo htmlspecialchars($item['nombre_producto']); ?> — <?php echo (int)$item['cantidad']; ?> x $<?php echo number_format($item['precio_unitario'], 0, ',', '.'); ?></li>
                                            <?php endforeach; ?>
                                            <?php if (empty($itemsPorPedido[$p['id']])): ?>
                                                <li>Sin productos registrados.</li>
                                            <?php endif; ?>
                                        </ul>
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

    <script>
        document.querySelectorAll('.toggle-detalle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var fila = document.getElementById('detalle-' + btn.dataset.id);
                if (fila) {
                    fila.style.display = (fila.style.display === 'table-row') ? 'none' : 'table-row';
                }
            });
        });
    </script>
</body>
</html>
