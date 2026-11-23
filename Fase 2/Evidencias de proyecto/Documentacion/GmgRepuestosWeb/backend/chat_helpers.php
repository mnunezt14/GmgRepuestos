<?php
/**
 * Funciones compartidas del Asistente Virtual IA - GMG Repuestos
 */

// Búsqueda por palabras, sinónimos y relevancia; historial desde la base (F1-2).
require_once __DIR__ . '/chat_busqueda.php';

// =========================================================
// 1) Límite de mensajes por sesión (anti-abuso / control de costo)
// =========================================================
function chatVerificarLimite() {
    gmgIniciarSesion(); // cookie protegida (F3-1)
    $ahora = time();
    if (!isset($_SESSION['gmg_chat_timestamps']) || !is_array($_SESSION['gmg_chat_timestamps'])) {
        $_SESSION['gmg_chat_timestamps'] = [];
    }
    // Solo dejar los timestamps dentro de la ventana de tiempo
    $_SESSION['gmg_chat_timestamps'] = array_values(array_filter(
        $_SESSION['gmg_chat_timestamps'],
        function ($t) use ($ahora) { return ($ahora - $t) < CHAT_RATE_LIMIT_WINDOW; }
    ));

    if (count($_SESSION['gmg_chat_timestamps']) >= CHAT_RATE_LIMIT_MAX) {
        return false;
    }
    $_SESSION['gmg_chat_timestamps'][] = $ahora;
    return true;
}

/**
 * Fotos adjuntas al mensaje (E5-H13, RNF-036, INC-02): máximo 3, de 4 MB cada
 * una, y solo JPG, PNG o WebP según su contenido real (finfo), no según el
 * nombre ni lo que declare el navegador. Si alguna no cumple se rechaza todo.
 * $archivos es $_FILES['images']. Devuelve [imagenes, aviso o null]; cada
 * imagen: ['base64' => ..., 'mime' => ...].
 */
function chatLeerImagenes(array $archivos): array {
    $aviso = 'Puedes enviar hasta 3 fotos JPG, PNG o WebP de 4 MB cada una.';
    $rutas = (array)($archivos['tmp_name'] ?? []);
    $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
    $maximo = 4 * 1024 * 1024;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $imagenes = [];
    foreach ($rutas as $i => $ruta) {
        $error = (int)(((array)($archivos['error'] ?? []))[$i] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($ruta) || filesize($ruta) > $maximo) {
            return [[], $aviso];
        }
        $mime = $finfo->file($ruta);
        if (!in_array($mime, $permitidos, true)) {
            return [[], $aviso];
        }
        $imagenes[] = ['base64' => base64_encode(file_get_contents($ruta)), 'mime' => $mime];
    }
    if (count($imagenes) > 3) {
        return [[], $aviso];
    }
    return [$imagenes, null];
}

// =========================================================
// 2) Registro de conversaciones y mensajes (para el panel de
//    analítica en Admin)
// =========================================================
function chatObtenerOCrearConversacion($conexion, $sesionId) {
    $sesionId = substr(trim($sesionId), 0, 64);
    if ($sesionId === '') {
        return null;
    }
    $stmt = $conexion->prepare("SELECT id_conversacion AS id FROM conversaciones_chat WHERE sesion_id = ?");
    $stmt->bind_param("s", $sesionId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $id = $res->fetch_assoc()['id'];
        $stmt->close();
        $upd = $conexion->prepare("UPDATE conversaciones_chat SET ultimo_mensaje_en = NOW() WHERE id_conversacion = ?");
        $upd->bind_param("i", $id);
        $upd->execute();
        $upd->close();
        return (int)$id;
    }
    $stmt->close();

    $ins = $conexion->prepare("INSERT INTO conversaciones_chat (sesion_id) VALUES (?)");
    $ins->bind_param("s", $sesionId);
    if ($ins->execute()) {
        $id = $conexion->insert_id;
        $ins->close();
        return (int)$id;
    }
    $ins->close();
    return null;
}

