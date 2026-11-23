<?php
// Registra la solicitud de un cliente para que se le avise cuando un
// repuesto sin stock vuelva a estar disponible. El bot activa este flujo
// agregando la etiqueta [NOTIFICAR_STOCK:ID] en su respuesta (ver
// bot_config.php); el frontend (chat.js) le pide el contacto al cliente y
// llama a este endpoint.
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';
require_once 'datos.php';

// Límite por IP (F3-3, RNF-035): 5 avisos cada 10 minutos.
if (gmgLimiteSuperado($conexion, 'aviso_stock', 5, 600)) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Ya registraste varios avisos. Espera unos minutos o escríbenos por WhatsApp.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$productoId = (int)($input['producto_id'] ?? 0);
$contacto = isset($input['contacto']) && is_string($input['contacto']) ? trim($input['contacto']) : '';
$sesionId = isset($input['sesion_id']) && is_string($input['sesion_id']) ? trim($input['sesion_id']) : '';

if ($productoId <= 0 || $contacto === '') {
    echo json_encode(['status' => 'error', 'mensaje' => 'Faltan datos para registrar el aviso.']);
    exit;
}

// Correo o celular chileno (F3-11): así el aviso se puede enviar después (F3-9).
$telefono = gmgTelefonoChile($contacto);
if (filter_var($contacto, FILTER_VALIDATE_EMAIL) && mb_strlen($contacto) <= 150) {
    $contacto = mb_strtolower($contacto);
} elseif ($telefono !== '') {
    $contacto = $telefono;
} else {
    echo json_encode(['status' => 'error', 'mensaje' => 'Escribe un correo o un celular chileno (por ejemplo +56 9 1234 5678).']);
    exit;
}
if (mb_strlen($sesionId) > 64) {
    $sesionId = mb_substr($sesionId, 0, 64);
}

// Verificar que el producto exista
$check = $conexion->prepare("SELECT id_repuesto AS id, nombre FROM repuestos WHERE id_repuesto = ? LIMIT 1");
$check->bind_param("i", $productoId);
$check->execute();
$resCheck = $check->get_result();
if (!$resCheck || $resCheck->num_rows === 0) {
    $check->close();
    echo json_encode(['status' => 'error', 'mensaje' => 'El producto indicado no existe.']);
    exit;
}
$producto = $resCheck->fetch_assoc();
$check->close();

$stmt = $conexion->prepare("INSERT INTO avisos_stock (id_repuesto, contacto, sesion_id) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $productoId, $contacto, $sesionId);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'mensaje' => '¡Listo! Te avisaremos apenas "' . $producto['nombre'] . '" vuelva a tener stock.',
    ]);
} else {
    echo json_encode(['status' => 'error', 'mensaje' => 'No se pudo registrar el aviso. Intenta de nuevo.']);
}
$stmt->close();
?>
