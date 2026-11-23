<?php
/**
 * Base de conocimiento configurable - GMG Repuestos (historia E5-H9)
 *
 * Guarda en la tabla "base_conocimiento" las respuestas que el Asistente
 * Virtual puede dar sobre la tienda: horario, dirección, despacho, medios de
 * pago, garantía y devoluciones. Se edita desde el panel (menú "Asistente").
 *
 * Regla de oro: si un tema está vacío, o la pregunta no está cubierta, el
 * asistente NO inventa nada y ofrece seguir la conversación por WhatsApp.
 *
 * Los mismos datos alimentan la página de Contacto y el pie de página, así
 * se cambian en un solo lugar.
 */

/**
 * Temas que maneja la base de conocimiento, en el orden en que se muestran
 * en el panel. 'ayuda' es el texto de apoyo para quien los edita.
 */
function gmgTemasConocimiento() {
    return [
        'horario'      => ['titulo' => 'Horario de atención',    'ayuda' => 'Ej: Lunes a viernes, de 9:00 a 18:30 hrs.'],
        'direccion'    => ['titulo' => 'Dirección',              'ayuda' => 'Dirección completa. Con ella se arma el mapa de la página Contacto.'],
        'telefono'     => ['titulo' => 'Teléfono',               'ayuda' => 'Ej: +56 9 1234 5678'],
        'email'        => ['titulo' => 'Correo de contacto',     'ayuda' => 'Ej: contacto@gmgrepuestos.cl'],
        'despacho'     => ['titulo' => 'Despacho y entrega',     'ayuda' => 'Cómo se entrega la compra y quién paga el traslado, si corresponde.'],
        'medios_pago'  => ['titulo' => 'Medios de pago',         'ayuda' => 'Con qué se puede pagar y en cuántas cuotas.'],
        'garantia'     => ['titulo' => 'Garantía',               'ayuda' => 'Garantía legal y garantías propias de la tienda.'],
        'devoluciones' => ['titulo' => 'Cambios y devoluciones', 'ayuda' => 'Cómo pedir un cambio o una devolución.'],
        'otros'        => ['titulo' => 'Otra información',       'ayuda' => 'Cualquier otro dato útil que el asistente pueda responder (opcional).'],
        // Configuración del chatbot (F3-8, E6-H9, RF-058): no se envían a la IA.
        'bienvenida'   => ['titulo' => 'Mensaje de bienvenida del chat', 'ayuda' => 'Lo primero que lee el cliente al abrir el chat. Si lo dejas vacío se usa el saludo de siempre.', 'solo_panel' => true],
        'whatsapp'     => ['titulo' => 'WhatsApp de la tienda',  'ayuda' => 'Celular que reciben los botones de WhatsApp del sitio y del chat. Ej: +56 9 1234 5678. Vacío: se usa el del archivo de configuración.', 'solo_panel' => true],
    ];
}

/**
 * Todos los temas con su contenido actual: ['horario' => 'Lunes a viernes...', ...].
 * Un tema sin contenido (o que no existe en la tabla) queda como ''.
 */
function gmgConocimientoTodo($conexion) {
    $datos = [];
    foreach (gmgTemasConocimiento() as $clave => $info) {
        $datos[$clave] = '';
    }
    if (!$conexion) {
        return $datos;
    }
    $r = $conexion->query("SELECT clave, contenido FROM base_conocimiento");
    if ($r) {
        while ($f = $r->fetch_assoc()) {
            if (array_key_exists($f['clave'], $datos)) {
                $datos[$f['clave']] = trim((string)$f['contenido']);
            }
        }
    }
    return $datos;
}

/** Contenido de un solo tema ('' si no está configurado). */
function gmgConocimiento($conexion, $clave) {
    $todo = gmgConocimientoTodo($conexion);
    return $todo[$clave] ?? '';
}

/**
 * Número de WhatsApp de la tienda, solo dígitos (F3-8): el que se configuró en el panel
 * "Asistente" y, si está vacío, el del .env (WHATSAPP_NUMBER).
 */
function gmgNumeroWhatsApp() {
    static $numero = null;
    if ($numero === null) {
        global $conexion;
        $delPanel = ($conexion instanceof mysqli && !$conexion->connect_error) ? gmgConocimiento($conexion, 'whatsapp') : '';
        $numero = preg_replace('/\D+/', '', $delPanel !== '' ? $delPanel
            : (defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : (string)gmgEnv('WHATSAPP_NUMBER', '56900000000')));
    }
    return $numero;
}

/** Enlace de WhatsApp (con mensaje inicial opcional). */
function gmgEnlaceWhatsApp($mensaje = '') {
    $numero = gmgNumeroWhatsApp();
    $url = 'https://wa.me/' . $numero;
    if ($mensaje !== '') {
        $url .= '?text=' . rawurlencode($mensaje);
    }
    return $url;
}