function chatGuardarMensaje($conexion, $conversacionId, $rol, $contenido, $productos = []) {
    if (!$conversacionId) {
        return null;
    }
    $stmt = $conexion->prepare("INSERT INTO mensajes_chat (id_conversacion, rol, contenido) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $conversacionId, $rol, $contenido);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $id = (int)$conexion->insert_id;
    $stmt->close();

    // Base v2: los repuestos que el bot mostró se guardan en la tabla
    // intermedia mensaje_repuestos (antes era un JSON en productos_json).
    if (!empty($productos)) {
        $insProd = $conexion->prepare("INSERT INTO mensaje_repuestos (id_mensaje, id_repuesto, orden) VALUES (?, ?, ?)");
        $orden = 1;
        $yaGuardados = [];
        foreach ($productos as $p) {
            $idRepuesto = (int)($p['id'] ?? 0);
            if ($idRepuesto <= 0 || isset($yaGuardados[$idRepuesto]) || $orden > 255) {
                continue;
            }
            $insProd->bind_param("iii", $id, $idRepuesto, $orden);
            $insProd->execute();
            $yaGuardados[$idRepuesto] = true;
            $orden++;
        }
        $insProd->close();
    }
    return $id;
}

// =========================================================
// 3) Herramienta "buscar_productos": lo que la IA puede
//    consultar en vivo contra la base de datos real.
// =========================================================
function chatDefinicionHerramientas() {
    return [
        [
            'type' => 'function',
            'name' => 'buscar_productos',
            'description' => 'Busca repuestos reales en el catálogo de GMG Repuestos por modelo y año del vehículo, categoría, SKU/código OEM, texto libre o precio máximo. IMPORTANTE: salvo que se busque por SKU o código OEM, es obligatorio enviar el modelo y el año del vehículo del cliente (si no los sabes, pregúntaselos antes de llamar a esta herramienta). Devuelve hasta 8 resultados ordenados por relevancia, con id, nombre, modelo, año, motor, compatible_con (lista exacta de vehículos: modelo, motor y años), categoría, sku, codigo_oem, precio, stock y disponibilidad ("Disponible", "Últimas unidades" o "Sin stock"). Si falta un dato o el modelo no existe en el catálogo, devuelve un "aviso" con lo que debes hacer.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'modelo' => ['type' => 'string', 'description' => 'Ej: Swift, Alto, S-Presso'],
                    'anio' => ['type' => 'integer', 'description' => 'Año del vehículo del cliente. Ej: 2018'],
                    'anio_desconocido' => ['type' => 'boolean', 'description' => 'Enviar true SOLO si el cliente dijo claramente que no sabe el año de su vehículo'],
                    'categoria' => ['type' => 'string', 'description' => 'Ej: Frenos, Filtros, Suspensión, Motor'],
                    'sku_o_codigo_oem' => ['type' => 'string', 'description' => 'SKU exacto o parcial, o código OEM'],
                    'texto' => ['type' => 'string', 'description' => 'Solo la pieza que busca el cliente, sin modelo ni año. Ej: pastillas de freno delanteras'],
                    'precio_maximo' => ['type' => 'number', 'description' => 'Precio máximo en CLP'],
                ],
                'required' => [],
            ],
        ],
        [
            'type' => 'function',
            'name' => 'consultar_pedido',
            'description' => 'Consulta el estado de un pedido de la tienda (F3-12). Pide SIEMPRE el número de pedido y el correo o RUT con que se hizo la compra. Devuelve estado, pago, fecha, productos, total y el siguiente paso, o un "aviso" si los datos no coinciden.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'numero_pedido' => ['type' => 'integer', 'description' => 'Número del pedido. Ej: 125'],
                    'correo_o_rut' => ['type' => 'string', 'description' => 'Correo o RUT con que se hizo la compra. Ej: 12.345.678-5'],
                ],
                'required' => ['numero_pedido', 'correo_o_rut'],
            ],
        ],
    ];
}

/**
 * Estado de un pedido para el chat (F3-12, E5-H12, RF-047). Solo responde si el número coincide
 * con el correo o el RUT del cliente; si no, el mismo aviso siempre (no revela qué pedidos existen).
 * Máximo 5 consultas por IP cada 10 minutos.
 */
