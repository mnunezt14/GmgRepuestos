<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'bot_config.php';
require_once 'config.php';
require_once 'datos.php';
require_once 'chat_helpers.php';
require_once 'conocimiento.php';

// 0. Límites anti-abuso y de costo (INC-02, E10-H5): por sesión y por IP (guardada
//    como hash en limites_solicitudes). Con otra sesión no se salta el límite por IP.
if (!chatVerificarLimite() || gmgLimiteSuperado($conexion, 'chat', CHAT_RATE_LIMIT_MAX, CHAT_RATE_LIMIT_WINDOW)) {
    echo json_encode([
        'status' => 'error',
        'reply' => 'Estás enviando mensajes muy rápido 🙂. Espera unos segundos y vuelve a intentar.'
    ]);
    exit;
}

// 1. Ya no se carga el catálogo completo en cada mensaje (F1-5): la IA consulta
//    solo lo que necesita con la herramienta buscar_productos.

// 2. Extraer mensaje, imágenes adjuntas y sesión. El historial NO se recibe del
//    navegador (se podía inventar un turno falso del bot): se lee de la base (paso 3).
$mensaje = '';
$imagenes = []; // cada una: ['base64' => ..., 'mime' => ...]
$sesionId = '';
$atajo = ''; // botón rápido del chat: buscar, despacho o pagos

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Solo texto: un campo enviado como arreglo (message[]=...) se ignora en vez de romper la página.
    if (isset($_POST['message']) && is_string($_POST['message'])) {
        $mensaje = trim($_POST['message']);
    }
    if (isset($_POST['sesion_id']) && is_string($_POST['sesion_id'])) {
        $sesionId = trim($_POST['sesion_id']);
    }
    if (isset($_POST['atajo']) && is_string($_POST['atajo'])) {
        $atajo = trim($_POST['atajo']);
    }

    // Fotos: solo el campo images[] y con sus validaciones (los campos antiguos
    // "image" e "image_base64" ya no se aceptan: no pasaban ningún control).
    $avisoFotos = null;
    if (isset($_FILES['images'])) {
        [$imagenes, $avisoFotos] = chatLeerImagenes($_FILES['images']);
    } elseif (empty($_POST) && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') === 0) {
        // El envío superó el tamaño que acepta el servidor (post_max_size) y llegó vacío.
        $avisoFotos = 'Puedes enviar hasta 3 fotos JPG, PNG o WebP de 4 MB cada una.';
    }
    if ($avisoFotos !== null) {
        echo json_encode(['status' => 'error', 'reply' => $avisoFotos], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($mensaje) && empty($imagenes)) {
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $jsonData = json_decode($rawInput, true);
            if (isset($jsonData['message']) && is_string($jsonData['message'])) {
                $mensaje = trim($jsonData['message']);
            }
            if (isset($jsonData['sesion_id']) && is_string($jsonData['sesion_id'])) {
                $sesionId = trim($jsonData['sesion_id']);
            }
        }
    }
}

