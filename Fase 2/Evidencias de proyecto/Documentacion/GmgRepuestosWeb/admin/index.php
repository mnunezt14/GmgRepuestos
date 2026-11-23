<?php
require_once '../backend/auth.php';
requerirAdmin('../');
require_once '../backend/config.php';
require_once '../backend/datos.php';

const DASH_STOCK_MINIMO = 5;

// --- Estadísticas rápidas ---
$totalProductos = 0;
$stockBajo = 0;
$totalPedidos = 0;
$pedidosPendientes = 0;
$pagosPorConfirmar = 0;
$ingresosAprobados = 0;

$r = $conexion->query("SELECT COUNT(*) AS c FROM repuestos");
if ($r) { $totalProductos = (int)$r->fetch_assoc()['c']; }

$r = $conexion->query("SELECT COUNT(*) AS c FROM repuestos WHERE stock < " . DASH_STOCK_MINIMO);
if ($r) { $stockBajo = (int)$r->fetch_assoc()['c']; }

// Detalle de los productos con stock bajo (para el medidor "Stock bajo").
$productosStockBajo = [];
$r = $conexion->query("SELECT p.id, p.nombre, p.modelo, p.stock FROM " . gmgSqlProductos() . " p WHERE p.stock < " . DASH_STOCK_MINIMO . " ORDER BY p.stock ASC, p.nombre ASC LIMIT 8");
if ($r) {
    while ($fila = $r->fetch_assoc()) {
        $productosStockBajo[] = $fila;
    }
}

$r = $conexion->query("SELECT COUNT(*) AS c FROM pedidos");
if ($r) { $totalPedidos = (int)$r->fetch_assoc()['c']; }

$r = $conexion->query("SELECT COUNT(*) AS c FROM pedidos WHERE estado = 'pendiente'");
if ($r) { $pedidosPendientes = (int)$r->fetch_assoc()['c']; }

// Pedidos que YA tienen el pago confirmado por Mercado Pago pero siguen
// esperando que el administrador los apruebe manualmente — la cola más
// urgente, porque el cliente ya pagó.
$r = $conexion->query("SELECT COUNT(*) AS c FROM pedidos WHERE estado = 'pendiente' AND pago_estado = 'recibido'");
if ($r) { $pagosPorConfirmar = (int)$r->fetch_assoc()['c']; }

$r = $conexion->query("SELECT COALESCE(SUM(total), 0) AS s FROM pedidos WHERE estado = 'aprobado'");
if ($r) { $ingresosAprobados = (float)$r->fetch_assoc()['s']; }

// --- Pedidos por estado (para el medidor de la derecha) ---
$conteoEstados = ['pendiente' => 0, 'aprobado' => 0, 'rechazado' => 0, 'error' => 0];
$r = $conexion->query("SELECT estado, COUNT(*) AS c FROM pedidos GROUP BY estado");
if ($r) {
    while ($fila = $r->fetch_assoc()) {
        if (isset($conteoEstados[$fila['estado']])) {
            $conteoEstados[$fila['estado']] = (int)$fila['c'];
        }
    }
}

// --- Productos más vendidos (por unidades, histórico) ---
$topProductos = [];
$r = $conexion->query("SELECT nombre_repuesto AS nombre_producto, SUM(cantidad) AS unidades FROM pedido_detalle GROUP BY nombre_repuesto ORDER BY unidades DESC LIMIT 5");
if ($r) {
    while ($fila = $r->fetch_assoc()) {
        $topProductos[] = $fila;
    }
}

// --- Ingresos aprobados por día, últimos 14 días (para el gráfico de línea) ---
$diasIngresos = [];
for ($i = 13; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $diasIngresos[$fecha] = 0.0;
}
$r = $conexion->query("SELECT DATE(fecha) AS dia, SUM(total) AS total FROM pedidos WHERE estado = 'aprobado' AND fecha >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY DATE(fecha)");
if ($r) {
    while ($fila = $r->fetch_assoc()) {
        if (isset($diasIngresos[$fila['dia']])) {
            $diasIngresos[$fila['dia']] = (float)$fila['total'];
        }
    }
}

