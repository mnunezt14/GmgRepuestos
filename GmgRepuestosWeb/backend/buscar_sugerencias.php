<?php
// Sugerencias de autocompletado para la barra de búsqueda de la tienda.
// Devuelve hasta 6 productos desde 2 caracteres (decisión del PO, CP-05), con las
// mismas reglas del buscador y del chat: todas las palabras, plurales y sinónimos (F2-5).
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';
require_once 'datos.php';
require_once 'chat_busqueda.php';
require_once 'pagina_publica.php';

// Límite por IP (F3-3, RNF-035): 60 por minuto (se pide mientras el cliente escribe).
if (gmgLimiteSuperado($conexion, 'sugerencias', 60, 60)) {
    echo json_encode(['sugerencias' => []]);
    exit;
}

$q = isset($_GET['q']) && is_string($_GET['q']) ? trim(mb_substr($_GET['q'], 0, 100)) : '';
$busqueda = mb_strlen($q) >= 2 ? gmgBusquedaTienda($q) : null;

if (!$busqueda) {
    echo json_encode(['sugerencias' => []]);
    exit;
}

$sql = "SELECT p.id, p.nombre, p.sku, p.precio, p.imagen_url, p.modelo, p.categoria
        FROM " . gmgSqlProductos() . " p
        WHERE p.activo = 1 AND " . $busqueda['condicion'] . "
        ORDER BY " . $busqueda['orden'] . ", (p.stock > 0) DESC, p.nombre ASC
        LIMIT 6";

$sugerencias = [];
$stmt = $conexion->prepare($sql);
if ($stmt) {
    $params = array_merge($busqueda['params'], $busqueda['params_orden']);
    $stmt->bind_param($busqueda['tipos'] . $busqueda['tipos_orden'], ...$params);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $fila) {
        $sugerencias[] = [
            'id' => (int)$fila['id'],
            'nombre' => $fila['nombre'],
            'sku' => $fila['sku'],
            'precio' => (float)$fila['precio'],
            'imagen' => $fila['imagen_url'] ?: 'img/placeholder-repuesto.svg',
            'modelo' => (string)$fila['modelo'],
            'categoria' => $fila['categoria'],
            'url' => gmgUrlProducto($fila['id'], $fila['nombre'], $fila['modelo']), // F4-2
        ];
    }
    $stmt->close();
} else {
    error_log('[GMG Sugerencias] No se pudo preparar la búsqueda: ' . $conexion->error);
}

echo json_encode(['sugerencias' => $sugerencias]);
