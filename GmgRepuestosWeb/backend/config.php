<?php
/**
 * Configuración central del sitio - GMG Repuestos
 *
 * Aquí se define lo que todas las páginas necesitan: zona horaria, idioma,
 * conexión a la base de datos y claves de Mercado Pago.
 *
 * Las contraseñas y claves NO se escriben en este archivo: se leen del
 * archivo ".env" (ver backend/entorno.php y docs/procedimiento_de_despliegue.md).
 */

// Zona horaria del sitio. Sin esto, PHP usa la zona horaria por defecto del
// servidor de Hostinger (normalmente UTC), por lo que las horas mostradas
// (reloj del dashboard, fecha de los pedidos, encabezado "Date" de los
// correos, etc.) quedaban varias horas adelantadas respecto a la hora real
// de Chile. 'America/Santiago' ajusta el horario de verano automáticamente.
date_default_timezone_set('America/Santiago');

// Idioma y codificación para Chile (historia E10-H14): textos en español de
// Chile (es-CL) y caracteres UTF-8 en todo el sitio.
mb_internal_encoding('UTF-8');
setlocale(LC_TIME, 'es_CL.UTF-8', 'es_CL', 'es_ES.UTF-8', 'es_ES', 'Spanish_Chile.1252');
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Content-Language: es-CL');
}

// Lectura de claves desde el archivo .env y protecciones de conexión (HTTPS y
// cabeceras de seguridad, historia E10-H1).
require_once __DIR__ . '/entorno.php';
require_once __DIR__ . '/seguridad.php';

// Configuración de la base de datos: se toma del .env de cada ambiente
// (DB_HOST, DB_USER, DB_PASS, DB_NAME). En Hostinger se obtienen desde
// hPanel -> Bases de datos MySQL.
$host = gmgEnv('DB_HOST', 'localhost');
$usuario = gmgEnv('DB_USER', '');
$contrasena = gmgEnv('DB_PASS', '');
$base_datos = gmgEnv('DB_NAME', '');

// Desde PHP 8.1 mysqli lanza excepciones ante cualquier error de SQL y eso
// tumba la página completa con "HTTP ERROR 500". El código del sitio revisa
// el resultado de cada consulta (if ($resultado) ...), así que se deja el
// modo clásico: los errores se registran en el log de errores de PHP y la
// consulta simplemente devuelve false.
mysqli_report(MYSQLI_REPORT_ERROR);
// En producción los avisos van solo al log de errores (hPanel > Avanzado >
// Registros de errores), nunca impresos dentro de la página.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Intentar la conexión
$conexion = new mysqli($host, $usuario, $contrasena, $base_datos);

// Verificar la conexión. El detalle técnico va solo al registro de errores;
// al visitante nunca se le muestran datos de la conexión.
if ($conexion->connect_error) {
    error_log('[GMG] Error de conexión a la base de datos: ' . $conexion->connect_error);
    http_response_code(503);
    die("El sitio no está disponible en este momento. Inténtalo nuevamente en unos minutos.");
}

// Conjunto de caracteres: utf8mb4, igual que la base de datos v2
// (con "utf8" fallan los emojis y algunos textos con tilde en modo estricto).
$conexion->set_charset("utf8mb4");

// La base de datos también guarda sus propias fechas (por ejemplo
// pedidos.fecha) con la zona horaria del SERVIDOR de MySQL, que es
// independiente de la de PHP y normalmente también está en UTC. Se alinea
// la sesión de MySQL al mismo desfase horario que PHP acaba de calcular
// para Chile (ej. "-03:00" en horario de verano, "-04:00" en invierno), así
// los pedidos nuevos quedan guardados con la hora real de Chile.
$conexion->query("SET time_zone = '" . date('P') . "'");

// Credenciales Mercado Pago (del .env). En el ambiente de pruebas deben ser
// las credenciales de prueba (sandbox) y en producción las reales.
define('MP_PUBLIC_KEY', gmgEnv('MP_PUBLIC_KEY', ''));
define('MP_ACCESS_TOKEN', gmgEnv('MP_ACCESS_TOKEN', ''));
