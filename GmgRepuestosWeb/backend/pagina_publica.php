<?php
/**
 * Estructura común de las páginas públicas - GMG Repuestos
 *
 * Reúne en un solo lugar lo que se repite en todas las páginas que ve el
 * cliente: el encabezado, el pie de página (con los enlaces a las páginas
 * informativas y legales, historia E9-H1) y el chat/carrito/formulario de pago.
 *
 * Uso en una página:
 *   require_once 'backend/config.php';
 *   require_once 'backend/pagina_publica.php';
 *   gmgCabeceraPublica(['titulo' => 'Contacto', 'descripcion' => '...']);
 *   ... contenido de la página dentro de <main> ...
 *   gmgPiePublico($conexion);
 */

require_once __DIR__ . '/modelos.php';
require_once __DIR__ . '/conocimiento.php';

/** Escapa texto para mostrarlo en HTML. */
function gmgH($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

/**
 * Formulario "Avísame cuando llegue" para un repuesto sin stock (F3-11, E2-H9, RF-016).
 * Lo atiende js/tienda.js y lo guarda backend/registrar_aviso_stock.php (el mismo del chat).
 * $plegado: en las tarjetas del listado va dentro de un <details> para no ocupar espacio.
 */
function gmgFormularioAvisoStock($idProducto, $plegado = true) {
    $id = (int)$idProducto;
    $formulario = '<form class="aviso-stock" data-producto-id="' . $id . '" novalidate>'
        . '<label for="aviso-' . $id . '">Te avisamos apenas vuelva a haber stock</label>'
        . '<div class="aviso-stock-fila"><input id="aviso-' . $id . '" type="text" name="contacto" maxlength="150" required'
        . ' placeholder="Correo o celular (+56 9...)" autocomplete="email">'
        . '<button type="submit">Avisarme</button></div>'
        . '<p class="aviso-stock-msg" role="status" aria-live="polite"></p></form>';
    echo $plegado ? '<details class="aviso-stock-plegado"><summary>🔔 Avísame cuando llegue</summary>' . $formulario . '</details>' : $formulario;
}

/** Texto a "slug" para URLs: "Óptica delantera 3ª gen." -> "optica-delantera-3a-gen". */
function gmgSlug($texto) {
    $texto = mb_strtolower(trim((string)$texto), 'UTF-8');
    $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n', 'ª' => 'a', 'º' => 'o']);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $texto), '-');
}

/** ¿El servidor reescribe URLs? Lo marca el .htaccess (Apache o LiteSpeed); php -S no lo hace (F4-2). */
function gmgReescrituraActiva() {
    return !empty($_SERVER['GMG_REESCRITURA']) || !empty($_SERVER['REDIRECT_GMG_REESCRITURA']);
}

/**
 * Dirección de la ficha de un repuesto, relativa al sitio (F4-2, E8-H3, RF-068):
 * "repuestos/swift/optica-delantera-derecha-swift-3a-gen-255" si el servidor reescribe URLs,
 * o "producto.php?id=255" si no (ambiente local). $modelos: texto "Baleno, Swift" (se usa el primero).
 */
function gmgUrlProducto($id, $nombre, $modelos = '') {
    $id = (int)$id;
    if (!gmgReescrituraActiva()) {
        return 'producto.php?id=' . $id;
    }
    $modelo = gmgSlug(explode(',', (string)$modelos)[0]) ?: 'suzuki';
    return 'repuestos/' . $modelo . '/' . (gmgSlug($nombre) ?: 'repuesto') . '-' . $id;
}

/**
 * Modelos con repuestos para mostrar en la portada, el buscador, el pie de
 * página y el chat. Se arman desde la base de datos: un modelo nuevo aparece
 * solo, sin tocar código (historia E10-H12).
 *
 * Devuelve ['modelosInfo' => [...], 'modelosCortosTexto' => 'Alto, S-Presso y Swift'].
 */
function gmgDatosModelosPublicos($conexion) {
    $modelosInfo = [];
    $modelosCortosTexto = 'todos los modelos Suzuki';

    if ($conexion && !$conexion->connect_error) {
        foreach (gmgModelosConProductos($conexion) as $modeloCompleto) {
            $imagenModelo = gmgModeloImagen($modeloCompleto);
            $resumenModelo = gmgModeloResumen($modeloCompleto);
            $modelosInfo[] = [
                'completo' => $modeloCompleto,
                'corto' => gmgModeloCorto($modeloCompleto),
                'imagen' => $imagenModelo,
                'tiene_foto' => strpos($imagenModelo, 'placeholder-modelo') === false,
                'repuestos' => $resumenModelo['repuestos'],
                'anios' => $resumenModelo['anios'],
            ];
        }
        if (count($modelosInfo) > 0) {
            $modelosCortosTexto = gmgUnirConY(array_column($modelosInfo, 'corto'));
        }
    }
    return ['modelosInfo' => $modelosInfo, 'modelosCortosTexto' => $modelosCortosTexto];
}

/**
 * Imprime el inicio de la página: <head> y encabezado con el menú.
 *
 * Opciones:
 *   titulo       Título de la pestaña (se le agrega " - GMG Repuestos").
 *   titulo_completo  Título exacto, sin agregar nada (opcional).
 *   descripcion  Texto para buscadores (meta description).
 *   canonical    Dirección canónica absoluta (opcional, historia E8-H8).
 *   robots       Valor de la etiqueta robots, ej. "noindex, follow" (opcional).
 *   css          Hojas de estilo adicionales (arreglo de rutas).
 *   og_tipo      Tipo para redes sociales (por defecto "website"; la ficha usa "product").
 *   og_imagen    Dirección absoluta de la imagen para redes sociales (opcional).
 *   json_ld      Datos estructurados para buscadores (arreglo; se imprime como JSON-LD).
 *   base         true: agrega <base href> a la raíz del sitio (la ficha con URL legible, F4-2).
 */
function gmgCabeceraPublica(array $o = []) {
    $titulo = $o['titulo_completo'] ?? (($o['titulo'] ?? 'Inicio') . ' - GMG Repuestos');
    $descripcion = $o['descripcion'] ?? 'GMG Repuestos SpA: tienda en línea de repuestos para vehículos Suzuki en Chile.';
    $css = array_merge(['css/style.css', 'css/chat.css'], $o['css'] ?? []);
    ?>
<!DOCTYPE html>
<html lang="es-CL">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if (!empty($o['base'])): ?>
    <?php // Solo la ruta (sin dominio): así el CSS y el JS se piden al mismo sitio que abrió el visitante,
          // con o sin "www". Con el dominio de SITE_URL, la CSP ('self') los bloqueaba al entrar por el otro. ?>
    <base href="<?php echo gmgH(rtrim((string)parse_url(gmgUrlBase(), PHP_URL_PATH), '/') . '/'); ?>">
<?php endif; ?>
    <title><?php echo gmgH($titulo); ?></title>
    <meta name="description" content="<?php echo gmgH($descripcion); ?>">
<?php if (!empty($o['canonical'])): ?>
    <link rel="canonical" href="<?php echo gmgH($o['canonical']); ?>">
<?php endif; ?>
<?php if (!empty($o['robots'])): ?>
    <meta name="robots" content="<?php echo gmgH($o['robots']); ?>">
<?php endif; ?>
    <meta property="og:type" content="<?php echo gmgH($o['og_tipo'] ?? 'website'); ?>">
    <meta property="og:locale" content="es_CL">
    <meta property="og:site_name" content="GMG Repuestos">
    <meta property="og:title" content="<?php echo gmgH($titulo); ?>">
    <meta property="og:description" content="<?php echo gmgH($descripcion); ?>">
<?php if (!empty($o['canonical'])): ?>
    <meta property="og:url" content="<?php echo gmgH($o['canonical']); ?>">
<?php endif; ?>
<?php if (!empty($o['og_imagen'])): ?>
    <meta property="og:image" content="<?php echo gmgH($o['og_imagen']); ?>">
<?php endif; ?>
<?php if (!empty($o['json_ld'])): ?>
    <script type="application/ld+json"><?php echo json_encode($o['json_ld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
<?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap">
<?php $ga4 = (string)gmgEnv('GA4_ID', ''); if (preg_match('/^G-[A-Z0-9]{4,20}$/', $ga4)): // F4-3: solo con el ID de GMG en el .env ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo gmgH($ga4); ?>"></script>
    <script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', '<?php echo gmgH($ga4); ?>', { anonymize_ip: true });</script>
<?php endif; ?>
<?php foreach ($css as $hoja): ?>
    <link rel="stylesheet" href="<?php echo gmgH($hoja); ?>?v=<?php echo gmgVersionArchivo($hoja); ?>">
<?php endforeach; ?>
</head>
<body>

    <header>
        <div class="container nav-container">
            <a href="index.php" class="logo">GMG <span>Repuestos</span></a>
            <nav aria-label="Menú principal">
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="mi-pedido.php">Mi pedido</a></li>
                    <li>
                        <a href="#" id="cart-toggle" class="cart-toggle" aria-label="Abrir carrito">
                            🛒 <span id="cart-count" class="cart-count">0</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
<?php
    // Banners del panel (F4-10, E6-H11): activos y vigentes, máximo 2, el más nuevo primero.
    global $conexion;
    $banners = [];
    if (isset($conexion) && $conexion instanceof mysqli && !$conexion->connect_error) {
        $r = @$conexion->query("SELECT mensaje, enlace FROM banners WHERE activo = 1 AND (vigente_hasta IS NULL OR vigente_hasta >= CURDATE()) ORDER BY creado_en DESC LIMIT 2");
        $banners = $r ? $r->fetch_all(MYSQLI_ASSOC) : []; // sin la tabla (antes de BD_v2/09) no se muestra nada
    }
    foreach ($banners as $banner) {
        $texto = gmgH($banner['mensaje']);
        echo '    <div class="banner-tienda" role="note">' . ($banner['enlace'] ? '<a href="' . gmgH($banner['enlace']) . '">' . $texto . ' →</a>' : $texto) . "</div>
";
    }
}

/**
 * Imprime el final de la página: pie de página con enlaces informativos y
 * legales, asistente virtual, carrito y formulario de pago, y los scripts.
 *
 * $conexion        Conexión a la base de datos (para modelos y datos de contacto).
 * $o['scripts']    Scripts adicionales de la página (arreglo de rutas).
 * $o['modelos']    Resultado de gmgDatosModelosPublicos() si la página ya lo calculó.
 * $o['whatsapp']   Mensaje inicial del botón flotante de WhatsApp (F3-6), según la página.
 */
function gmgPiePublico($conexion, array $o = []) {
    $datos = $o['modelos'] ?? gmgDatosModelosPublicos($conexion);
    $modelosInfo = $datos['modelosInfo'];
    $modelosCortosTexto = $datos['modelosCortosTexto'];

    $contacto = gmgConocimientoTodo($conexion);
    $porConfirmar = 'Por confirmar';
    $paginas = [
        'quienes-somos.php'            => 'Quiénes somos',
        'contacto.php'                 => 'Contacto',
        'despacho.php'                 => 'Despacho',
        'cambios-y-devoluciones.php'   => 'Cambios y devoluciones',
        'terminos-y-condiciones.php'   => 'Términos y condiciones',
        'politica-de-privacidad.php'   => 'Política de privacidad',
    ];
    ?>
    <footer>
        <div class="container footer-grid">
            <div class="footer-col footer-brand">
                <div class="footer-logo">GMG <span>Repuestos</span></div>
                <p class="footer-desc">Especialistas en repuestos originales y compatibles para <?php echo gmgH($modelosCortosTexto); ?>. Piezas de calidad, con garantía, coordinando cada compra directo por WhatsApp.</p>
            </div>

            <div class="footer-col">
                <h4 class="footer-heading">Modelos</h4>
                <ul class="footer-links">
                    <?php foreach ($modelosInfo as $mi): ?>
                        <li><a href="index.php?modelo=<?php echo urlencode($mi['corto']); ?>"><?php echo gmgH($mi['completo']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="footer-col">
                <h4 class="footer-heading">Información</h4>
                <ul class="footer-links">
                    <?php foreach ($paginas as $archivo => $nombre): ?>
                        <li><a href="<?php echo gmgH($archivo); ?>"><?php echo gmgH($nombre); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="footer-col">
                <h4 class="footer-heading">Contacto</h4>
                <ul class="footer-contact">
                    <li>📍 <?php echo gmgH($contacto['direccion'] !== '' ? $contacto['direccion'] : $porConfirmar); ?></li>
                    <li>📞 <?php echo gmgH($contacto['telefono'] !== '' ? $contacto['telefono'] : $porConfirmar); ?></li>
                    <li>✉️ <?php echo gmgH($contacto['email'] !== '' ? $contacto['email'] : $porConfirmar); ?></li>
                    <li>🕒 <?php echo gmgH($contacto['horario'] !== '' ? $contacto['horario'] : $porConfirmar); ?></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container footer-bottom-inner">
                <p>&copy; <?php echo date('Y'); ?> GMG Repuestos SpA. Especialistas en repuestos Suzuki en Chile.</p>
                <a href="login.php" class="footer-admin-link">Acceso administrador</a>
            </div>
        </div>
    </footer>

<?php
    $whatsappMensaje = $o['whatsapp'] ?? null;
    $bienvenidaChat = $contacto['bienvenida'] ?? ''; // F3-8: editable en el panel "Asistente"
    require __DIR__ . '/parciales/widgets_tienda.php';

    $scripts = array_merge(['js/analitica.js', 'js/cart.js', 'js/chat.js'], $o['scripts'] ?? []);
    foreach ($scripts as $script) {
        echo '    <script src="' . gmgH($script) . '?v=' . gmgVersionArchivo($script) . '"></script>' . "\n";
    }
    echo "</body>\n</html>\n";
}
