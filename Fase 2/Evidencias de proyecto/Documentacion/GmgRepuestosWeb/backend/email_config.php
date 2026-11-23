<?php
/**
 * Configuración del sistema de avisos por correo - GMG Repuestos
 *
 * Cubre dos tipos de aviso:
 *  1. Aviso al ADMINISTRADOR cuando un cliente realiza un pedido nuevo
 *     (queda "pendiente" esperando revisión/aprobación).
 *  2. Aviso al CLIENTE cuando el estado de su pedido cambia (aprobado,
 *     rechazado, etc.).
 *
 * Los correos se envían por SMTP autenticado (backend/SmtpMailer.php),
 * usando una casilla de correo real creada en hPanel (Hostinger), no
 * Gmail ni la función mail() de PHP.
 *
 * Las claves de la casilla (usuario y contraseña) se leen del archivo .env
 * (historia E10-H4). Ver backend/entorno.php.
 */
require_once __DIR__ . '/entorno.php';

// 1. Datos del servidor SMTP (hPanel -> Correos -> tu casilla -> "Configurar
//    cliente de correo"). Para una casilla @tudominio.cl creada en Hostinger
//    normalmente son:
//      Host:    smtp.hostinger.com
//      Puerto:  465 (SSL) o 587 (STARTTLS) — usa 465 si no estás seguro.
//      Usuario: la casilla completa, ej. avisos@gmgrepuestos.cl
//      Clave:   la contraseña de esa casilla de correo (NO tu clave de hPanel)
define('SMTP_HOST', gmgEnv('SMTP_HOST', 'smtp.hostinger.com'));
// Por defecto 465 con SSL. Si Hostinger o la red bloquean el 465, en el .env: SMTP_PORT=587 y SMTP_SECURE=tls.
define('SMTP_PORT', (int)gmgEnv('SMTP_PORT', '465'));
define('SMTP_SECURE', in_array(gmgEnv('SMTP_SECURE', 'ssl'), ['ssl', 'tls'], true) ? gmgEnv('SMTP_SECURE', 'ssl') : 'ssl');
define('SMTP_USER', gmgEnv('SMTP_USER', ''));
define('SMTP_PASS', gmgEnv('SMTP_PASS', ''));

// 2. Remitente con el que llegarán los correos (normalmente la misma
//    casilla que SMTP_USER).
define('SMTP_FROM_EMAIL', 'Ventas@gmgrepuestos.cl');
define('SMTP_FROM_NAME', gmgEnv('SMTP_FROM_NAME', 'GMG Repuestos'));

// 3. Correo(s) del administrador que deben recibir el aviso de "pedido
//    nuevo esperando revisión". Si son varios, sepáralos con comas. Por
//    ahora se usa la misma casilla Ventas@gmgrepuestos.cl (no se indicó
//    una distinta) — cámbiala aquí si quieres que los avisos de pedidos
//    nuevos lleguen a otro correo.
define('ADMIN_NOTIFICATION_EMAIL', 'Ventas@gmgrepuestos.cl');

// 4. Interruptor general: mientras esté en false, el sistema NO intenta
//    enviar ningún correo (útil mientras terminas de configurar las
//    credenciales reales de arriba, para que el checkout no se demore ni
//    falle esperando una conexión SMTP que aún no funciona). Ponlo en true
//    cuando ya hayas completado SMTP_USER, SMTP_PASS, SMTP_FROM_EMAIL y
//    ADMIN_NOTIFICATION_EMAIL con tus datos reales.
define('EMAIL_ENABLED', true);

// 5. URL base del panel de administración, para incluir un link directo al
//    pedido en el correo de aviso al administrador. Ajusta si tu sitio vive
//    en una subcarpeta.
define('ADMIN_PANEL_URL', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'tudominio.cl') . '/admin/pedidos.php');

// 6. Número de WhatsApp que se muestra en los correos al cliente para que
//    coordine directamente (GMG Repuestos no hace envíos, todo se coordina
//    por WhatsApp).
define('EMAIL_WHATSAPP_NUMBER', gmgEnv('WHATSAPP_NUMBER', '56900000000')); // Número real en el .env (WHATSAPP_NUMBER)
?>
