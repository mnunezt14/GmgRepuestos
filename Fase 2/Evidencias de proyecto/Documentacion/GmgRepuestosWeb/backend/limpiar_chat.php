<?php
/**
 * Borra las conversaciones del chat de más de 12 meses (F3-7, E5-H18, RNF-017).
 * Se ejecuta con el cron de Hostinger (hPanel > Avanzado > Trabajos Cron), una vez al día:
 *   php /home/USUARIO/public_html/backend/limpiar_chat.php
 * Solo funciona por línea de comandos: desde el navegador responde 404 (y además está
 * bloqueado en backend/.htaccess).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/config.php';
require __DIR__ . '/chat_busqueda.php';

$borradas = chatBorrarConversacionesAntiguas($conexion, 12);
echo date('d-m-Y H:i') . " - conversaciones de más de 12 meses borradas: $borradas
";
