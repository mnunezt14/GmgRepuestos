<?php
require_once 'backend/config.php';
require_once 'backend/modelos.php';
require_once 'backend/datos.php';
require_once 'backend/pagina_publica.php';
require_once 'backend/chat_busqueda.php'; // chatDisponibilidad(): misma etiqueta de stock que el asistente

// Filtros de la URL: solo texto (un ?q[]=x rompía htmlspecialchars en PHP 8).
foreach (['q', 'modelo', 'categoria', 'orden', 'disponible', 'precio_min', 'precio_max'] as $filtro) {
    if (isset($_GET[$filtro]) && !is_string($_GET[$filtro])) {
        unset($_GET[$filtro]);
    }
}

// Filtros y orden del listado (F4-4, E2-H8, RF-015): solo valores conocidos o números enteros.
$ordenesListado = ['' => 'Más relevantes', 'precio_asc' => 'Precio: menor a mayor', 'precio_desc' => 'Precio: mayor a menor', 'nombre' => 'Nombre (A-Z)'];
$ordenElegido = isset($ordenesListado[$_GET['orden'] ?? '']) ? (string)($_GET['orden'] ?? '') : '';
$soloDisponibles = !empty($_GET['disponible']);
$precioMin = isset($_GET['precio_min']) && ctype_digit($_GET['precio_min']) ? (int)$_GET['precio_min'] : null;
$precioMax = isset($_GET['precio_max']) && ctype_digit($_GET['precio_max']) ? (int)$_GET['precio_max'] : null;

