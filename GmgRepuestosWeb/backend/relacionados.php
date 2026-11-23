<?php
// Productos relacionados ("Complemente su compra") para mostrar en el
// carrito, a partir de la categoría de un producto ya agregado.
// Base de datos v2: repuestos + categorias + repuesto_imagenes.
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$categoria = isset($_GET['categoria']) && is_string($_GET['categoria']) ? trim($_GET['categoria']) : '';
$excluirIds = isset($_GET['excluir']) && is_string($_GET['excluir']) ? array_filter(array_map('intval', explode(',', $_GET['excluir']))) : [];

$selectBase = "SELECT r.id_repuesto AS id, r.nombre, r.precio, r.sku,
                      COALESCE((SELECT i.url FROM repuesto_imagenes i
                                 WHERE i.id_repuesto = r.id_repuesto AND i.orden = 1),
                               'img/placeholder-repuesto.svg') AS imagen_url
                 FROM repuestos r
                 JOIN categorias c ON c.id_categoria = r.id_categoria";

function relacionadosLeer($resultado) {
    $lista = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $lista[] = [
                'id' => (int)$fila['id'],
                'nombre' => $fila['nombre'],
                'precio' => (float)$fila['precio'],
                'imagen' => $fila['imagen_url'] ?: 'img/placeholder-repuesto.svg',
                'sku' => $fila['sku'],
            ];
        }
    }
    return $lista;
}

// Sentencia preparada (F3-4, RNF-030); los ids ya son enteros.
$where = ["r.activo = 1"];
if ($categoria !== '') {
    $where[] = "c.nombre = ?";
}
if (count($excluirIds) > 0) {
    $where[] = "r.id_repuesto NOT IN (" . implode(',', array_map('intval', $excluirIds)) . ")";
}

$sql = $selectBase . " WHERE " . implode(" AND ", $where) . " ORDER BY RAND() LIMIT 4";
$stmt = $conexion->prepare($sql);
if ($categoria !== '') {
    $stmt->bind_param('s', $categoria);
}
$stmt->execute();
$productos = relacionadosLeer($stmt->get_result());
$stmt->close();

// Si no hay suficientes productos de la misma categoría, completar con otros al azar
if (count($productos) < 4) {
    $idsExistentes = array_merge($excluirIds, array_column($productos, 'id'));
    $idsExistentesList = count($idsExistentes) > 0 ? implode(',', array_map('intval', $idsExistentes)) : '0';
    $faltan = 4 - count($productos);
    $sql2 = $selectBase . " WHERE r.activo = 1 AND r.id_repuesto NOT IN ($idsExistentesList) ORDER BY RAND() LIMIT $faltan";
    $productos = array_merge($productos, relacionadosLeer($conexion->query($sql2)));
}

echo json_encode(['productos' => $productos]);
