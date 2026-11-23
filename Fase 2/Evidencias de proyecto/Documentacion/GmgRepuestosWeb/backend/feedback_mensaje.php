<?php
// Guarda el feedback (👍/👎) que el cliente deja sobre una respuesta del Asistente Virtual
// (mensajes_chat.feedback). Auditoría 15-11: solo la sesión del chat dueña del mensaje puede
// calificarlo (antes bastaba con adivinar el número) y hay un límite de 30 por IP cada 10 minutos.
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$mensajeId = (int)($input['mensaje_id'] ?? 0);
$feedback = isset($input['feedback']) && is_string($input['feedback']) ? trim($input['feedback']) : '';
$sesionId = isset($input['sesion_id']) && is_string($input['sesion_id']) ? mb_substr(trim($input['sesion_id']), 0, 64) : '';

if ($mensajeId <= 0 || $sesionId === '' || !in_array($feedback, ['positivo', 'negativo'], true)) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Datos inválidos.']);
    exit;
}
if (gmgLimiteSuperado($conexion, 'feedback', 30, 600)) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Demasiadas calificaciones seguidas.']);
    exit;
}

$stmt = $conexion->prepare("UPDATE mensajes_chat m JOIN conversaciones_chat c ON c.id_conversacion = m.id_conversacion
                               SET m.feedback = ? WHERE m.id_mensaje = ? AND m.rol = 'bot' AND c.sesion_id = ?");
$stmt->bind_param('sis', $feedback, $mensajeId, $sesionId);
echo json_encode($stmt->execute() && $stmt->affected_rows > 0 ? ['status' => 'success'] : ['status' => 'error', 'mensaje' => 'No se pudo guardar la calificación.']);
$stmt->close();
