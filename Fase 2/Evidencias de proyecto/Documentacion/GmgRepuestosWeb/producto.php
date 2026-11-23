<?php
/**
 * Ficha de producto (F2-4: E2-H5, RF-012, E2-H11, E8-H1, E8-H4, E8-H5, E8-H7, E8-H9).
 * producto.php?id=N muestra fotos, descripción, SKU, compatibilidad, precio y
 * disponibilidad, con compra, consulta al asistente, relacionados y datos para buscadores.
 */
require_once 'backend/config.php';
require_once 'backend/datos.php';
require_once 'backend/pagina_publica.php';
require_once 'backend/chat_busqueda.php'; // chatDisponibilidad(): misma etiqueta de stock que la tienda y el chat

$idProducto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$producto = null;
$relacionados = [];

if ($idProducto && isset($conexion) && !$conexion->connect_error) {
    $stmt = $conexion->prepare("SELECT * FROM " . gmgSqlProductos() . " p WHERE p.id = ? AND p.activo = 1");
    if ($stmt) {
        $stmt->bind_param('i', $idProducto);
        $stmt->execute();
        $producto = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    } else {
        error_log('[GMG Ficha] No se pudo preparar la consulta del producto: ' . $conexion->error);
    }

    if ($producto) {
        // Hasta 4 de la misma categoría: primero los que sirven a algún mismo vehículo y los con stock.
        $stmt = $conexion->prepare(
            "SELECT r.id_repuesto AS id, r.nombre, r.precio, r.stock,
                    COALESCE((SELECT i.url FROM repuesto_imagenes i
                               WHERE i.id_repuesto = r.id_repuesto AND i.orden = 1),
                             'img/placeholder-repuesto.svg') AS imagen_url
               FROM repuestos r
              WHERE r.activo = 1 AND r.id_categoria = ? AND r.id_repuesto <> ?
              ORDER BY EXISTS (SELECT 1 FROM compatibilidades a
                                 JOIN compatibilidades b ON b.id_vehiculo = a.id_vehiculo
                                WHERE a.id_repuesto = r.id_repuesto AND b.id_repuesto = ?) DESC,
                       r.stock > 0 DESC, r.nombre
              LIMIT 4");
        if ($stmt) {
            $idCategoria = (int)$producto['id_categoria'];
            $stmt->bind_param('iii', $idCategoria, $idProducto, $idProducto);
            $stmt->execute();
            $relacionados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            error_log('[GMG Ficha] No se pudo preparar la consulta de relacionados: ' . $conexion->error);
        }
    }
}

/** Dirección absoluta de una imagen guardada como ruta relativa del sitio. */
function gmgUrlAbsoluta($ruta) {
    return preg_match('#^https?://#i', $ruta) ? $ruta : gmgUrlBase() . '/' . ltrim($ruta, '/');
}

/** Clase CSS de la etiqueta de disponibilidad. */
function gmgClaseDisponibilidad($disponibilidad) {
    return $disponibilidad === 'Sin stock' ? 'agotado' : ($disponibilidad === 'Últimas unidades' ? 'pocas' : 'ok');
}

// Producto inexistente, inactivo o id inválido: 404 con salidas a la tienda y al asistente.
if (!$producto) {
    http_response_code(404);
    gmgCabeceraPublica([
        'titulo'      => 'Repuesto no encontrado',
        'descripcion' => 'El repuesto que buscas no está disponible en GMG Repuestos.',
        'robots'      => 'noindex, follow',
    ]);
    ?>
    <main class="container">
        <div class="sin-resultados ficha-no-encontrada" role="status">
            <h1>No encontramos este repuesto</h1>
            <p>Puede que ya no esté a la venta o que el enlace esté incompleto.</p>
            <p>Busca en la tienda o pregúntale al Asistente Virtual por la pieza que necesitas.</p>
            <a href="index.php" class="btn-back">← Volver a la tienda</a>
            <button type="button" class="gmg-chat-promo-btn" data-abrir-chat>Consultar al Asistente</button>
        </div>
    </main>
    <?php
    gmgPiePublico($conexion ?? null);
    exit;
}

$id = (int)$producto['id'];
$nombre = (string)$producto['nombre'];
$sku = (string)$producto['sku'];
$precio = (float)$producto['precio'];
$stock = (int)$producto['stock'];
$disponibilidad = chatDisponibilidad($stock);
$agotado = $disponibilidad === 'Sin stock';
$imagenes = array_values(array_filter([$producto['imagen_url'], $producto['imagen_url_2'], $producto['imagen_url_3']]));
$modelos = $producto['modelo'] ? explode(', ', $producto['modelo']) : [];
$vehiculos = $producto['compatible_con'] ? explode('; ', $producto['compatible_con']) : [];
$descripcion = trim((string)$producto['descripcion']);
$rutaFicha = gmgUrlProducto($id, $nombre, $producto['modelo']);
$urlCanonica = gmgUrlBase() . '/' . $rutaFicha;
// URL legible (F4-2): si llegó por producto.php?id= o con un nombre viejo, 301 a la dirección correcta.
if (gmgReescrituraActiva()) {
    $pedida = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
    if ($pedida !== (string)parse_url($urlCanonica, PHP_URL_PATH)) {
        header('Location: ' . $urlCanonica, true, 301);
        exit;
    }
}
$imagenesAbsolutas = array_map('gmgUrlAbsoluta', $imagenes);

$textoMeta = $descripcion !== ''
    ? $descripcion
    : $nombre . ($modelos ? ' para Suzuki ' . gmgUnirConY($modelos) : '') . '. SKU ' . $sku . '. Compra en línea en GMG Repuestos.';
$textoMeta = mb_strimwidth(preg_replace('/\s+/', ' ', $textoMeta), 0, 160, '...', 'UTF-8');

// Datos estructurados Product/Offer (E8-H5) para que los buscadores muestren precio y stock.
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type'    => 'Product',
    'name'     => $nombre,
    'sku'      => $sku,
    'image'    => $imagenesAbsolutas,
    'category' => (string)$producto['categoria'],
    'offers'   => [
        '@type'         => 'Offer',
        'url'           => $urlCanonica,
        'priceCurrency' => 'CLP',
        'price'         => round($precio),
        'availability'  => $agotado ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
        'seller'        => ['@type' => 'Organization', 'name' => 'GMG Repuestos SpA'],
    ],
];
if ($descripcion !== '') {
    $jsonLd['description'] = $descripcion;
}
if (!empty($producto['codigo_oem'])) {
    $jsonLd['mpn'] = (string)$producto['codigo_oem'];
}
if (!empty($producto['marca_fabricante'])) {
    $jsonLd['brand'] = ['@type' => 'Brand', 'name' => (string)$producto['marca_fabricante']];
}