function chatConsultarPedido($conexion, array $args) {
    $noEncontrado = ['aviso' => 'No encontramos un pedido con ese número y ese correo o RUT. Pídele al cliente que revise los datos o que escriba por WhatsApp.'];
    if (gmgLimiteSuperado($conexion, 'chat_pedido', 5, 600)) {
        return ['aviso' => 'Se hicieron muchas consultas de pedidos seguidas. Pídele al cliente que espere unos minutos o que escriba por WhatsApp.'];
    }
    $numero = (int)($args['numero_pedido'] ?? 0);
    $dato = trim((string)($args['correo_o_rut'] ?? ''));
    if ($numero <= 0 || $dato === '') {
        return ['aviso' => 'Falta el número de pedido o el correo o RUT con que se compró. Pídeselo al cliente.'];
    }
    $stmt = $conexion->prepare("SELECT * FROM " . gmgSqlPedidos() . " p WHERE p.id = ? LIMIT 1");
    $stmt->bind_param('i', $numero);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $coincide = $pedido && (
        (strpos($dato, '@') !== false && mb_strtolower($dato) === mb_strtolower((string)$pedido['email_cliente']))
        || (gmgNormalizarRut($dato) !== '' && gmgNormalizarRut($dato) === gmgNormalizarRut((string)$pedido['rut_cliente'])));
    if (!$coincide) {
        return $noEncontrado;
    }
    $stmt = $conexion->prepare("SELECT nombre_producto, cantidad FROM " . gmgSqlPedidoItems() . " i WHERE i.pedido_id = ?");
    $stmt->bind_param('i', $numero);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $pasos = [
        'pendiente' => 'Estamos revisando el pago. Cuando se apruebe, te escribimos por WhatsApp para coordinar la entrega.',
        'aprobado'  => 'Pedido aprobado: coordinamos la entrega o el retiro por WhatsApp.',
        'rechazado' => 'El pedido fue rechazado. Si crees que es un error, escríbenos por WhatsApp.',
        'error'     => 'Hubo un problema con el pago. Escríbenos por WhatsApp para ayudarte.',
    ];
    return [
        'pedido' => $numero,
        'estado' => $pedido['estado'],
        'pago' => $pedido['pago_estado'] ?: 'pendiente',
        'fecha' => date('d-m-Y', strtotime($pedido['fecha'])),
        'total_clp' => (int)$pedido['total'],
        'productos' => array_map(function ($i) { return $i['cantidad'] . ' x ' . $i['nombre_producto']; }, $items),
        'siguiente_paso' => $pasos[$pedido['estado']] ?? 'Escríbenos por WhatsApp para más detalles.',
    ];
}

/**
 * Guarda/recupera el último SQL ejecutado por buscar_productos, solo para
 * poder mostrarlo en el modo debug (CHAT_DEBUG en bot_config.php).
 */
function chatUltimaConsultaSQL($nuevoValor = null) {
    static $ultimo = '';
    if ($nuevoValor !== null) {
        $ultimo = $nuevoValor;
    }
    return $ultimo;
}

/**
 * Historia E5-H4: el asistente debe conocer el modelo y el año del vehículo
 * antes de recomendar un repuesto. Esta revisión se hace aquí, en el servidor,
 * para que no dependa solo de que la IA "se acuerde" de preguntar.
 *
 * Devuelve null si la búsqueda puede hacerse, o un mensaje (para la IA)
 * pidiéndole que primero pregunte los datos que faltan, o avisándole que el
 * modelo no está en el catálogo (con la lista de modelos que sí hay).
 */
