<?php
/**
 * Registro de pagos (historia E4-H4).
 *
 * Muestra TODOS los intentos de pago de los pedidos: la pasarela, el número de
 * transacción, el monto, el estado, el código de autorización y la fecha.
 * Un pedido puede tener varios intentos (por ejemplo, uno rechazado y uno
 * aprobado). Pantalla de solo lectura: sirve para conciliar los cobros con
 * Mercado Pago.
 */
require_once '../backend/auth.php';
requerirRolAdmin('../'); // solo rol admin (F4-10)
require_once '../backend/config.php';
require_once '../backend/admin_ui.php';

$porPagina = 50;
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$filtroResultado = trim((string)($_GET['resultado'] ?? ''));
$resultadosValidos = ['recibido', 'pendiente', 'rechazado'];
if (!in_array($filtroResultado, $resultadosValidos, true)) { $filtroResultado = ''; }
$filtroPedido = (int)($_GET['pedido'] ?? 0);
$busqueda = trim((string)($_GET['q'] ?? ''));

$rExiste = $conexion->query("SHOW TABLES LIKE 'intentos_pago'");
$tablaLista = $rExiste && $rExiste->num_rows > 0;

$filas = [];
$total = 0;
$totalPaginas = 1;
$resumen = ['recibido' => 0, 'pendiente' => 0, 'rechazado' => 0];
$montoRecibido = 0.0;

if ($tablaLista) {
    $rRes = $conexion->query("SELECT resultado, COUNT(*) AS n, SUM(monto) AS monto FROM intentos_pago GROUP BY resultado");
    if ($rRes) {
        while ($f = $rRes->fetch_assoc()) {
            $resumen[$f['resultado']] = (int)$f['n'];
            if ($f['resultado'] === 'recibido') { $montoRecibido = (float)$f['monto']; }
        }
    }

    $donde = [];
    $tipos = '';
    $valores = [];
    if ($filtroResultado !== '') { $donde[] = 'ip.resultado = ?'; $tipos .= 's'; $valores[] = $filtroResultado; }
    if ($filtroPedido > 0) { $donde[] = 'ip.id_pedido = ?'; $tipos .= 'i'; $valores[] = $filtroPedido; }
    if ($busqueda !== '') {
        $donde[] = '(ip.id_transaccion LIKE ? OR ip.codigo_autorizacion LIKE ? OR c.rut LIKE ? OR c.email LIKE ? OR CONCAT(c.nombre, " ", c.apellido) LIKE ?)';
        $tipos .= 'sssss';
        $like = '%' . $busqueda . '%';
        array_push($valores, $like, $like, $like, $like, $like);
    }
    $sqlDonde = $donde ? ' WHERE ' . implode(' AND ', $donde) : '';
    $desdeSql = " FROM intentos_pago ip JOIN pedidos p ON p.id_pedido = ip.id_pedido LEFT JOIN clientes c ON c.id_cliente = p.id_cliente";

    $stmt = $conexion->prepare("SELECT COUNT(*) AS n" . $desdeSql . $sqlDonde);
    if ($tipos !== '') { $stmt->bind_param($tipos, ...$valores); }
    $stmt->execute();
    $res = $stmt->get_result();
    $total = $res ? (int)$res->fetch_assoc()['n'] : 0;
    $stmt->close();

    $totalPaginas = max(1, (int)ceil($total / $porPagina));
    if ($pagina > $totalPaginas) { $pagina = $totalPaginas; }
    $desdeFila = ($pagina - 1) * $porPagina;

    $stmt = $conexion->prepare("SELECT ip.id_intento, ip.id_pedido, ip.pasarela, ip.id_transaccion, ip.monto, ip.estado, ip.resultado, ip.medio_pago, ip.codigo_autorizacion, ip.verificado, ip.fecha, p.total AS total_pedido, c.nombre, c.apellido" . $desdeSql . $sqlDonde . " ORDER BY ip.fecha DESC, ip.id_intento DESC LIMIT ? OFFSET ?");
    $valoresPag = array_merge($valores, [$porPagina, $desdeFila]);
    $stmt->bind_param($tipos . 'ii', ...$valoresPag);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($f = $res->fetch_assoc())) { $filas[] = $f; }
    $stmt->close();
}

