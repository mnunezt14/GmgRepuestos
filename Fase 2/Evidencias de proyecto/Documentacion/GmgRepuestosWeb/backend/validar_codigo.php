<?php
// Valida un código de descuento y calcula el monto a descontar sobre
// un subtotal dado. El total final SIEMPRE se vuelve a calcular en el
// servidor en checkout.php (nunca se confía en el descuento que venga
// del navegador).
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

// Límite por IP (F3-3, RNF-035): evita probar códigos a ciegas.
if (gmgLimiteSuperado($conexion, 'codigo', 10, 60)) {
    echo json_encode(['valido' => false, 'mensaje' => 'Hiciste muchos intentos. Espera un minuto y vuelve a probar.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$codigo = isset($input['codigo']) && is_string($input['codigo']) ? trim(mb_substr($input['codigo'], 0, 40)) : '';
$subtotal = isset($input['subtotal']) && is_numeric($input['subtotal']) ? (float)$input['subtotal'] : 0;

if ($codigo === '' || $subtotal <= 0) {
    echo json_encode(['valido' => false, 'mensaje' => 'Ingresa un código válido.']);
    exit;
}

$stmt = $conexion->prepare("SELECT codigo, tipo, valor FROM codigos_descuento WHERE codigo = ? AND activo = 1 AND (vigente_hasta IS NULL OR vigente_hasta >= CURDATE()) LIMIT 1");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows === 1) {
    $fila = $res->fetch_assoc();
    $descuento = calcularDescuento($fila['tipo'], (float)$fila['valor'], $subtotal);

    echo json_encode([
        'valido' => true,
        'codigo' => $fila['codigo'],
        'tipo' => $fila['tipo'],
        'valor' => (float)$fila['valor'],
        'descuento' => $descuento,
        'mensaje' => $fila['tipo'] === 'porcentaje'
            ? ('¡Código aplicado! ' . (float)$fila['valor'] . '% de descuento.')
            : '¡Código aplicado!',
    ]);
} else {
    echo json_encode(['valido' => false, 'mensaje' => 'Código inválido o expirado.']);
}
$stmt->close();

function calcularDescuento($tipo, $valor, $subtotal) {
    if ($tipo === 'porcentaje') {
        $descuento = $subtotal * ($valor / 100);
    } else {
        $descuento = $valor;
    }
    if ($descuento > $subtotal) {
        $descuento = $subtotal;
    }
    return round($descuento, 2);
}