function chatValidarBusqueda($args, $conexion = null) {
    // Con un SKU o código OEM no hace falta saber el vehículo.
    if (!empty(trim((string)($args['sku_o_codigo_oem'] ?? '')))) {
        return null;
    }
    $modelo = trim((string)($args['modelo'] ?? ''));
    $anio = (int)($args['anio'] ?? 0);
    $anioValido = $anio >= 1970 && $anio <= 2100;
    $anioDesconocido = !empty($args['anio_desconocido']);

    $faltan = [];
    if ($modelo === '') $faltan[] = 'el MODELO';
    if (!$anioValido && !$anioDesconocido) $faltan[] = 'el AÑO';
    if (count($faltan) > 0) {
        return 'BÚSQUEDA NO REALIZADA: falta ' . implode(' y ', $faltan) . ' del vehículo del cliente. '
             . 'Pregúntaselo ahora (una sola pregunta, breve y amable) y no recomiendes ningún producto hasta tenerlo.';
    }

    // El modelo debe ser uno de los que tienen repuestos (Vitara y Grand Vitara son distintos).
    if ($conexion) {
        $modelos = chatModelosCortos($conexion);
        if ($modelos && !in_array(chatModeloClave($modelo), array_map('chatModeloClave', $modelos), true)) {
            return 'BÚSQUEDA NO REALIZADA: no hay repuestos cargados para el modelo "' . mb_substr($modelo, 0, 40) . '". '
                 . 'Modelos con repuestos: ' . implode(', ', $modelos) . '. '
                 . 'Si el modelo del cliente es una versión de uno de estos (por ejemplo "Swift Sport" es Swift), vuelve a buscar con ese nombre. '
                 . 'Si no, dile que por ahora no tenemos repuestos para su modelo y ofrécele WhatsApp.';
        }
    }
    return null;
}

/**
 * Herramienta buscar_productos (E5-H3, INC-06): primero exige todas las
 * palabras y, si no hay resultados, basta una; ordena por relevancia y stock.
 * Todo con sentencias preparadas (chatConsultaBusqueda en chat_busqueda.php).
 * Devuelve el detalle de cada repuesto con su disponibilidad (RF-013).
 */
function chatEjecutarBusquedaProductos($conexion, $args) {
    $ids = [];
    foreach ([true, false] as $estricto) {
        [$sql, $tipos, $params] = chatConsultaBusqueda($args, 8, $estricto);
        // Guardada solo para el modo debug (CHAT_DEBUG o sesión de administrador).
        chatUltimaConsultaSQL($sql);
        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            error_log('[GMG Chat] No se pudo preparar la búsqueda: ' . $conexion->error);
            return [];
        }
        if ($tipos !== '') {
            $stmt->bind_param($tipos, ...$params);
        }
        $stmt->execute();
        $ids = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id');
        $stmt->close();
        if ($ids) {
            break;
        }
    }
    if (!$ids) {
        return [];
    }

    $lista = implode(',', array_map('intval', $ids));
    $r = $conexion->query("SELECT id, nombre, modelo, ano, motor, compatible_con, categoria, sku, codigo_oem,
                                  precio, stock, imagen_url
                             FROM " . gmgSqlProductos() . " p WHERE p.id IN ($lista)
                            ORDER BY FIELD(p.id, $lista)");
    $filas = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    foreach ($filas as &$f) {
        $f['disponibilidad'] = chatDisponibilidad((int)$f['stock']);
    }
    unset($f);
    return $filas;
}

// =========================================================
// 4) Extracción de etiquetas de acción en la respuesta del bot
// =========================================================
function chatExtraerAddToCart(&$texto, $productos) {
    $autoAddCart = [];
    if (preg_match_all('/\[ADD_TO_CART:\s*(\d+)\s*:\s*(\d+)\s*\]/i', $texto, $matches)) {
        for ($i = 0; $i < count($matches[0]); $i++) {
            $itemId = (int)$matches[1][$i];
            $cantidad = (int)$matches[2][$i];
            foreach ($productos as $p) {
                if ($p['id'] == $itemId) {
                    $autoAddCart[] = [
                        'id' => $itemId,
                        'quantity' => $cantidad,
                        'nombre' => $p['nombre'],
                        'precio' => $p['precio'],
                        'imagen_url' => $p['imagen_url'],
                        'stock' => isset($p['stock']) ? (int)$p['stock'] : null, // el carrito no deja superarlo (F2-2)
                    ];
                    break;
                }
            }
        }
        $texto = trim(preg_replace('/\[ADD_TO_CART:\s*\d+\s*:\s*\d+\s*\]/i', '', $texto));
    }
    return $autoAddCart;
}

