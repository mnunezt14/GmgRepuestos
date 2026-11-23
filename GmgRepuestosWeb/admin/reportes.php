<?php
/**
 * Reportes CSV (F4-10, E6-H12). Solo rol admin.
 * Descarga de pedidos (por rango de fechas) y del catálogo con stock, listos para Excel en Chile:
 * separador ";", BOM UTF-8 y celdas que empiezan con = + - @ neutralizadas (no se ejecutan como
 * fórmula al abrir el archivo). Solo lee datos: no cambia nada.
 */
require_once '../backend/auth.php';
requerirRolAdmin('../');
require_once '../backend/admin_ui.php';
require_once '../backend/datos.php';

/** Celda segura para CSV: un texto que empieza con = + - @ (o tabulador) se antepone con ' para que Excel no lo ejecute. */
function gmgCeldaCsv($valor) {
    $valor = (string)$valor;
    return preg_match('/^[=+\-@\t\r]/', $valor) ? "'" . $valor : $valor;
}

/** Envía el CSV y termina. */
function gmgDescargarCsv($nombreArchivo, array $encabezados, array $filas) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Cache-Control: no-store');
    $salida = fopen('php://output', 'w');
    fwrite($salida, "\xEF\xBB\xBF");
    fputcsv($salida, $encabezados, ';');
    foreach ($filas as $fila) {
        fputcsv($salida, array_map('gmgCeldaCsv', $fila), ';');
    }
    fclose($salida);
    exit;
}

$hoy = date('Y-m-d');
$desde = isset($_GET['desde']) && is_string($_GET['desde']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$hasta = isset($_GET['hasta']) && is_string($_GET['hasta']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['hasta']) ? $_GET['hasta'] : $hoy;
$tipo = isset($_GET['descargar']) && is_string($_GET['descargar']) ? $_GET['descargar'] : '';

if ($tipo === 'pedidos') {
    $stmt = $conexion->prepare("SELECT id, fecha, nombre_cliente, apellido_cliente, rut_cliente, telefono_cliente, email_cliente,
                                       estado, pago_estado, codigo_descuento, descuento_monto, total
                                  FROM " . gmgSqlPedidos() . " pv WHERE DATE(fecha) BETWEEN ? AND ? ORDER BY fecha");
    $stmt->bind_param('ss', $desde, $hasta);
    $stmt->execute();
    $filas = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $p) {
        $filas[] = [$p['id'], date('d-m-Y H:i', strtotime($p['fecha'])), trim($p['nombre_cliente'] . ' ' . $p['apellido_cliente']), $p['rut_cliente'],
                    $p['telefono_cliente'], $p['email_cliente'], $p['estado'], $p['pago_estado'], $p['codigo_descuento'] ?? '',
                    (int)$p['descuento_monto'], (int)$p['total']];
    }
    $stmt->close();
    gmgDescargarCsv("pedidos_{$desde}_a_{$hasta}.csv", ['Pedido', 'Fecha', 'Cliente', 'RUT', 'Teléfono', 'Correo', 'Estado', 'Pago', 'Código', 'Descuento', 'Total'], $filas);
}
if ($tipo === 'productos') {
    // Mismo formato que acepta Productos > Importar CSV (F4-5): se descarga, se edita en Excel y se vuelve a subir.
    $filas = [];
    $r = $conexion->query("SELECT sku, categoria, nombre, compatible_con, precio, stock, codigo_oem FROM " . gmgSqlProductos() . " p ORDER BY categoria, nombre");
    foreach ($r ? $r->fetch_all(MYSQLI_ASSOC) : [] as $p) {
        $vehiculos = $p['compatible_con'] !== null && $p['compatible_con'] !== '' ? explode('; ', $p['compatible_con']) : [];
        $filas[] = [$p['sku'], $p['categoria'], $p['nombre'], implode(' | ', $vehiculos), count($vehiculos), (int)$p['precio'], (int)$p['stock'], $p['codigo_oem'] ?? ''];
    }
    gmgDescargarCsv('catalogo_stock_' . $hoy . '.csv', ['sku', 'categoria', 'nombre', 'vehiculos_compatibles', 'n_vehiculos', 'precio_referencial_clp', 'stock', 'codigo_oem'], $filas);
}

gmgAdminCabecera('Reportes', 'reportes');
?>
    <main class="admin-main">
        <div class="container">
            <div class="admin-heading"><h1>Reportes</h1></div>
            <p class="form-ayuda">Archivos CSV que se abren con Excel. Solo leen datos: no cambian nada.</p>

            <h2 class="admin-subtitulo">Pedidos</h2>
            <form class="admin-form" method="GET" action="reportes.php">
                <input type="hidden" name="descargar" value="pedidos">
                <div class="form-grid">
                    <div><label for="r_desde">Desde</label><input id="r_desde" type="date" name="desde" value="<?php echo gmgAdminH($desde); ?>" required></div>
                    <div><label for="r_hasta">Hasta</label><input id="r_hasta" type="date" name="hasta" value="<?php echo gmgAdminH($hasta); ?>" required></div>
                    <div class="form-actions"><button type="submit" class="btn-admin">Descargar pedidos (CSV)</button></div>
                </div>
            </form>

            <h2 class="admin-subtitulo">Catálogo y stock</h2>
            <p class="form-ayuda">Viene en el formato de Productos > Importar CSV: puedes cambiar precios o stock en Excel, guardarlo como CSV (separado por punto y coma) y volver a subirlo.</p>
            <p><a class="btn-admin" href="reportes.php?descargar=productos">Descargar catálogo con stock (CSV)</a></p>
        </div>
    </main>
<?php gmgAdminPie(); ?>