gmgCabeceraPublica([
    'titulo'      => $nombre . ' (' . $sku . ')',
    'descripcion' => $textoMeta,
    'canonical'   => $urlCanonica,
    'og_tipo'     => 'product',
    'og_imagen'   => $imagenesAbsolutas[0] ?? '',
    'json_ld'     => $jsonLd,
    'base'        => true, // la URL legible tiene "carpetas": los recursos se piden desde la raíz
]);

$datosCarrito = 'data-id="' . $id . '" data-nombre="' . gmgH($nombre) . '" data-precio="' . gmgH($producto['precio'])
    . '" data-imagen="' . gmgH($imagenes[0] ?? 'img/placeholder-repuesto.svg') . '" data-categoria="' . gmgH($producto['categoria'])
    . '" data-stock="' . $stock . '"';
$preguntaChat = 'Hola, tengo una consulta sobre el repuesto ' . $nombre . ' (SKU ' . $sku . '): ';
$enlaceWhatsApp = gmgEnlaceWhatsApp('Hola, quiero confirmar si el repuesto ' . $nombre . ' (SKU ' . $sku . ') le sirve a mi auto.');
?>

    <main class="container ficha">
        <nav class="ficha-migas" aria-label="Migas de pan">
            <ol>
                <li><a href="index.php">Inicio</a></li>
                <?php if (count($modelos) === 1): ?>
                    <li><a href="index.php?modelo=<?php echo urlencode($modelos[0]); ?>">Suzuki <?php echo gmgH($modelos[0]); ?></a></li>
                <?php endif; ?>
                <li><a href="index.php?categoria=<?php echo urlencode($producto['categoria']); ?>"><?php echo gmgH($producto['categoria']); ?></a></li>
                <li aria-current="page"><?php echo gmgH($nombre); ?></li>
            </ol>
        </nav>

        <article class="ficha-producto" data-producto-id="<?php echo $id; ?>"
                 data-ga-item="<?php echo gmgH(json_encode(['item_id' => (string)$id, 'item_name' => $nombre, 'item_category' => (string)$producto['categoria'], 'price' => round($precio)], JSON_UNESCAPED_UNICODE)); ?>">
            <div class="ficha-galeria producto-img-wrap">
                <?php foreach ($imagenes as $i => $urlImagen): ?>
                    <img src="<?php echo gmgH($urlImagen); ?>"
                         alt="<?php echo gmgH($nombre . ($i > 0 ? ', foto ' . ($i + 1) : '')); ?>"
                         class="producto-img<?php echo $i === 0 ? ' producto-img-activa' : ''; ?>" data-index="<?php echo $i; ?>"
                         <?php echo $i > 0 ? 'loading="lazy"' : ''; ?> onerror="this.onerror=null;this.src='img/placeholder-repuesto.svg';">
                <?php endforeach; ?>
                <?php if (count($imagenes) > 1): ?>
                    <button type="button" class="producto-img-nav producto-img-prev" aria-label="Foto anterior">‹</button>
                    <button type="button" class="producto-img-nav producto-img-next" aria-label="Foto siguiente">›</button>
                    <div class="producto-img-dots">
                        <?php foreach ($imagenes as $i => $urlImagen): ?>
                            <span class="producto-img-dot<?php echo $i === 0 ? ' is-active' : ''; ?>" data-index="<?php echo $i; ?>"></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="ficha-info">
                <p class="ficha-categoria"><?php echo gmgH($producto['categoria']); ?></p>
                <h1 class="ficha-nombre"><?php echo gmgH($nombre); ?></h1>
                <p class="ficha-precio">$<?php echo number_format($precio, 0, ',', '.'); ?> CLP</p>
                <span class="gmg-disponibilidad gmg-disponibilidad--<?php echo gmgClaseDisponibilidad($disponibilidad); ?>"><?php echo gmgH($disponibilidad); ?></span>

                <div class="producto-acciones ficha-acciones">
                    <?php if ($agotado): ?>
                        <button type="button" class="btn-add-cart" disabled>Sin stock</button>
                    <?php else: ?>
                        <button type="button" class="btn-add-cart" <?php echo $datosCarrito; ?>>🛒 Añadir al carrito</button>
                        <button type="button" class="btn-comprar-ahora" <?php echo $datosCarrito; ?>>⚡ Comprar ahora</button>
                    <?php endif; ?>
                </div>
                <?php if ($agotado) { gmgFormularioAvisoStock($id, false); } // aviso de reposición (F3-11) ?>
                <div class="ficha-ayuda">
                    <button type="button" class="ficha-btn-asistente" data-abrir-chat data-chat-pregunta="<?php echo gmgH($preguntaChat); ?>">🤖 Preguntar al asistente</button>
                    <a class="ficha-btn-whatsapp" href="<?php echo gmgH($enlaceWhatsApp); ?>" target="_blank" rel="noopener">Confirmar compatibilidad por WhatsApp</a>
                </div>

                <dl class="ficha-datos">
                    <dt>SKU</dt><dd><?php echo gmgH($sku); ?></dd>
                    <?php if (!empty($producto['codigo_oem'])): ?>
                        <dt>Código OEM</dt><dd><?php echo gmgH($producto['codigo_oem']); ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($producto['marca_fabricante'])): ?>
                        <dt>Marca</dt><dd><?php echo gmgH($producto['marca_fabricante']); ?></dd>
                    <?php endif; ?>
                    <?php if ($modelos): ?>
                        <dt>Modelos</dt><dd><?php echo gmgH(gmgUnirConY($modelos)); ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($producto['ano'])): ?>
                        <dt>Años</dt><dd><?php echo gmgH($producto['ano']); ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($producto['motor'])): ?>
                        <dt>Motor</dt><dd><?php echo gmgH($producto['motor']); ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </article>

        <?php if ($descripcion !== ''): ?>
            <section class="ficha-seccion">
                <h2>Descripción</h2>
                <p><?php echo nl2br(gmgH($descripcion)); ?></p>
            </section>
        <?php endif; ?>

        <section class="ficha-seccion">
            <h2>Compatibilidad</h2>
            <?php if ($vehiculos): ?>
                <ul class="ficha-compat">
                    <?php foreach ($vehiculos as $vehiculo): ?>
                        <li><?php echo gmgH($vehiculo); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No tenemos registrada la compatibilidad de este repuesto. Consúltanos por WhatsApp antes de comprar.</p>
            <?php endif; ?>
            <p class="ficha-nota">Confirma la compatibilidad con el VIN o la patente de tu auto antes de comprar.</p>
        </section>

        <?php if ($relacionados): ?>
            <section class="ficha-seccion">
                <h2>También te puede servir</h2>
                <div class="ficha-relacionados">
                    <?php foreach ($relacionados as $rel): ?>
                        <?php $dispRel = chatDisponibilidad((int)$rel['stock']); ?>
                        <a class="ficha-relacionado" href="<?php echo gmgH(gmgUrlProducto($rel['id'], $rel['nombre'], $producto['modelo'])); ?>">
                            <img src="<?php echo gmgH($rel['imagen_url']); ?>" alt="<?php echo gmgH($rel['nombre']); ?>" loading="lazy"
                                 onerror="this.onerror=null;this.src='img/placeholder-repuesto.svg';">
                            <span class="ficha-relacionado-nombre"><?php echo gmgH($rel['nombre']); ?></span>
                            <span class="ficha-relacionado-precio">$<?php echo number_format((float)$rel['precio'], 0, ',', '.'); ?></span>
                            <span class="gmg-disponibilidad gmg-disponibilidad--<?php echo gmgClaseDisponibilidad($dispRel); ?>"><?php echo gmgH($dispRel); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

<?php
gmgPiePublico($conexion, ['scripts' => ['js/tienda.js'], 'whatsapp' => 'Hola, consulto por el repuesto ' . $nombre . ' (SKU ' . $sku . ')']);