/**
 * Limpia la respuesta de la IA antes de mostrarla y guardarla (hallazgo de F1-9):
 * - quita la línea interna "[Productos mostrados en esta respuesta: ...]", que la IA
 *   a veces imita del historial (el cliente nunca debe verla; el historial la agrega solo);
 * - cambia los guiones largos por comas (estilo del sitio, CLAUDE.md).
 */
function chatLimpiarRespuesta(string $texto): string {
    $texto = preg_replace('/[ \t]*\[Productos mostrados en esta respuesta:[^\]]*\]/u', '', $texto);
    $texto = preg_replace('/(?<=\S)[ \t]*—[ \t]*(?=\S)/u', ', ', $texto); // solo dentro de una línea
    $texto = str_replace('—', '-', $texto);
    return trim(preg_replace("/[ \t]+\n/u", "\n", $texto));
}

function chatExtraerStartCheckout(&$texto) {
    $encontrado = (bool)preg_match('/\[START_CHECKOUT\]/i', $texto);
    if ($encontrado) {
        $texto = trim(preg_replace('/\[START_CHECKOUT\]/i', '', $texto));
    }
    return $encontrado;
}

function chatExtraerNotificarStock(&$texto) {
    $productoId = null;
    if (preg_match('/\[NOTIFICAR_STOCK:\s*(\d+)\s*\]/i', $texto, $m)) {
        $productoId = (int)$m[1];
        $texto = trim(preg_replace('/\[NOTIFICAR_STOCK:\s*\d+\s*\]/i', '', $texto));
    }
    return $productoId;
}

// =========================================================
// 5) Orquestación de la llamada a la IA (Responses API con la herramienta
//    buscar_productos). Sin respaldo con el catálogo completo: si la IA no
//    responde a tiempo, el cliente recibe la salida a WhatsApp (F1-5, INC-01).
// =========================================================

/**
 * Consulta a la IA con presupuesto de tiempo (RNF-012): $limiteHasta es
 * microtime(true) + 25 al empezar el mensaje. La entrada es el prompt con la
 * base de conocimiento, el historial leído de la base y el mensaje actual.
 * Devuelve ['success' => true, 'reply', 'productos_encontrados', 'debug_busquedas', 'debug_modo']
 * o ['success' => false, 'error', 'http_code'].
 */
function chatConsultarIA($mensaje, $imagenes, $historial, $conexion, $limiteHasta) {
    // Al prompt base se le suman la base de conocimiento, la regla de preguntar
    // modelo y año y la lista actual de modelos (ver backend/conocimiento.php).
    $contextoNegocio = gmgContextoNegocioParaIA($conexion, chatModelosCortos($conexion));
    $entrada = chatArmarMensajesBase(AI_SYSTEM_PROMPT . $contextoNegocio, $historial, $mensaje, $imagenes);
    $herramientas = chatDefinicionHerramientas();

    $resultado = chatLlamarResponsesAPI($entrada, $herramientas, (float)$limiteHasta);
    if (!$resultado['success']) {
        return $resultado;
    }
    return chatResolverToolCalls($resultado, $entrada, $herramientas, $conexion, (float)$limiteHasta);
}

/**
 * Nombres cortos de los modelos que hoy tienen repuestos activos en el
 * catálogo (ej: ['Alto', 'S-Presso', 'Swift']). Se arma desde la base de
 * datos, así un modelo nuevo aparece en el chatbot sin cambiar código.
 */