$paramsFiltro = array_filter([
    'resultado' => $filtroResultado,
    'pedido' => $filtroPedido > 0 ? (string)$filtroPedido : '',
    'q' => $busqueda,
], function ($v) { return $v !== ''; });
$enlacePagina = function ($n) use ($paramsFiltro) {
    return 'pagos.php?' . http_build_query(array_merge($paramsFiltro, ['pagina' => $n]));
};

$etiquetaResultado = ['recibido' => 'Pago recibido', 'pendiente' => 'Pendiente', 'rechazado' => 'Rechazado'];
$claseResultado = ['recibido' => 'badge-aprobado', 'pendiente' => 'badge-pendiente', 'rechazado' => 'badge-rechazado'];
$etiquetaMedio = ['credit_card' => 'Tarjeta de crédito', 'debit_card' => 'Tarjeta de débito', 'account_money' => 'Dinero en Mercado Pago', 'prepaid_card' => 'Tarjeta prepago', 'bank_transfer' => 'Transferencia'];

gmgAdminCabecera('Pagos', 'pagos');
?>

    <main class="admin-main">
        <div class="container">
            <div class="admin-heading">
                <h1>Pagos</h1>
            </div>

            <p style="margin:-6px 0 16px; font-size:0.9rem; color:#666; max-width:760px;">
                Cada vez que un cliente vuelve de Mercado Pago queda un registro aquí, con el número de la operación, el monto,
                el resultado y el código de autorización. Un mismo pedido puede tener varios intentos (por ejemplo, uno rechazado y
                después uno aprobado). Esta pantalla es solo para consultar y conciliar los cobros.
            </p>

            <?php if (!$tablaLista): ?>
                <div class="flash-msg flash-error">
                    El registro de pagos todavía no está activado en la base de datos. Hay que ejecutar una vez el archivo
                    <strong>BD_v2/06_sprint1a4_tablas_nuevas.sql</strong> y luego recargar esta página.
                </div>
            <?php else: ?>

            <div class="stats-grid" style="margin-bottom:20px;">
                <a href="pagos.php?resultado=recibido" class="stat-card stat-card-link">
                    <div class="stat-label">Pagos recibidos</div>
                    <div class="stat-value"><?php echo (int)$resumen['recibido']; ?></div>
                    <div style="font-size:0.78rem; color:#888; margin-top:4px;">$<?php echo number_format($montoRecibido, 0, ',', '.'); ?> en total</div>
                </a>
                <a href="pagos.php?resultado=pendiente" class="stat-card stat-card-link">
                    <div class="stat-label">Pendientes</div>
                    <div class="stat-value"><?php echo (int)$resumen['pendiente']; ?></div>
                </a>
                <a href="pagos.php?resultado=rechazado" class="stat-card stat-card-link">
                    <div class="stat-label">Rechazados</div>
                    <div class="stat-value"><?php echo (int)$resumen['rechazado']; ?></div>
                </a>
            </div>

            <form method="GET" action="pagos.php" class="admin-form" style="margin-bottom:20px;">
                <div class="form-grid">
                    <div>
                        <label for="f-resultado">Resultado</label>
                        <select id="f-resultado" name="resultado">
                            <option value="">Todos</option>
                            <?php foreach ($resultadosValidos as $r): ?>
                                <option value="<?php echo $r; ?>"<?php echo $r === $filtroResultado ? ' selected' : ''; ?>><?php echo gmgAdminH($etiquetaResultado[$r]); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="f-pedido">N.º de pedido</label>
                        <input type="number" id="f-pedido" name="pedido" min="1" value="<?php echo $filtroPedido > 0 ? (int)$filtroPedido : ''; ?>" placeholder="Ej: 15">
                    </div>
                    <div>
                        <label for="f-q">Buscar</label>
                        <input type="text" id="f-q" name="q" value="<?php echo gmgAdminH($busqueda); ?>" placeholder="Operación, cliente, RUT o correo">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-admin">Filtrar</button>
                        <?php if ($paramsFiltro): ?><a href="pagos.php" class="btn-admin btn-secundario">Limpiar</a><?php endif; ?>
                    </div>
                </div>
            </form>

            <p style="margin:0 0 10px; font-size:0.85rem; color:#888;">
                <?php echo (int)$total; ?> intento<?php echo $total === 1 ? '' : 's'; ?> de pago<?php echo $paramsFiltro ? ' con los filtros elegidos' : ''; ?>.
            </p>

            <div class="admin-panel">
                <div class="admin-table-wrap">
                    <?php if (count($filas) === 0): ?>
                        <p class="admin-empty">Todavía no hay intentos de pago registrados<?php echo $paramsFiltro ? ' con esos filtros' : ''; ?>.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Fecha y hora</th>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th>Pasarela y operación</th>
                                    <th>Monto</th>
                                    <th>Resultado</th>
                                    <th>Medio y autorización</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filas as $f): ?>
                                <tr>
                                    <td style="white-space:nowrap;"><?php echo gmgAdminH(date('d-m-Y H:i:s', strtotime($f['fecha']))); ?></td>
                                    <td>
                                        <a class="link-editar" href="pedidos.php?q=<?php echo (int)$f['id_pedido']; ?>">#<?php echo (int)$f['id_pedido']; ?></a><br>
                                        <span style="color:#888; font-size:0.78rem;">Total $<?php echo number_format((float)$f['total_pedido'], 0, ',', '.'); ?></span>
                                    </td>
                                    <td><?php echo !empty($f['nombre']) ? gmgAdminH($f['nombre'] . ' ' . $f['apellido']) : '<span style="color:#bbb;">Sin datos</span>'; ?></td>
                                    <td>
                                        <?php echo gmgAdminH($f['pasarela']); ?><br>
                                        <span style="color:#888; font-size:0.8rem;">
                                            <?php echo $f['id_transaccion'] !== null ? 'Operación ' . gmgAdminH($f['id_transaccion']) : 'Sin número de operación'; ?>
                                        </span>
                                    </td>
                                    <td>$<?php echo number_format((float)$f['monto'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $claseResultado[$f['resultado']] ?? 'badge-pendiente'; ?>"><?php echo gmgAdminH($etiquetaResultado[$f['resultado']] ?? $f['resultado']); ?></span><br>
                                        <span style="color:#888; font-size:0.78rem;">
                                            <?php echo gmgAdminH($f['estado']); ?> ·
                                            <?php echo (int)$f['verificado'] === 1 ? 'confirmado con Mercado Pago' : 'sin confirmar con Mercado Pago'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $f['medio_pago'] !== null ? gmgAdminH($etiquetaMedio[$f['medio_pago']] ?? $f['medio_pago']) : '<span style="color:#bbb;">-</span>'; ?><br>
                                        <span style="color:#888; font-size:0.8rem;"><?php echo $f['codigo_autorizacion'] !== null ? 'Autorización ' . gmgAdminH($f['codigo_autorizacion']) : 'Sin código de autorización'; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($totalPaginas > 1): ?>
                <div class="table-actions" style="margin-top:16px; align-items:center;">
                    <?php if ($pagina > 1): ?><a class="btn-admin btn-secundario" href="<?php echo gmgAdminH($enlacePagina($pagina - 1)); ?>">&larr; Más recientes</a><?php endif; ?>
                    <span style="font-size:0.85rem; color:#666;">Página <?php echo (int)$pagina; ?> de <?php echo (int)$totalPaginas; ?></span>
                    <?php if ($pagina < $totalPaginas): ?><a class="btn-admin btn-secundario" href="<?php echo gmgAdminH($enlacePagina($pagina + 1)); ?>">Más antiguos &rarr;</a><?php endif; ?>
                </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </main>
<?php gmgAdminPie(); ?>