$productos = [];
$porPagina = 24;
$paginaActual = isset($_GET['pagina']) && is_string($_GET['pagina']) && ctype_digit($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$totalProductos = 0;
$totalPaginas = 1;
$mostrarSelector = true; // Por defecto mostramos las tarjetas de autos
$categoriasDisponibles = [];

// Modelos que realmente tienen productos cargados (dinámico: se arma
// solo, sin tocar código, apenas el admin carga el primer producto de un
// modelo nuevo). $modelosInfo trae, para cada uno, su nombre completo,
// su nombre corto (el que se usa en la URL y el buscador) y su imagen.
$modelosInfo = [];
$modelosCortosTexto = 'todos los modelos Suzuki';

if (isset($conexion) && !$conexion->connect_error) {
    $datosModelos = gmgDatosModelosPublicos($conexion);
    $modelosInfo = $datosModelos['modelosInfo'];
    $modelosCortosTexto = $datosModelos['modelosCortosTexto'];

    // Categorías con al menos un repuesto activo, para el filtro del buscador (siempre visible)
    $resCategorias = $conexion->query("SELECT c.nombre FROM categorias c
                                        WHERE EXISTS (SELECT 1 FROM repuestos r WHERE r.id_categoria = c.id_categoria AND r.activo = 1)
                                        ORDER BY c.orden, c.nombre");
    if ($resCategorias) {
        while ($filaCategoria = $resCategorias->fetch_assoc()) {
            $categoriasDisponibles[] = $filaCategoria['nombre'];
        }
    }

    // Si hay una búsqueda, un modelo o una categoría seleccionada, ocultamos el selector y buscamos repuestos
    if (!empty($_GET['modelo']) || !empty($_GET['q']) || !empty($_GET['categoria'])) {
        $mostrarSelector = false;

        // Base v2: los repuestos se filtran por modelo a través de la tabla
        // compatibilidades (un repuesto puede servir a varios modelos).
        $sql = "SELECT * FROM " . gmgSqlProductos() . " p";
        $where = ["p.activo = 1"];
        $tipos = '';
        $params = [];
        $orden = 'p.categoria, p.nombre';

        if (!empty($_GET['modelo'])) {
            [$condicionModelo, $tiposModelo, $paramsModelo] = gmgCondicionModelo($_GET['modelo'], 'p.id');
            $where[] = $condicionModelo;
            $tipos .= $tiposModelo;
            $params = array_merge($params, $paramsModelo);
        }
        if (!empty($_GET['categoria'])) {
            $where[] = "p.categoria = ?";
            $tipos .= 's';
            $params[] = $_GET['categoria'];
        }
        // Buscador por palabras (F2-5): mismas reglas que el chat, todas las palabras deben estar.
        $busqueda = !empty($_GET['q']) ? gmgBusquedaTienda(mb_substr($_GET['q'], 0, 100)) : null;
        if ($busqueda) {
            $where[] = $busqueda['condicion'];
            $tipos .= $busqueda['tipos'];
            $params = array_merge($params, $busqueda['params']);
        }

        if ($soloDisponibles) {
            $where[] = "p.stock > 0";
        }
        if ($precioMin !== null) {
            $where[] = "p.precio >= ?";
            $tipos .= 'i';
            $params[] = $precioMin;
        }
        if ($precioMax !== null) {
            $where[] = "p.precio <= ?";
            $tipos .= 'i';
            $params[] = $precioMax;
        }

        $condicion = " WHERE " . implode(" AND ", $where);
        $consultar = function ($sql, $tipos, $params) use ($conexion) {
            $stmt = $conexion->prepare($sql);
            if (!$stmt) {
                error_log('[GMG Tienda] No se pudo preparar el listado: ' . $conexion->error);
                return [];
            }
            if ($tipos !== '') {
                $stmt->bind_param($tipos, ...$params);
            }
            $stmt->execute();
            $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $filas;
        };

        // Paginación (F2-6, RF-008): 24 por página; una página fuera de rango muestra la última.
        $totalProductos = (int)($consultar("SELECT COUNT(*) AS total FROM " . gmgSqlProductos() . " p" . $condicion, $tipos, $params)[0]['total'] ?? 0);
        $totalPaginas = max(1, (int)ceil($totalProductos / $porPagina));
        $paginaActual = min($paginaActual, $totalPaginas);

        $sql .= $condicion;
        $ordenesSql = ['precio_asc' => 'p.precio ASC, p.nombre', 'precio_desc' => 'p.precio DESC, p.nombre', 'nombre' => 'p.nombre ASC'];
        if ($ordenElegido !== '') {
            $sql .= " ORDER BY " . $ordenesSql[$ordenElegido]; // orden elegido por el cliente (F4-4)
        } elseif ($busqueda) {
            // Lo más parecido primero (SKU exacto, palabras en el nombre); los parámetros del orden van después.
            $sql .= " ORDER BY " . $busqueda['orden'] . ", (p.stock > 0) DESC, " . $orden;
            $tipos .= $busqueda['tipos_orden'];
            $params = array_merge($params, $busqueda['params_orden']);
        } else {
            $sql .= " ORDER BY " . $orden;
        }
        $sql .= " LIMIT $porPagina OFFSET ?";
        $tipos .= 'i';
        $params[] = ($paginaActual - 1) * $porPagina;

        $productos = $totalProductos > 0 ? $consultar($sql, $tipos, $params) : [];
        if ($totalProductos === 0 && !empty($_GET['q'])) {
            gmgRegistrarBusquedaSinResultado($conexion, $_GET['q'], $_GET['modelo'] ?? '', $_GET['categoria'] ?? ''); // F3-10
        }
    }
}

/** Dirección de una página del listado, con los mismos filtros (la página 1 va sin "pagina"). */
function gmgEnlacePagina($numero) {
    $filtros = [];
    foreach (['modelo', 'categoria', 'q', 'orden', 'disponible', 'precio_min', 'precio_max'] as $filtro) {
        if (!empty($_GET[$filtro])) {
            $filtros[$filtro] = $_GET[$filtro];
        }
    }
    if ($numero > 1) {
        $filtros['pagina'] = $numero;
    }
    return 'index.php?' . http_build_query($filtros);
}
// --- Datos para buscadores (E8-H8): título, descripción y etiqueta canonical ---
// Las páginas con filtros (modelo, categoría, búsqueda) apuntan con "canonical"
// a su versión principal para que no cuenten como contenido duplicado.
$modeloCanonico = !empty($_GET['modelo']) ? gmgModeloCorto((string)$_GET['modelo']) : '';
$baseSitio = gmgUrlBase();
$urlCanonica = $modeloCanonico !== ''
    ? $baseSitio . '/index.php?modelo=' . urlencode($modeloCanonico) . ($paginaActual > 1 ? '&pagina=' . $paginaActual : '')
    : $baseSitio . '/';
$tituloPagina = 'GMG Repuestos SpA - Especialistas en Suzuki';
$descripcionPagina = 'Repuestos originales y compatibles para ' . $modelosCortosTexto . '. Compra en línea con Mercado Pago y coordina la entrega por WhatsApp.';
if ($modeloCanonico !== '') {
    $tituloPagina = 'Repuestos para Suzuki ' . $modeloCanonico . ' - GMG Repuestos';
    $descripcionPagina = 'Repuestos para Suzuki ' . $modeloCanonico . ': filtros, frenos, motor, suspensión y más. Revisa la compatibilidad por año y compra en línea.';
    if ($paginaActual > 1) {
        // Cada página del listado con título propio (no se repiten títulos en buscadores).
        $tituloPagina = 'Repuestos para Suzuki ' . $modeloCanonico . ' (página ' . $paginaActual . ') - GMG Repuestos';
    }
}
$esBusquedaFiltrada = !empty($_GET['q']) || !empty($_GET['categoria']) || $ordenElegido !== '' || $soloDisponibles || $precioMin !== null || $precioMax !== null;

gmgCabeceraPublica([
    'titulo_completo' => $tituloPagina,
    'descripcion'     => $descripcionPagina,
    'canonical'       => $urlCanonica,
    'robots'          => $esBusquedaFiltrada ? 'noindex, follow' : null,
    'css'             => ['css/model-carousel.css'],
]);
?>

    <div class="critical-rule">
        ⚠️ REGLA CRÍTICA: Por favor confirma la compatibilidad de tu repuesto con nosotros vía WhatsApp antes de comprar para evitar errores.
    </div>

    <!-- Barra de búsqueda siempre visible -->
    <section class="search-section">
        <div class="container">
            <form action="index.php" method="GET" class="search-form" id="searchForm" autocomplete="off">
                <div class="search-input-wrap">
                    <label for="searchInput" class="sr-only">Buscar repuesto por nombre o SKU</label>
                    <input type="text" name="q" id="searchInput" placeholder="Buscar por nombre o código SKU (ej: FRE-SWI3-PDL)" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <div id="searchSugerencias" class="search-sugerencias"></div>
                </div>
                <label for="filtroModelo" class="sr-only">Modelo</label>
                <select name="modelo" id="filtroModelo">
                    <option value="">Todos los modelos</option>
                    <?php foreach ($modelosInfo as $mi): ?>
                        <option value="<?php echo htmlspecialchars($mi['corto']); ?>" <?php echo (isset($_GET['modelo']) && $_GET['modelo'] == $mi['corto']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($mi['completo']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="filtroCategoria" class="sr-only">Categoría</label>
                <select name="categoria" id="filtroCategoria">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categoriasDisponibles as $categoriaOpcion): ?>
                        <option value="<?php echo htmlspecialchars($categoriaOpcion); ?>" <?php echo (isset($_GET['categoria']) && $_GET['categoria'] === $categoriaOpcion) ? 'selected' : ''; ?>><?php echo htmlspecialchars($categoriaOpcion); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-search">Buscar</button>
            </form>
        </div>
    </section>

    <main class="container">

        <?php if ($mostrarSelector): ?>
            <!-- ESTADO 1: SELECTOR DE MODELOS -->
            <section class="hero-carousel" id="heroCarousel">
                <div class="hero-track">
                    <div class="hero-slide is-active">
                        <div class="hero-band-inner">
                            <h1 class="hero-title">Repuestos <span>Suzuki</span> al instante</h1>
                            <p class="hero-subtitle">Piezas originales y compatibles para <?php echo htmlspecialchars($modelosCortosTexto); ?>. Encuentra lo que buscas y coordina tu compra directo por WhatsApp.</p>
                        </div>
                    </div>
                    <div class="hero-slide">
                        <div class="hero-band-inner">
                            <h1 class="hero-title">Compra <span>rápido</span>, sin vueltas</h1>
                            <p class="hero-subtitle">Usa "Comprar ahora" en cualquier repuesto y paga directo con Mercado Pago en pocos clics, sin pasos de más.</p>
                        </div>
                    </div>
                    <div class="hero-slide">
                        <div class="hero-band-inner">
                            <h1 class="hero-title">Busca por <span>SKU</span> exacto</h1>
                            <p class="hero-subtitle">¿Ya tienes el código de tu repuesto? Escríbelo en el buscador y aparecerán sugerencias al instante.</p>
                        </div>
                    </div>
                </div>
                <div class="hero-dots">
                    <button type="button" class="hero-dot is-active" data-hero-index="0" aria-label="Mostrar mensaje 1"></button>
                    <button type="button" class="hero-dot" data-hero-index="1" aria-label="Mostrar mensaje 2"></button>
                    <button type="button" class="hero-dot" data-hero-index="2" aria-label="Mostrar mensaje 3"></button>
                </div>
            </section>

            <section class="gmg-chat-promo">
                <div class="gmg-chat-promo-icon">🤖</div>
                <div class="gmg-chat-promo-texto">
                    <h3>¿No encuentras tu repuesto?</h3>
                    <p>Pregúntale a nuestro Asistente Virtual con IA: identifica piezas por foto, busca en el catálogo en tiempo real y te ayuda a comprar sin salir del chat.</p>
                </div>
                <button type="button" id="gmgHomeChatCTA" class="gmg-chat-promo-btn">Hablar con el Asistente</button>
            </section>

            <h2 class="section-title">Selecciona tu modelo Suzuki</h2>

            <?php if (count($modelosInfo) > 0): ?>
                <!-- Carrusel de modelos: avanza solo con las flechas, los puntos,
                     el teclado o deslizando con el dedo (nunca automático). -->
                <div class="model-carousel" id="modelCarousel" role="region" aria-roledescription="carrusel" aria-label="Modelos Suzuki">
                    <button type="button" class="mc-nav mc-nav--prev" aria-label="Ver modelos anteriores" hidden>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <div class="mc-track" tabindex="0">
                        <?php foreach ($modelosInfo as $mi): ?>
                            <a href="index.php?modelo=<?php echo urlencode($mi['corto']); ?>"
                               class="mc-card"
                               aria-label="Ver repuestos para <?php echo htmlspecialchars($mi['completo']); ?>">
                                <span class="mc-card__media<?php echo $mi['tiene_foto'] ? '' : ' mc-card__media--sin-foto'; ?>">
                                    <span class="mc-card__placeholder" aria-hidden="true">
                                        <svg viewBox="0 0 120 52"><path d="M6 36v-6c0-4 3-7 9-8l18-2 12-10c2-2 5-3 9-3h24c5 0 8 1 11 4l11 10 10 2c5 1 8 4 8 9v4z"/><circle cx="31" cy="38" r="9"/><circle cx="31" cy="38" r="4"/><circle cx="91" cy="38" r="9"/><circle cx="91" cy="38" r="4"/></svg>
                                        <span>Foto próximamente</span>
                                    </span>
                                    <?php if ($mi['tiene_foto']): ?>
                                        <img class="mc-card__img" src="<?php echo htmlspecialchars($mi['imagen']); ?>" alt="<?php echo htmlspecialchars($mi['completo']); ?>" loading="lazy" draggable="false" onerror="this.parentNode.classList.add('mc-card__media--sin-foto');this.remove();">
                                    <?php endif; ?>
                                </span>
                                <span class="mc-card__body">
                                    <span class="mc-card__marca">Suzuki</span>
                                    <span class="mc-card__nombre"><?php echo htmlspecialchars($mi['corto']); ?></span>
                                    <span class="mc-card__meta">
                                        <?php echo (int)$mi['repuestos']; ?> repuestos<?php if ($mi['anios'] !== ''): ?> · <?php echo htmlspecialchars($mi['anios']); ?><?php endif; ?>
                                    </span>
                                    <span class="mc-card__cta">Ver repuestos <span aria-hidden="true">→</span></span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="mc-nav mc-nav--next" aria-label="Ver más modelos" hidden>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                    </button>
                    <div class="mc-dots" aria-label="Páginas del carrusel"></div>
                </div>
            <?php else: ?>
                <p style="color:#888; text-align:center; margin: 20px 0;">Todavía no hay productos cargados. Vuelve pronto.</p>
            <?php endif; ?>

        <?php else: ?>
            <!-- ESTADO 2: CATÁLOGO DE REPUESTOS -->
            <a href="index.php" class="btn-back">← Volver a todos los modelos</a>

            <?php
                $tituloCatálogo = "Repuestos Disponibles";
                if (!empty($_GET['modelo'])) {
                    $tituloCatálogo = "Repuestos para Suzuki " . htmlspecialchars($_GET['modelo']);
                }
                if (!empty($_GET['q'])) {
                    $tituloCatálogo .= " - Búsqueda: '" . htmlspecialchars($_GET['q']) . "'";
                }
            ?>
            <h2 id="listado"><?php echo $tituloCatálogo; ?></h2>

            <!-- Filtros y orden (F4-4, E2-H8, RF-015): conservan modelo, categoría y búsqueda -->
            <form class="listado-filtros" method="GET" action="index.php#listado">
                <?php foreach (['modelo', 'categoria', 'q'] as $filtroOculto): ?>
                    <?php if (!empty($_GET[$filtroOculto])): ?>
                        <input type="hidden" name="<?php echo $filtroOculto; ?>" value="<?php echo gmgH($_GET[$filtroOculto]); ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <label class="listado-filtro">
                    <span>Ordenar</span>
                    <select name="orden">
                        <?php foreach ($ordenesListado as $valorOrden => $textoOrden): ?>
                            <option value="<?php echo gmgH($valorOrden); ?>" <?php echo $valorOrden === $ordenElegido ? 'selected' : ''; ?>><?php echo gmgH($textoOrden); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="listado-filtro">
                    <span>Precio desde</span>
                    <input type="number" name="precio_min" min="0" step="1000" inputmode="numeric" placeholder="$ mín." value="<?php echo $precioMin !== null ? $precioMin : ''; ?>">
                </label>
                <label class="listado-filtro">
                    <span>Hasta</span>
                    <input type="number" name="precio_max" min="0" step="1000" inputmode="numeric" placeholder="$ máx." value="<?php echo $precioMax !== null ? $precioMax : ''; ?>">
                </label>
                <label class="listado-filtro listado-filtro-check">
                    <input type="checkbox" name="disponible" value="1" <?php echo $soloDisponibles ? 'checked' : ''; ?>>
                    <span>Solo con stock</span>
                </label>
                <button type="submit" class="listado-filtros-aplicar">Aplicar</button>
                <?php if ($ordenElegido !== '' || $soloDisponibles || $precioMin !== null || $precioMax !== null): ?>
                    <a class="listado-filtros-limpiar" href="<?php echo gmgH('index.php?' . http_build_query(array_filter(['modelo' => $_GET['modelo'] ?? '', 'categoria' => $_GET['categoria'] ?? '', 'q' => $_GET['q'] ?? '']))); ?>#listado">Quitar filtros</a>
                <?php endif; ?>
            </form>

            <?php if (count($productos) > 0): ?>
                <?php $desde = ($paginaActual - 1) * $porPagina + 1; ?>
                <p class="listado-resumen">
                    <?php echo $totalProductos === 1 ? '1 repuesto' : gmgH(number_format($totalProductos, 0, ',', '.')) . ' repuestos'; ?>
                    <?php if ($totalPaginas > 1): ?>· mostrando del <?php echo $desde; ?> al <?php echo $desde + count($productos) - 1; ?><?php endif; ?>
                </p>
                <div class="productos-grid">
                    <?php foreach($productos as $producto): ?>
                    <?php
                        $imagenes = [$producto['imagen_url']];
                        if (!empty($producto['imagen_url_2'])) { $imagenes[] = $producto['imagen_url_2']; }
                        if (!empty($producto['imagen_url_3'])) { $imagenes[] = $producto['imagen_url_3']; }
                        $numImagenes = count($imagenes);
                        // Disponibilidad (RF-013, E2-H6): "Disponible", "Últimas unidades" (3 o menos) o "Sin stock".
                        $disponibilidad = chatDisponibilidad((int)$producto['stock']);
                        $agotado = $disponibilidad === 'Sin stock';
                        $claseDisponibilidad = $agotado ? 'agotado' : ($disponibilidad === 'Últimas unidades' ? 'pocas' : 'ok');
                    ?>
                    <div class="producto-card" data-producto-id="<?php echo (int)$producto['id']; ?>">
                        <div class="producto-img-wrap">
                            <?php foreach ($imagenes as $i => $urlImagen): ?>
                                <img src="<?php echo htmlspecialchars($urlImagen); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="producto-img<?php echo $i === 0 ? ' producto-img-activa' : ''; ?>" data-index="<?php echo $i; ?>" loading="lazy" onerror="this.onerror=null;this.src='img/placeholder-repuesto.svg';">
                            <?php endforeach; ?>
                            <?php if ($numImagenes > 1): ?>
                                <button type="button" class="producto-img-nav producto-img-prev" aria-label="Foto anterior">‹</button>
                                <button type="button" class="producto-img-nav producto-img-next" aria-label="Foto siguiente">›</button>
                                <div class="producto-img-dots">
                                    <?php for ($i = 0; $i < $numImagenes; $i++): ?>
                                        <span class="producto-img-dot<?php echo $i === 0 ? ' is-active' : ''; ?>" data-index="<?php echo $i; ?>"></span>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="producto-info">
                            <div class="producto-categoria"><?php echo htmlspecialchars($producto['categoria']); ?></div>
                            <h3 class="producto-nombre"><a href="<?php echo gmgH(gmgUrlProducto($producto['id'], $producto['nombre'], $producto['modelo'] ?? '')); ?>"><?php echo gmgH($producto['nombre']); ?></a></h3>
                            <div class="producto-detalles">
                                <p><strong>Modelo:</strong> <?php echo htmlspecialchars($producto['modelo'] ?? ''); ?></p>
                                <p><strong>Año:</strong> <?php echo htmlspecialchars($producto['ano'] ?? ''); ?></p>
                                <p><strong>SKU:</strong> <?php echo htmlspecialchars($producto['sku']); ?></p>
                                <?php if (!empty($producto['compatible_con'])): ?>
                                    <?php $vehiculosCompatibles = explode('; ', $producto['compatible_con']); ?>
                                    <details class="producto-compat">
                                        <summary>Vehículos compatibles (<?php echo count($vehiculosCompatibles); ?>)</summary>
                                        <ul>
                                            <?php foreach ($vehiculosCompatibles as $vehiculoCompatible): ?>
                                                <li><?php echo htmlspecialchars($vehiculoCompatible); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </details>
                                <?php endif; ?>
                            </div>
                            <div class="producto-precio">$<?php echo number_format($producto['precio'], 0, ',', '.'); ?> CLP</div>
                            <span class="gmg-disponibilidad gmg-disponibilidad--<?php echo $claseDisponibilidad; ?>"><?php echo gmgH($disponibilidad); ?></span>

                            <div class="producto-acciones">
                                <?php if ($agotado): ?>
                                <button type="button" class="btn-add-cart" disabled>Sin stock</button>
                                <?php else: ?>
                                <button class="btn-add-cart"
                                        data-id="<?php echo htmlspecialchars($producto['id']); ?>"
                                        data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                        data-precio="<?php echo htmlspecialchars($producto['precio']); ?>"
                                        data-imagen="<?php echo htmlspecialchars($producto['imagen_url']); ?>"
                                        data-categoria="<?php echo htmlspecialchars($producto['categoria']); ?>"
                                        data-stock="<?php echo (int)$producto['stock']; ?>">
                                    🛒 Añadir al carrito
                                </button>
                                <button class="btn-comprar-ahora"
                                        data-id="<?php echo htmlspecialchars($producto['id']); ?>"
                                        data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                        data-precio="<?php echo htmlspecialchars($producto['precio']); ?>"
                                        data-imagen="<?php echo htmlspecialchars($producto['imagen_url']); ?>"
                                        data-categoria="<?php echo htmlspecialchars($producto['categoria']); ?>"
                                        data-stock="<?php echo (int)$producto['stock']; ?>">
                                    ⚡ Comprar ahora
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php if ($agotado) { gmgFormularioAvisoStock($producto['id']); } // aviso de reposición (F3-11) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPaginas > 1): ?>
                    <?php
                        // Números visibles: la primera, la última y dos alrededor de la actual; el resto como "…".
                        $numeros = array_unique(array_merge([1, $totalPaginas], range(max(1, $paginaActual - 2), min($totalPaginas, $paginaActual + 2))));
                        sort($numeros);
                    ?>
                    <nav class="paginacion" aria-label="Páginas de resultados">
                        <?php if ($paginaActual > 1): ?>
                            <a class="paginacion-flecha" href="<?php echo gmgH(gmgEnlacePagina($paginaActual - 1)); ?>#listado" rel="prev">‹ Anterior</a>
                        <?php endif; ?>
                        <ol>
                            <?php $anterior = 0; ?>
                            <?php foreach ($numeros as $numero): ?>
                                <?php if ($numero - $anterior > 1): ?><li class="paginacion-salto" aria-hidden="true">…</li><?php endif; ?>
                                <li>
                                    <?php if ($numero === $paginaActual): ?>
                                        <span aria-current="page"><?php echo $numero; ?></span>
                                    <?php else: ?>
                                        <a href="<?php echo gmgH(gmgEnlacePagina($numero)); ?>#listado" aria-label="Página <?php echo $numero; ?>"><?php echo $numero; ?></a>
                                    <?php endif; ?>
                                </li>
                                <?php $anterior = $numero; ?>
                            <?php endforeach; ?>
                        </ol>
                        <?php if ($paginaActual < $totalPaginas): ?>
                            <a class="paginacion-flecha" href="<?php echo gmgH(gmgEnlacePagina($paginaActual + 1)); ?>#listado" rel="next">Siguiente ›</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <?php
                    // Sin resultados (F3-10): alternativas, asistente y WhatsApp con lo que buscó.
                    $buscado = (string)($_GET['q'] ?? '');
                    $alternativas = [];
                    if ($buscado !== '' && (!empty($_GET['modelo']) || !empty($_GET['categoria']))) {
                        $alternativas['index.php?' . http_build_query(['q' => $buscado])] = 'Buscar "' . $buscado . '" en todos los modelos y categorías';
                    }
                    if (!empty($_GET['modelo'])) {
                        $alternativas['index.php?' . http_build_query(['modelo' => $_GET['modelo']])] = 'Ver todos los repuestos para Suzuki ' . gmgModeloCorto((string)$_GET['modelo']);
                    }
                    foreach (array_slice($categoriasDisponibles, 0, 6) as $categoriaAlternativa) {
                        $alternativas['index.php?' . http_build_query(array_filter(['modelo' => $_GET['modelo'] ?? '', 'categoria' => $categoriaAlternativa]))] = $categoriaAlternativa;
                    }
                    $mensajeWhatsApp = 'Hola, busqué ' . ($buscado !== '' ? '"' . $buscado . '"' : 'un repuesto')
                        . (!empty($_GET['modelo']) ? ' para mi Suzuki ' . gmgModeloCorto((string)$_GET['modelo']) : '') . ' en la página y no lo encontré. ¿Lo tienen?';
                ?>
                <div class="sin-resultados" role="status">
                    <p>No encontramos repuestos para tu búsqueda<?php echo !empty($_GET['modelo']) ? ' en el modelo ' . gmgH($_GET['modelo']) : ''; ?>.</p>
                    <?php if ($alternativas): ?>
                        <p class="sin-resultados-sub">Prueba con:</p>
                        <ul class="sin-resultados-alternativas">
                            <?php foreach ($alternativas as $urlAlternativa => $textoAlternativa): ?>
                                <li><a href="<?php echo gmgH($urlAlternativa); ?>"><?php echo gmgH($textoAlternativa); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <p>O pregúntale a nuestro Asistente Virtual (te ayuda incluso con una foto), o escríbenos y lo buscamos por ti.</p>
                    <div class="sin-resultados-acciones">
                        <button type="button" class="gmg-chat-promo-btn" data-abrir-chat data-chat-pregunta="<?php echo gmgH($buscado !== '' ? 'Busco ' . $buscado . (!empty($_GET['modelo']) ? ' para mi Suzuki ' . gmgModeloCorto((string)$_GET['modelo']) : '') : ''); ?>">Consultar al Asistente</button>
                        <a class="btn-sin-resultados-wa" href="<?php echo gmgH(gmgEnlaceWhatsApp($mensajeWhatsApp)); ?>" target="_blank" rel="noopener">Preguntar por WhatsApp</a>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </main>

<?php
// Pie de página (con enlaces informativos y legales), asistente virtual,
// carrito y formulario de pago: se comparten con todas las páginas públicas.
gmgPiePublico($conexion, [
    'modelos' => ['modelosInfo' => $modelosInfo, 'modelosCortosTexto' => $modelosCortosTexto],
    'scripts' => ['js/tienda.js', 'js/model-carousel.js'],
    // Botón de WhatsApp con el modelo que está mirando el cliente (F3-6).
    'whatsapp' => $modeloCanonico !== '' ? 'Hola, busco un repuesto para mi Suzuki ' . $modeloCanonico : null,
]);