function chatModelosCortos($conexion) {
    $lista = [];
    if (!$conexion) {
        return $lista;
    }
    $r = $conexion->query("SELECT DISTINCT mo.nombre, mo.orden
                             FROM modelos mo
                             JOIN vehiculos v ON v.id_modelo = mo.id_modelo
                             JOIN compatibilidades co ON co.id_vehiculo = v.id_vehiculo
                             JOIN repuestos r ON r.id_repuesto = co.id_repuesto
                            WHERE mo.activo = 1 AND r.activo = 1
                            ORDER BY mo.orden, mo.nombre");
    if ($r) {
        while ($f = $r->fetch_assoc()) {
            $lista[] = $f['nombre'];
        }
    }
    return $lista;
}

function chatArmarMensajesBase($systemPrompt, $historial, $mensaje, $imagenes) {
    $mensajes = [
        ['role' => 'system', 'content' => $systemPrompt],
    ];

    // Historial leído de la base con chatHistorialDesdeBD(): ya viene en orden,
    // recortado y con los productos que mostró cada respuesta. Solo texto.
    foreach ($historial as $turno) {
        if (in_array($turno['role'] ?? '', ['user', 'assistant'], true) && is_string($turno['content'] ?? null) && $turno['content'] !== '') {
            $mensajes[] = ['role' => $turno['role'], 'content' => $turno['content']];
        }
    }

    // Mensaje actual (con imágenes si las hay)
    if (!empty($imagenes)) {
        $contenido = [];
        $contenido[] = ['type' => 'input_text', 'text' => $mensaje !== '' ? $mensaje : 'Por favor identifica qué repuesto es el de esta foto y si lo tienen disponible en la tienda para Suzuki.'];
        foreach ($imagenes as $img) {
            $contenido[] = ['type' => 'input_image', 'image_url' => "data:{$img['mime']};base64,{$img['base64']}"];
        }
        $mensajes[] = ['role' => 'user', 'content' => $contenido];
    } else {
        $mensajes[] = ['role' => 'user', 'content' => $mensaje];
    }

    return $mensajes;
}

/**
 * Llamada a la Responses API con presupuesto de tiempo y 1 reintento
 * (RNF-012). $limiteHasta = microtime(true) + 25 al empezar el mensaje.
 * Sin reasoning.summary ni reasoning.mode: el código nunca los lee.
 */
function chatLlamarResponsesAPI(array $entrada, ?array $herramientas, float $limiteHasta): array {
    $payload = [
        'model' => AI_MODEL,
        'input' => $entrada,
        'reasoning' => ['effort' => 'low'],
        'text' => ['format' => ['type' => 'text'], 'verbosity' => 'low'],
        'max_output_tokens' => 1500,
    ];
    if ($herramientas) {
        $payload['tools'] = $herramientas;
        $payload['tool_choice'] = 'auto';
    }
    $cuerpo = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $resultado = ['success' => false, 'error' => 'Sin tiempo disponible', 'http_code' => 0];

    for ($intento = 1; $intento <= 2; $intento++) {
        $restante = (int)floor($limiteHasta - microtime(true));
        if ($restante < 3) {
            return $resultado;
        }
        $ch = curl_init(AI_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $cuerpo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => min(20, $restante),
            CURLOPT_SSL_VERIFYPEER => gmgCurlVerificarSsl(),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . AI_API_KEY],
        ]);
        $respuesta = curl_exec($ch);
        $errorCurl = curl_error($ch);
        $codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($errorCurl === '' && $codigo >= 200 && $codigo < 300) {
            $datos = json_decode((string)$respuesta, true);
            return is_array($datos)
                ? ['success' => true, 'data' => $datos]
                : ['success' => false, 'error' => 'Respuesta no válida de la API', 'http_code' => $codigo];
        }
        $detalle = json_decode((string)$respuesta, true)['error']['message'] ?? '';
        $resultado = ['success' => false, 'error' => trim(($errorCurl ?: "HTTP $codigo") . ' ' . $detalle), 'http_code' => $codigo];

        $reintentable = $codigo === 0 || $codigo === 429 || $codigo >= 500;
        if (!$reintentable) {
            return $resultado;
        }
        usleep(800000);
    }
    return $resultado;
}