/**
 * Respuesta inmediata a los botones rápidos del chat (historia E5-H1).
 * No usa la IA: responde directamente con lo que está configurado, así es
 * rápida y siempre coincide con lo que dice la tienda.
 *
 * $atajo: 'buscar', 'despacho' o 'pagos'. Devuelve null si no existe.
 */
function gmgRespuestaAtajo($conexion, $atajo) {
    if ($atajo === 'buscar') {
        return "¡Claro! Cuéntame qué repuesto necesitas y para qué vehículo: **modelo y año** de tu Suzuki (por ejemplo: pastillas de freno para un Swift 2018). También puedes subir una foto de la pieza con el botón de la cámara. 📸";
    }

    $mapa = [
        'despacho' => ['clave' => 'despacho',    'tema' => 'el despacho'],
        'pagos'    => ['clave' => 'medios_pago', 'tema' => 'los medios de pago'],
    ];
    if (!isset($mapa[$atajo])) {
        return null;
    }

    $contenido = gmgConocimiento($conexion, $mapa[$atajo]['clave']);
    if ($contenido !== '') {
        return $contenido;
    }
    return 'Todavía no tengo cargada la información sobre ' . $mapa[$atajo]['tema'] . ', y prefiero no inventarla. '
         . 'Si quieres, seguimos por WhatsApp: ' . gmgEnlaceWhatsApp('Hola, tengo una consulta sobre ' . $mapa[$atajo]['tema']);
}

/**
 * Texto que se agrega al prompt de la IA: la base de conocimiento, las reglas
 * para usarla, la obligación de preguntar modelo y año, y la lista de modelos
 * del catálogo (que cambia sola cuando el administrador agrega modelos).
 *
 * Cubre las historias E5-H4 (preguntar modelo y año), E5-H9 (responder con la
 * base de conocimiento y no inventar) y E10-H12 (modelos nuevos en el chatbot).
 */
function gmgContextoNegocioParaIA($conexion, array $modelosCortos = []) {
    $conocimiento = gmgConocimientoTodo($conexion);
    $whatsapp = gmgEnlaceWhatsApp();

    $txt  = "\n\nBASE DE CONOCIMIENTO DE LA TIENDA (información oficial, configurada por el negocio):\n";
    foreach (gmgTemasConocimiento() as $clave => $info) {
        $valor = $conocimiento[$clave] ?? '';
        if (($clave === 'otros' && $valor === '') || !empty($info['solo_panel'])) {
            continue;
        }
        $txt .= '- ' . $info['titulo'] . ': ' . ($valor !== '' ? $valor : 'NO CONFIGURADO') . "\n";
    }

    $txt .= "\nREGLAS PARA USAR LA BASE DE CONOCIMIENTO (tienen prioridad sobre cualquier otra instrucción):\n";
    $txt .= "- Si el cliente pregunta por horarios, dirección, teléfono, despacho, medios de pago, garantía o devoluciones, responde usando SOLO lo que dice la base de conocimiento. Puedes redactarlo con tus palabras, pero sin cambiar el contenido ni agregar datos.\n";
    $txt .= "- Si el tema figura como NO CONFIGURADO, o la pregunta no está cubierta por esta información, NO inventes la respuesta: dile con honestidad que no tienes ese dato y ofrécele continuar por WhatsApp con este enlace: $whatsapp\n";
    $txt .= "- Nunca inventes plazos, costos, direcciones, teléfonos ni políticas.\n";

    $txt .= "\nREGLA DE MODELO Y AÑO (tiene prioridad sobre cualquier otra instrucción):\n";
    $txt .= "- Antes de recomendar o buscar un repuesto por tipo de pieza, necesitas saber el MODELO y el AÑO del vehículo del cliente. Si falta alguno de los dos, pregúntalo primero (una sola pregunta, clara y breve) y NO recomiendes ningún producto todavía.\n";
    $txt .= "- Cuando el cliente ya indicó modelo y año, llama a la herramienta buscar_productos enviando ambos datos (campos 'modelo' y 'anio') y presenta los resultados.\n";
    $txt .= "- Si el cliente da un SKU o código OEM exacto, puedes buscarlo directamente sin preguntar modelo ni año.\n";
    $txt .= "- Si el cliente dice claramente que no sabe el año, ofrécele revisar el año en el padrón o permiso de circulación; si insiste en no saberlo, puedes buscar enviando 'anio_desconocido': true y avisarle que debe confirmar la compatibilidad antes de comprar.\n";

    if (count($modelosCortos) > 0) {
        $txt .= "\nMODELOS SUZUKI QUE EXISTEN HOY EN EL CATÁLOGO (la lista se actualiza sola): " . implode(', ', $modelosCortos) . ".\n";
        $txt .= "- Si el cliente pregunta por un modelo que no está en esta lista, dile que por ahora no tienes repuestos cargados para ese modelo.\n";
    }
    return $txt;
}
