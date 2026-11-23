<?php
/**
 * Mapa del sitio para buscadores (F4-1, E8-H2, RF-067). El .htaccess lo sirve también como
 * /sitemap.xml. Incluye la portada, cada modelo con repuestos, cada ficha de repuesto activo y
 * las páginas informativas. No incluye el panel, el checkout ni búsquedas.
 */
require_once 'backend/config.php';
require_once 'backend/pagina_publica.php';
require_once 'backend/datos.php';

header('Content-Type: application/xml; charset=utf-8');
$base = gmgUrlBase();
$urls = [[$base . '/', 'daily', '1.0']];
if (isset($conexion) && !$conexion->connect_error) {
    foreach (gmgDatosModelosPublicos($conexion)['modelosInfo'] as $modelo) {
        $urls[] = [$base . '/index.php?modelo=' . rawurlencode($modelo['corto']), 'weekly', '0.8'];
    }
    $r = $conexion->query("SELECT id, nombre, modelo FROM " . gmgSqlProductos() . " p WHERE p.activo = 1 ORDER BY id");
    while ($r && ($fila = $r->fetch_assoc())) {
        $urls[] = [$base . '/' . gmgUrlProducto($fila['id'], $fila['nombre'], $fila['modelo']), 'weekly', '0.6'];
    }
}
foreach (['quienes-somos.php', 'contacto.php', 'despacho.php', 'cambios-y-devoluciones.php', 'terminos-y-condiciones.php', 'politica-de-privacidad.php'] as $pagina) {
    $urls[] = [$base . '/' . $pagina, 'monthly', '0.3'];
}
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$loc, $frecuencia, $prioridad]) {
    echo '  <url><loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc><changefreq>$frecuencia</changefreq><priority>$prioridad</priority></url>\n";
}
echo "</urlset>\n";