// Un mensaje muy largo solo encarece la consulta a la IA.
if (mb_strlen($mensaje) > 1000) {
    echo json_encode(['status' => 'error', 'reply' => 'Tu mensaje es muy largo. Escríbelo en menos de 1.000 caracteres, por favor.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($mensaje) && empty($imagenes)) {
    echo json_encode([
        'status' => 'error',
        'reply' => 'Por favor escribe un mensaje o sube una imagen de la pieza que buscas.'
    ]);
    exit;
}

// 3. Conversación (para el panel de analítica en Admin)
$conversacionId = (isset($conexion) && !$conexion->connect_error && !empty($sesionId))
    ? chatObtenerOCrearConversacion($conexion, $sesionId)
    : null;

// Historial para la IA (E5-H8, E5-H15, INC-05): se lee de la base ANTES de guardar
// el mensaje nuevo (así no va duplicado) y trae los productos que mostró cada respuesta.
$historial = $conversacionId ? chatHistorialDesdeBD($conexion, $conversacionId) : [];

if ($conversacionId) {
    chatGuardarMensaje($conexion, $conversacionId, 'user', $mensaje !== '' ? $mensaje : '(foto adjunta)');
}

// 3.1 Botones rápidos del chat (E5-H1): se responden de inmediato con la
//     información configurada, sin pasar por la IA.
if ($atajo !== '' && empty($imagenes)) {
    $respuestaAtajo = gmgRespuestaAtajo($conexion, $atajo);
    if ($respuestaAtajo !== null) {
        $mensajeIdAtajo = $conversacionId ? chatGuardarMensaje($conexion, $conversacionId, 'bot', $respuestaAtajo) : null;
        echo json_encode([
            'status' => 'success',
            'reply' => $respuestaAtajo,
            'products' => [],
            'auto_add_cart' => [],
            'start_checkout' => false,
            'notificar_stock_producto_id' => null,
            'mensaje_id' => $mensajeIdAtajo,
            'model_used' => 'respuesta_configurada'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 4. Procesamiento con la IA
$respuestaTexto = '';
$repuestosSugeridos = [];
$autoAddCart = [];
$startCheckout = false;
$notificarStockProductoId = null;
$errorIA = false;       // true si la IA no pudo responder (el chat no ofrece 👍/👎 sobre ese error)
$sufijoDebugIA = '';    // detalle técnico del error: solo se muestra a administradores o con CHAT_DEBUG

if (!empty(AI_API_KEY)) {
    // Presupuesto de 25 s para toda la respuesta (RNF-012): 1 reintento y máximo 2 rondas de herramientas.
    $respuestaAPI = chatConsultarIA($mensaje, $imagenes, $historial, $conexion, microtime(true) + 25);

    if ($respuestaAPI['success']) {
        $respuestaTexto = $respuestaAPI['reply'];
        // Las tarjetas de producto son solo los resultados reales de buscar_productos.
        $repuestosSugeridos = $respuestaAPI['productos_encontrados'] ?? [];
        // Botón "Ver ficha" en cada tarjeta, apenas exista la página del producto (F2-4).
        require_once __DIR__ . '/pagina_publica.php';
        foreach ($repuestosSugeridos as &$repuesto) {
            $repuesto['url_ficha'] = gmgUrlProducto($repuesto['id'], $repuesto['nombre'], $repuesto['modelo'] ?? ''); // F4-2
        }
        unset($repuesto);

        // [ADD_TO_CART] solo con productos que el bot mostró en esta conversación
        // o en esta misma respuesta (E5-H6): un id inventado no agrega nada.
        $productosPermitidos = array_merge(
            $repuestosSugeridos,
            $conversacionId ? chatProductosPorIds($conexion, chatIdsMostrados($conexion, $conversacionId)) : []
        );
        $autoAddCart = chatExtraerAddToCart($respuestaTexto, $productosPermitidos);
        $startCheckout = chatExtraerStartCheckout($respuestaTexto);
        $notificarStockProductoId = chatExtraerNotificarStock($respuestaTexto);
        $respuestaTexto = chatLimpiarRespuesta($respuestaTexto);

        if (defined('CHAT_DEBUG') && CHAT_DEBUG) {
            $modo = $respuestaAPI['debug_modo'] ?? 'desconocido';
            $detalleDebug = "modo=$modo";
            if (!empty($respuestaAPI['debug_busquedas'])) {
                foreach ($respuestaAPI['debug_busquedas'] as $b) {
                    $detalleDebug .= ' | buscar_productos(' . json_encode($b['args'], JSON_UNESCAPED_UNICODE) . ') -> ' . $b['num_resultados'] . ' resultado(s)';
                }
                $detalleDebug .= ' | SQL: ' . chatUltimaConsultaSQL();
            } elseif ($modo === 'tools') {
                $detalleDebug .= ' | la IA no llamó a buscar_productos';
            }
            $respuestaTexto .= "\n\n[DEBUG] " . $detalleDebug;
        }
    } else {
        $detalleError = $respuestaAPI['error'] ?? 'desconocido';
        error_log('[GMG Chat] Error IA: ' . $detalleError);
        $errorIA = true;
        $respuestaTexto = 'Lo siento, estoy teniendo problemas de conexión en este momento. Intenta de nuevo en un momento o [escríbenos por WhatsApp](' . gmgEnlaceWhatsApp('Hola, necesito ayuda con un repuesto') . ') si es urgente.';
        // El detalle técnico (error real de la API) se muestra solo si hay una
        // sesión de administrador abierta en este navegador, o con CHAT_DEBUG
        // en true. Los clientes nunca lo ven y tampoco se guarda en la base.
        if ((defined('CHAT_DEBUG') && CHAT_DEBUG) || !empty($_SESSION['admin_id'])) {
            $sufijoDebugIA = "\n\n[DEBUG] " . $detalleError;
        }
    }
} else {
    // Sin la clave de la IA el asistente no puede conversar; en vez de mostrar
    // un mensaje técnico, se le ofrece al cliente seguir por WhatsApp.
    error_log('[GMG Chat] AI_API_KEY no está configurada en el archivo .env');
    $respuestaTexto = 'Por ahora no puedo responder por aquí. Escríbenos por WhatsApp y te ayudamos enseguida: ' . gmgEnlaceWhatsApp('Hola, necesito ayuda con un repuesto');
}

// 5. Si el producto sugerido con stock 0 no trajo la etiqueta de aviso pero
//    el cliente aceptó implícitamente, no forzamos nada: solo se activa si
//    la IA agregó [NOTIFICAR_STOCK:ID] explícitamente (ver bot_config.php).

// Solo se guardan las respuestas reales de la IA: un mensaje de error o de "sin
// clave" no entra al historial (si entrara, la IA lo leería como algo que dijo).
$mensajeId = null;
if ($conversacionId && !$errorIA && !empty(AI_API_KEY)) {
    $mensajeId = chatGuardarMensaje($conexion, $conversacionId, 'bot', $respuestaTexto, $repuestosSugeridos);
}

// 6. Retornar respuesta estructurada
echo json_encode([
    'status' => $errorIA ? 'error' : 'success',
    'reply' => $respuestaTexto . $sufijoDebugIA,
    'products' => $repuestosSugeridos,
    'auto_add_cart' => $autoAddCart,
    'start_checkout' => $startCheckout,
    'notificar_stock_producto_id' => $notificarStockProductoId,
    'mensaje_id' => $errorIA ? null : $mensajeId,
    'model_used' => AI_MODEL
], JSON_UNESCAPED_UNICODE);
exit;
?>