// --- Últimos 5 pedidos (con cliente, pago y cantidad de productos) ---
$ultimosPedidos = [];
$r = $conexion->query("SELECT pv.id, pv.fecha, pv.estado, pv.pago_estado, pv.total, pv.metodo_pago, pv.nombre_cliente, pv.apellido_cliente FROM " . gmgSqlPedidos() . " pv ORDER BY pv.fecha DESC LIMIT 5");
if ($r) {
    while ($fila = $r->fetch_assoc()) {
        $ultimosPedidos[] = $fila;
    }
}

$resumenItemsPorPedido = [];
if (count($ultimosPedidos) > 0) {
    $ids = array_map(fn($p) => (int)$p['id'], $ultimosPedidos);
    $idsList = implode(',', $ids);
    $r = $conexion->query("SELECT id_pedido AS pedido_id, SUM(cantidad) AS unidades, COUNT(*) AS lineas FROM pedido_detalle WHERE id_pedido IN ($idsList) GROUP BY id_pedido");
    if ($r) {
        while ($fila = $r->fetch_assoc()) {
            $resumenItemsPorPedido[$fila['pedido_id']] = $fila;
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

/**
 * Lista de medidores (barras horizontales tipo "meter"): cada fila trae su
 * propio nombre visible + valor, así la identidad nunca depende solo del
 * color (importante para daltonismo, ya que aprobado/pendiente/rechazado
 * usan tonos que pueden confundirse sin la etiqueta de texto).
 *
 * $filas: [['label'=>string, 'valor'=>number, 'color'=>hex, 'texto'=>string
 *           opcional, 'max'=>number opcional (si no, se usa el máximo del
 *           grupo), 'umbral'=>number opcional (línea de referencia
 *           punteada), 'href'=>url opcional]]
 */
function panelMeters(array $filas) {
    if (empty($filas)) {
        return '<p class="dash-panel-empty">Sin datos para mostrar.</p>';
    }
    $maxGrupo = 0;
    foreach ($filas as $fila) {
        $maxGrupo = max($maxGrupo, (float)$fila['valor']);
    }
    if ($maxGrupo <= 0) { $maxGrupo = 1; }

    $html = '<div class="meter-list">';
    foreach ($filas as $fila) {
        $maxFila = $fila['max'] ?? $maxGrupo;
        $pct = $maxFila > 0 ? min(100, round(((float)$fila['valor'] / $maxFila) * 100)) : 0;
        $texto = $fila['texto'] ?? number_format((float)$fila['valor'], 0, ',', '.');

        $umbralHtml = '';
        if (isset($fila['umbral']) && $maxFila > 0) {
            $pctUmbral = min(100, round(($fila['umbral'] / $maxFila) * 100));
            $umbralHtml = '<div class="meter-umbral" style="left:' . $pctUmbral . '%;" title="Mínimo recomendado: ' . (int)$fila['umbral'] . '"></div>';
        }

        $cuerpo = '<div class="meter-row-head"><span class="meter-label">' . htmlspecialchars($fila['label']) . '</span><span class="meter-value">' . htmlspecialchars($texto) . '</span></div>'
            . '<div class="meter-track">' . $umbralHtml . '<div class="meter-fill" style="width:' . $pct . '%; background:' . htmlspecialchars($fila['color']) . ';"></div></div>';

        if (!empty($fila['href'])) {
            $html .= '<a class="meter-row meter-row-link" href="' . htmlspecialchars($fila['href']) . '">' . $cuerpo . '</a>';
        } else {
            $html .= '<div class="meter-row">' . $cuerpo . '</div>';
        }
    }
    $html .= '</div>';
    return $html;
}

/**
 * Gráfico de línea (SVG, sin librerías externas) para los ingresos
 * aprobados de los últimos 14 días. Incluye marca de hover (ver el script
 * al final de la página) y un valor directo en el último punto.
 *
 * $puntos: [['label' => '17/09', 'valor' => 55000.0], ...] en orden
 * cronológico.
 */
function chartLineaSVG(array $puntos) {
    $n = count($puntos);
    if ($n === 0) {
        return '<p class="dash-panel-empty">Sin datos suficientes.</p>';
    }

    $anchoSvg = 680;
    $altoSvg = 200;
    $padIzq = 6;
    $padDer = 6;
    $padTop = 20;
    $padAbajo = 26;

    $valores = array_column($puntos, 'valor');
    $maxValor = max($valores);
    $maxEje = $maxValor > 0 ? $maxValor * 1.15 : 1000;

    $anchoUtil = $anchoSvg - $padIzq - $padDer;
    $altoUtil = $altoSvg - $padTop - $padAbajo;
    $pasoX = $n > 1 ? $anchoUtil / ($n - 1) : 0;
    $baseY = $padTop + $altoUtil;

    $coords = [];
    foreach ($puntos as $i => $p) {
        $x = $padIzq + $i * $pasoX;
        $y = $padTop + $altoUtil - ($maxEje > 0 ? ($p['valor'] / $maxEje) * $altoUtil : 0);
        $coords[] = ['x' => $x, 'y' => $y];
    }

    $pathLinea = '';
    foreach ($coords as $i => $c) {
        $pathLinea .= ($i === 0 ? 'M' : 'L') . round($c['x'], 1) . ' ' . round($c['y'], 1) . ' ';
    }
    $pathArea = $pathLinea . 'L ' . round($coords[$n - 1]['x'], 1) . ' ' . round($baseY, 1) . ' L ' . round($coords[0]['x'], 1) . ' ' . round($baseY, 1) . ' Z';

    $colorLinea = '#D32F2F';

    $svg = '<svg viewBox="0 0 ' . $anchoSvg . ' ' . $altoSvg . '" preserveAspectRatio="none" role="img" aria-label="Ingresos aprobados por día, últimos 14 días">';

    // Líneas de referencia (base y máximo del eje)
    $svg .= '<line x1="' . $padIzq . '" y1="' . round($baseY, 1) . '" x2="' . ($anchoSvg - $padDer) . '" y2="' . round($baseY, 1) . '" stroke="#eee" stroke-width="1"/>';
    $svg .= '<line x1="' . $padIzq . '" y1="' . $padTop . '" x2="' . ($anchoSvg - $padDer) . '" y2="' . $padTop . '" stroke="#f4f4f4" stroke-width="1"/>';
    $svg .= '<text x="0" y="' . ($padTop + 4) . '" font-size="10" fill="#aaa">$' . number_format($maxEje, 0, ',', '.') . '</text>';
    $svg .= '<text x="0" y="' . round($baseY - 3, 1) . '" font-size="10" fill="#aaa">$0</text>';

    // Área (10% de opacidad) y línea (2px)
    $svg .= '<path d="' . $pathArea . '" fill="' . $colorLinea . '" opacity="0.1"/>';
    $svg .= '<path d="' . $pathLinea . '" fill="none" stroke="' . $colorLinea . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';

    // Etiquetas del eje X (una de cada dos, para no saturar 14 días)
    foreach ($coords as $i => $c) {
        if ($i % 2 === 0 || $i === $n - 1) {
            $anchor = 'middle';
            if ($i === 0) { $anchor = 'start'; }
            if ($i === $n - 1) { $anchor = 'end'; }
            $svg .= '<text x="' . round($c['x'], 1) . '" y="' . ($altoSvg - 6) . '" font-size="10" fill="#aaa" text-anchor="' . $anchor . '">' . htmlspecialchars($puntos[$i]['label']) . '</text>';
        }
    }

    // Puntos: hit-target grande (r=12) + tooltip nativo de respaldo
    foreach ($coords as $i => $c) {
        $valorTxt = number_format($puntos[$i]['valor'], 0, ',', '.');
        $svg .= '<circle class="chart-punto" cx="' . round($c['x'], 1) . '" cy="' . round($c['y'], 1) . '" r="12" fill="transparent" data-label="' . htmlspecialchars($puntos[$i]['label']) . '" data-valor="' . $valorTxt . '"><title>' . htmlspecialchars($puntos[$i]['label']) . ': $' . $valorTxt . '</title></circle>';
    }

    // Marcador + valor directo en el último punto (hoy)
    $ultimoCoord = end($coords);
    $ultimoPunto = end($puntos);
    $svg .= '<circle cx="' . round($ultimoCoord['x'], 1) . '" cy="' . round($ultimoCoord['y'], 1) . '" r="4" fill="' . $colorLinea . '" stroke="#fff" stroke-width="2"/>';
    $svg .= '<text x="' . round($ultimoCoord['x'] - 6, 1) . '" y="' . round($ultimoCoord['y'] - 10, 1) . '" font-size="11" font-weight="700" fill="' . $colorLinea . '" text-anchor="end">$' . number_format($ultimoPunto['valor'], 0, ',', '.') . '</text>';

    // Crosshair + punto de hover, ocultos por defecto (los activa el script)
    $svg .= '<line class="chart-crosshair" x1="0" y1="' . $padTop . '" x2="0" y2="' . round($baseY, 1) . '" stroke="#ccc" stroke-width="1" opacity="0"/>';
    $svg .= '<circle class="chart-hover-dot" cx="0" cy="0" r="5" fill="' . $colorLinea . '" stroke="#fff" stroke-width="2" opacity="0"/>';

    $svg .= '</svg>';

    return '<div class="chart-linea-wrap">' . $svg . '<div class="chart-tooltip"></div></div>';
}

// --- Datos ya preparados para los medidores ---
$coloresEstado = ['pendiente' => '#b3760c', 'aprobado' => '#1e8e4c', 'rechazado' => '#D32F2F', 'error' => '#D32F2F'];
$etiquetasEstado = ['pendiente' => 'Pendientes', 'aprobado' => 'Aprobados', 'rechazado' => 'Rechazados', 'error' => 'Con error'];
$filasEstado = [];
foreach ($conteoEstados as $estado => $cantidad) {
    $filasEstado[] = [
        'label' => $etiquetasEstado[$estado],
        'valor' => $cantidad,
        'color' => $coloresEstado[$estado],
        'texto' => $cantidad . ' pedido' . ($cantidad == 1 ? '' : 's'),
        'href' => 'pedidos.php?estado=' . urlencode($estado),
    ];
}

$filasProductos = [];
foreach ($topProductos as $prod) {
    $filasProductos[] = [
        'label' => $prod['nombre_producto'],
        'valor' => (int)$prod['unidades'],
        'color' => '#111111',
        'texto' => (int)$prod['unidades'] . ' unid.',
    ];
}

$filasStock = [];
$maxEjeStock = max(DASH_STOCK_MINIMO + 3, 8);
foreach ($productosStockBajo as $prod) {
    $filasStock[] = [
        'label' => $prod['nombre'],
        'valor' => (int)$prod['stock'],
        'max' => $maxEjeStock,
        'umbral' => DASH_STOCK_MINIMO,
        'color' => '#D32F2F',
        'texto' => (int)$prod['stock'] . ' en stock',
        'href' => 'productos.php?editar=' . (int)$prod['id'] . '&modelo=' . urlencode($prod['modelo']),
    ];
}

$puntosIngresos = [];
foreach ($diasIngresos as $fecha => $monto) {
    $puntosIngresos[] = ['label' => date('d/m', strtotime($fecha)), 'valor' => $monto];
}
$totalPeriodo14 = array_sum($diasIngresos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel administrador - GMG Repuestos</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo gmgVersionArchivo('css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo gmgVersionArchivo('css/admin.css'); ?>">
</head>
<body class="admin-body">

    <div class="admin-topbar">
        <div class="container">
            <div class="admin-brand">GMG <span>Admin</span></div>
            <nav class="admin-nav">
<?php require_once '../backend/admin_ui.php'; gmgAdminEnlaces('dashboard'); ?>
                <span class="admin-user">Hola, <?php echo htmlspecialchars(nombreAdminActual()); ?></span>
                <a href="../logout.php" class="admin-logout">Cerrar sesión</a>
            </nav>
        </div>
    </div>

    <main class="admin-main">
        <div class="container">
            <div class="admin-heading">
                <h1>Resumen</h1>
                <span class="dash-refresh-note"><span class="dash-refresh-dot"></span> Actualizado a las <?php echo date('H:i:s'); ?> · se refresca solo cada 2 min</span>
            </div>

            <?php if ($pagosPorConfirmar > 0): ?>
                <div class="dash-alert">
                    <div class="dash-alert-texto">⚠️ Hay <strong><?php echo $pagosPorConfirmar; ?></strong> pedido<?php echo $pagosPorConfirmar == 1 ? '' : 's'; ?> con el pago ya recibido, esperando tu aprobación.</div>
                    <a href="pedidos.php?estado=pendiente&pago=recibido" class="btn-admin btn-peligro">Revisar ahora</a>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <a href="productos.php" class="stat-card stat-card-link">
                    <div class="stat-label">Productos</div>
                    <div class="stat-value"><?php echo $totalProductos; ?></div>
                </a>

                <a href="#stock-bajo" class="stat-card stat-card-link<?php echo $stockBajo > 0 ? ' stat-card-alerta' : ''; ?>">
                    <div class="stat-label">Stock bajo (&lt; <?php echo DASH_STOCK_MINIMO; ?>)</div>
                    <div class="stat-value"><?php echo $stockBajo; ?></div>
                </a>

                <a href="pedidos.php" class="stat-card stat-card-link">
                    <div class="stat-label">Pedidos totales</div>
                    <div class="stat-value"><?php echo $totalPedidos; ?></div>
                </a>

                <a href="pedidos.php?estado=pendiente" class="stat-card stat-card-link">
                    <div class="stat-label">Pedidos pendientes</div>
                    <div class="stat-value"><?php echo $pedidosPendientes; ?></div>
                </a>

                <a href="pedidos.php?estado=pendiente&pago=recibido" class="stat-card stat-card-link<?php echo $pagosPorConfirmar > 0 ? ' stat-card-alerta' : ''; ?>">
                    <div class="stat-label">Pagos por confirmar</div>
                    <div class="stat-value"><?php echo $pagosPorConfirmar; ?></div>
                </a>

                <a href="pedidos.php?estado=aprobado" class="stat-card stat-card-link">
                    <div class="stat-label">Ingresos aprobados</div>
                    <div class="stat-value">$<?php echo number_format($ingresosAprobados, 0, ',', '.'); ?></div>
                </a>
            </div>

            <div class="dash-grid">
                <div class="dash-panel">
                    <div class="dash-panel-header">
                        <span class="dash-panel-title">Ingresos aprobados · últimos 14 días</span>
                        <span class="dash-panel-figure">$<?php echo number_format($totalPeriodo14, 0, ',', '.'); ?></span>
                    </div>
                    <?php echo chartLineaSVG($puntosIngresos); ?>
                </div>

                <div class="dash-panel">
                    <div class="dash-panel-header">
                        <span class="dash-panel-title">Pedidos por estado</span>
                        <span class="dash-panel-figure"><?php echo $totalPedidos; ?></span>
                    </div>
                    <?php echo panelMeters($filasEstado); ?>
                </div>
            </div>

            <div class="dash-grid">
                <div class="dash-panel">
                    <div class="dash-panel-header">
                        <span class="dash-panel-title">Más vendidos</span>
                    </div>
                    <?php echo panelMeters($filasProductos); ?>
                </div>

                <div class="dash-panel" id="stock-bajo">
                    <div class="dash-panel-header">
                        <span class="dash-panel-title">Stock bajo (&lt; <?php echo DASH_STOCK_MINIMO; ?>)</span>
                        <span class="dash-panel-figure"><?php echo $stockBajo; ?></span>
                    </div>
                    <?php if (empty($filasStock)): ?>
                        <p class="dash-panel-empty">Todo el stock está sobre <?php echo DASH_STOCK_MINIMO; ?> unidades.</p>
                    <?php else: ?>
                        <?php echo panelMeters($filasStock); ?>
                        <?php if ($stockBajo > count($filasStock)): ?>
                            <a href="productos.php" class="stat-mini-more" style="margin-top:10px;">Ver <?php echo $stockBajo - count($filasStock); ?> más &rarr;</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-heading">
                <h1>Últimos pedidos</h1>
                <a href="pedidos.php" class="btn-admin btn-secundario">Ver todos</a>
            </div>

            <div class="admin-panel">
                <div class="admin-table-wrap">
                    <?php if (count($ultimosPedidos) === 0): ?>
                        <p class="admin-empty">Todavía no se han registrado pedidos.</p>
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
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimosPedidos as $p): ?>
                                <tr>
                                    <td>#<?php echo (int)$p['id']; ?></td>
                                    <td><?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($p['fecha']))); ?></td>
                                    <td>
                                        <?php if (!empty($p['nombre_cliente'])): ?>
                                            <?php echo htmlspecialchars($p['nombre_cliente'] . ' ' . $p['apellido_cliente']); ?>
                                        <?php else: ?>
                                            <span style="color:#bbb;">Sin datos</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php $resumen = $resumenItemsPorPedido[$p['id']] ?? null; ?>
                                        <?php echo $resumen ? (int)$resumen['unidades'] . ' unid. (' . (int)$resumen['lineas'] . ')' : '—'; ?>
                                    </td>
                                    <td><?php echo badgeEstado($p['estado']); ?></td>
                                    <td><?php echo badgePago($p['pago_estado'] ?? 'pendiente'); ?></td>
                                    <td><?php echo htmlspecialchars($p['metodo_pago']); ?></td>
                                    <td>$<?php echo number_format($p['total'], 0, ',', '.'); ?></td>
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
        // Hover: crosshair + tooltip del gráfico de línea (sin librerías).
        document.querySelectorAll('.chart-linea-wrap').forEach(function (wrap) {
            var svg = wrap.querySelector('svg');
            var tooltip = wrap.querySelector('.chart-tooltip');
            var crosshair = svg.querySelector('.chart-crosshair');
            var hoverDot = svg.querySelector('.chart-hover-dot');
            var puntos = Array.prototype.slice.call(svg.querySelectorAll('.chart-punto')).map(function (c) {
                return {
                    x: parseFloat(c.getAttribute('cx')),
                    y: parseFloat(c.getAttribute('cy')),
                    label: c.getAttribute('data-label'),
                    valor: c.getAttribute('data-valor')
                };
            });
            if (puntos.length === 0) { return; }

            function mostrar(punto) {
                crosshair.setAttribute('x1', punto.x);
                crosshair.setAttribute('x2', punto.x);
                crosshair.setAttribute('opacity', '1');
                hoverDot.setAttribute('cx', punto.x);
                hoverDot.setAttribute('cy', punto.y);
                hoverDot.setAttribute('opacity', '1');

                tooltip.textContent = '';
                var fecha = document.createElement('strong');
                fecha.textContent = punto.label;
                var salto = document.createElement('br');
                var valor = document.createElement('span');
                valor.textContent = '$' + punto.valor;
                tooltip.appendChild(fecha);
                tooltip.appendChild(salto);
                tooltip.appendChild(valor);
                tooltip.style.opacity = '1';

                var rectWrap = wrap.getBoundingClientRect();
                var rectSvg = svg.getBoundingClientRect();
                var escalaX = rectSvg.width / svg.viewBox.baseVal.width;
                var left = (rectSvg.left - rectWrap.left) + punto.x * escalaX - 55;
                left = Math.max(0, Math.min(left, rectWrap.width - 120));
                tooltip.style.left = left + 'px';
            }

            function ocultar() {
                crosshair.setAttribute('opacity', '0');
                hoverDot.setAttribute('opacity', '0');
                tooltip.style.opacity = '0';
            }

            svg.addEventListener('pointermove', function (e) {
                var rect = svg.getBoundingClientRect();
                var escalaX = svg.viewBox.baseVal.width / rect.width;
                var xSvg = (e.clientX - rect.left) * escalaX;
                var cercano = puntos[0];
                var mejorDist = Math.abs(puntos[0].x - xSvg);
                puntos.forEach(function (p) {
                    var d = Math.abs(p.x - xSvg);
                    if (d < mejorDist) {
                        mejorDist = d;
                        cercano = p;
                    }
                });
                mostrar(cercano);
            });
            svg.addEventListener('pointerleave', ocultar);
        });

        // Pantalla de monitoreo: se refresca sola cada 2 minutos.
        setTimeout(function () {
            window.location.reload();
        }, 120000);
    </script>
</body>
</html>