/**
 * Ejecuta las llamadas a buscar_productos que pida la IA y le devuelve los
 * resultados, como máximo 2 rondas por mensaje (E5-H17: acota tiempo y costo).
 * Reenvía todo el "output" de cada respuesta: la API exige de vuelta los items
 * "reasoning" junto a cada function_call (si faltan, responde HTTP 400).
 * La respuesta final es solo el texto de los items "message"; si viene vacía o
 * incompleta se trata como falla y el cliente recibe la salida a WhatsApp.
 */
function chatResolverToolCalls(array $resultado, array $entrada, array $herramientas, $conexion, float $limiteHasta): array {
    $maxRondas = 2;
    $productosEncontrados = [];
    $debugBusquedas = [];

    for ($ronda = 1; ; $ronda++) {
        $output = $resultado['data']['output'] ?? [];
        $llamadas = [];
        foreach ($output as $item) {
            if (($item['type'] ?? '') === 'function_call') {
                $llamadas[] = $item;
            }
        }
        if (!$llamadas || $ronda > $maxRondas) {
            break;
        }

        foreach ($output as $item) {
            $entrada[] = $item;
        }
        foreach ($llamadas as $llamada) {
            $args = json_decode((string)($llamada['arguments'] ?? ''), true);
            $args = is_array($args) ? $args : [];
            $esBusqueda = ($llamada['name'] ?? '') === 'buscar_productos';
            $esPedido = ($llamada['name'] ?? '') === 'consultar_pedido'; // F3-12
            $aviso = $esBusqueda ? chatValidarBusqueda($args, $conexion) : ($esPedido ? null : 'Herramienta desconocida: usa buscar_productos o consultar_pedido.');
            $resultados = ($esBusqueda && $aviso === null && $conexion) ? chatEjecutarBusquedaProductos($conexion, $args) : [];
            if ($esPedido) {
                $consulta = $conexion ? chatConsultarPedido($conexion, $args) : ['aviso' => 'Sin conexión a la base.'];
                $aviso = $consulta['aviso'] ?? null;
                $resultados = isset($consulta['aviso']) ? [] : [$consulta];
            }
            foreach ($esBusqueda ? $resultados : [] as $p) {
                $productosEncontrados[$p['id']] = $p; // sin repetir (un pedido no es una tarjeta de producto)
            }
            $debugBusquedas[] = ['nombre' => $llamada['name'] ?? '', 'args' => $args, 'num_resultados' => count($resultados)];
            $entrada[] = [
                'type' => 'function_call_output',
                'call_id' => $llamada['call_id'] ?? '',
                'output' => json_encode($aviso !== null ? ['aviso' => $aviso] : $resultados, JSON_UNESCAPED_UNICODE),
            ];
        }

        $resultado = chatLlamarResponsesAPI($entrada, $herramientas, $limiteHasta);
        if (!$resultado['success']) {
            return $resultado + ['debug_busquedas' => $debugBusquedas];
        }
    }

    $texto = chatTextoRespuesta($resultado['data']);
    $estado = $resultado['data']['status'] ?? 'completed';
    if ($texto === '' || $estado === 'incomplete') {
        return ['success' => false, 'http_code' => 200, 'debug_busquedas' => $debugBusquedas,
                'error' => $estado === 'incomplete' ? 'Respuesta incompleta de la IA (status incomplete)'
                                                    : 'La IA no entregó texto (¿pidió más de ' . $maxRondas . ' rondas de herramientas?)'];
    }
    return ['success' => true, 'reply' => $texto, 'productos_encontrados' => array_values($productosEncontrados),
            'debug_busquedas' => $debugBusquedas, 'debug_modo' => 'tools'];
}

/**
 * Detalle de repuestos activos por id, para validar [ADD_TO_CART] contra lo que
 * el bot ya mostró en la conversación (E5-H6). Cada id se convierte a entero.
 */
function chatProductosPorIds($conexion, array $ids): array {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (!$conexion || !$ids) {
        return [];
    }
    $lista = implode(',', $ids);
    $r = $conexion->query("SELECT id, nombre, precio, imagen_url, stock FROM " . gmgSqlProductos() . " p
                            WHERE p.id IN ($lista) AND p.activo = 1");
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}
