<?php
/**
 * Búsqueda de productos e historial del Asistente IA - GMG Repuestos
 *
 * Historias: E5-H3 (buscar_productos), E5-H8 y E5-H15 (contexto de la
 * conversación), E5-H6 (agregar al carrito solo lo mostrado), RF-013
 * (disponibilidad). Incidentes: INC-05, INC-06. Especificación:
 * docs/FIX_ASISTENTE_IA.md, sección 4.
 *
 * - Texto libre del cliente -> grupos de búsqueda: normaliza, pasa a
 *   singular, quita género, entiende sinónimos chilenos ("balatas", "foco",
 *   "burro de arranque") y deja fuera el modelo y el año.
 * - chatConsultaBusqueda() arma el SQL con sentencias preparadas: primero
 *   exige todas las palabras (AND) y, si no hay resultados, basta una (OR);
 *   ordena por relevancia en el nombre y después por stock.
 * - El historial que va a la IA se lee de la base (nunca del navegador) y
 *   cada respuesta del bot lleva la lista numerada de productos que mostró.
 *
 * Archivo interno: bloqueado en backend/.htaccess. No carga otros archivos
 * para poder probarse solo (tests/chat_busqueda_test.php, prueba con SQLite
 * y con MariaDB en tests/).
 */

/** minúsculas, sin tildes, solo letras, números y espacios. */
function chatNormalizar(string $texto): string {
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto);
    return trim(preg_replace('/\s+/', ' ', $texto));
}

/** Plural simple del español a singular: terminales -> terminal, luces -> luz, pastillas -> pastilla. */
function chatSingular(string $palabra): string {
    $largo = strlen($palabra);
    if ($largo <= 3 || ctype_digit($palabra)) {
        return $palabra;
    }
    if (substr($palabra, -3) === 'ces') {
        return substr($palabra, 0, -3) . 'z';
    }
    if (substr($palabra, -2) === 'es' && strpos('lrndzj', $palabra[$largo - 3]) !== false) {
        return substr($palabra, 0, -2);
    }
    if (substr($palabra, -1) === 's') {
        return substr($palabra, 0, -1);
    }
    return $palabra;
}

/**
 * Raíz sin género: delantera/delantero -> delanter, optica -> optic.
 * Así "foco delantero" calza con "Óptica delantera".
 */
function chatRaiz(string $palabra): string {
    if (strlen($palabra) >= 6 && in_array(substr($palabra, -1), ['a', 'o'], true)) {
        return substr($palabra, 0, -1);
    }
    return $palabra;
}

/** Palabras que no ayudan a buscar (incluye chilenismos de relleno). */
function chatPalabrasVacias(): array {
    return ['de', 'del', 'la', 'el', 'los', 'las', 'un', 'una', 'unos', 'unas', 'para', 'pal', 'pa', 'por', 'con',
            'sin', 'y', 'o', 'mi', 'su', 'que', 'tiene', 'tienen', 'hay', 'necesito', 'busco', 'quiero', 'auto',
            'suzuki', 'repuesto', 'precio', 'cuanto', 'vale', 'sale', 'me', 'te', 'se', 'al', 'lo', 'wena', 'hola'];
}

/**
 * Sinónimos chilenos y técnicos -> raíz que aparece en el catálogo.
 * Cada clave es una palabra ya normalizada y en singular.
 */
function chatSinonimos(): array {
    return [
        'balata'      => ['pastilla', 'balata'],
        'pastilla'    => ['pastilla', 'balata'],
        'foco'        => ['optica'],
        'farol'       => ['optica'],
        'faro'        => ['optica'],
        'luz'         => ['optica'],
        'burro'       => ['partida'],
        'arranque'    => ['partida'],
        'palier'      => ['homocinetica'],
        'parrilla'    => ['bandeja'],
        'junta'       => ['empaquetadura'],
        'clutch'      => ['embrague'],
        'sonda'       => ['sensor'],
        'lambda'      => ['sensor'],
    ];
}

/**
 * Convierte texto libre en grupos de búsqueda. Cada grupo es una lista de
 * alternativas (OR); todos los grupos deben calzar (AND).
 * "balatas pal swift" -> [['balat','pastill']]  (swift se filtra aparte como modelo)
 */
function chatGruposBusqueda(string $texto, array $palabrasModelo = []): array {
    $vacias = array_flip(chatPalabrasVacias());
    $ignorar = array_flip($palabrasModelo);
    $sinonimos = chatSinonimos();
    $grupos = [];
    foreach (explode(' ', chatNormalizar($texto)) as $palabra) {
        if ($palabra === '' || isset($vacias[$palabra]) || isset($ignorar[$palabra])) {
            continue;
        }
        if (strlen($palabra) < 3) {
            continue;
        }
        if (ctype_digit($palabra) && (int)$palabra >= 1970 && (int)$palabra <= 2100) {
            continue; // un año va en el filtro de año, no en el texto
        }
        $singular = chatSingular($palabra);
        // Primero la palabra del cliente (pesa más en el orden), luego sus sinónimos.
        $grupo = array_values(array_unique(array_map('chatRaiz', array_merge([$singular], $sinonimos[$singular] ?? []))));
        $grupos[implode('|', $grupo)] = $grupo; // sin repetir grupos
    }
    return array_values($grupos);
}

/** "Suzuki S Presso" -> "spresso"; se compara contra REPLACE(slug,'-',''). */
function chatModeloClave(string $modelo): string {
    $limpio = chatNormalizar(preg_replace('/^\s*suzuki\s+/i', '', $modelo));
    return str_replace(' ', '', $limpio);
}

/**
 * Arma la consulta de buscar_productos con sentencias preparadas.
 * Devuelve [sql, tipos, parametros]. Ordena por relevancia (cuántos grupos
 * calzan en el nombre) y después por stock disponible.
 *
 * $args: modelo, anio, categoria, texto, sku_o_codigo_oem, precio_maximo
 * $estricto: true = todas las palabras deben calzar (AND). Si no hay
 * resultados, se repite con false (basta una) y el puntaje ordena.
 */
function chatConsultaBusqueda(array $args, int $limite = 8, bool $estricto = true): array {
    $where = ['r.activo = 1'];
    $tipos = '';
    $params = [];
    $puntaje = [];

    $sku = trim((string)($args['sku_o_codigo_oem'] ?? ''));
    if ($sku !== '') {
        $where[] = '(r.sku LIKE ? OR r.codigo_oem LIKE ?)';
        $tipos .= 'ss';
        array_push($params, "%$sku%", "%$sku%");
    }

    $modelo = chatModeloClave((string)($args['modelo'] ?? ''));
    $anio = (int)($args['anio'] ?? 0);
    if ($modelo !== '') {
        $condAnio = '';
        if ($anio >= 1970 && $anio <= 2100) {
            $condAnio = ' AND v.anio_desde <= ? AND (v.anio_hasta IS NULL OR v.anio_hasta >= ?)';
        }
        $where[] = "EXISTS (SELECT 1 FROM compatibilidades co
                              JOIN vehiculos v ON v.id_vehiculo = co.id_vehiculo
                              JOIN modelos mo ON mo.id_modelo = v.id_modelo
                             WHERE co.id_repuesto = r.id_repuesto
                               AND REPLACE(mo.slug, '-', '') = ?$condAnio)";
        $tipos .= 's';
        $params[] = $modelo;
        if ($condAnio !== '') {
            $tipos .= 'ii';
            array_push($params, $anio, $anio);
        }
    }

    $palabrasModelo = $modelo !== '' ? explode(' ', chatNormalizar((string)$args['modelo'])) : [];
    $texto = trim(($args['categoria'] ?? '') . ' ' . ($args['texto'] ?? ''));
    $condicionesTexto = [];
    foreach (chatGruposBusqueda($texto, $palabrasModelo) as $grupo) {
        $alternativas = [];
        $puntosGrupo = [];
        foreach ($grupo as $raiz) {
            $alternativas[] = '(r.nombre LIKE ? OR c.nombre LIKE ? OR r.descripcion LIKE ?)';
            $tipos .= 'sss';
            array_push($params, "%$raiz%", "%$raiz%", "%$raiz%");
            $puntosGrupo[] = $raiz;
        }
        $condicionesTexto[] = '(' . implode(' OR ', $alternativas) . ')';
        $puntaje[] = $puntosGrupo;
    }
    if ($condicionesTexto) {
        $where[] = '(' . implode($estricto ? ' AND ' : ' OR ', $condicionesTexto) . ')';
    }

    $precioMax = (float)($args['precio_maximo'] ?? 0);
    if ($precioMax > 0) {
        $where[] = 'r.precio <= ?';
        $tipos .= 'd';
        $params[] = $precioMax;
    }

    // Puntaje: cuántas palabras aparecen en el NOMBRE. La palabra que usó el
    // cliente vale 2 y un sinónimo vale 1 (pidió "pastillas": salen antes que "balatas").
    $orden = [];
    $tiposOrden = '';
    $paramsOrden = [];
    foreach ($puntaje as $grupo) {
        foreach ($grupo as $posicion => $raiz) {
            $orden[] = 'CASE WHEN r.nombre LIKE ? THEN ' . ($posicion === 0 ? 2 : 1) . ' ELSE 0 END';
            $tiposOrden .= 's';
            $paramsOrden[] = "%$raiz%";
        }
    }
    $expresionPuntaje = $orden ? implode(' + ', $orden) : '0';

    $sql = "SELECT r.id_repuesto AS id, ($expresionPuntaje) AS puntaje
              FROM repuestos r
              JOIN categorias c ON c.id_categoria = r.id_categoria
             WHERE " . implode("\n               AND ", $where) . "
             ORDER BY puntaje DESC, (r.stock > 0) DESC, r.nombre ASC
             LIMIT " . max(1, min(20, $limite));

    // Los parámetros del puntaje van primero porque aparecen antes en el SQL.
    return [$sql, $tiposOrden . $tipos, array_merge($paramsOrden, $params)];
}

/**
 * Búsqueda de la tienda (F2-5: E2-H3, E2-H7, INC-07): el buscador y sus sugerencias
 * usan la misma normalización del chat (plurales, sinónimos, palabras vacías).
 * Calza si el texto completo está en el SKU u OEM, o si TODAS las palabras aparecen
 * (cada una en el nombre, la categoría, la descripción, el SKU, el OEM o los modelos
 * compatibles; así "terminal swift" encuentra los terminales del Swift).
 * Trabaja sobre las columnas de gmgSqlProductos() con el alias dado.
 * Devuelve null si no hay nada que buscar; si no, la condición y el orden
 * (SKU exacto, SKU que empieza igual, palabras en el nombre) con sus parámetros.
 */
function gmgBusquedaTienda(string $q, string $alias = 'p'): ?array {
    $q = trim($q);
    if ($q === '') {
        return null;
    }
    // El texto completo va a un LIKE: %, _ y \ se buscan como letras, no como comodines.
    $qLike = '%' . addcslashes($q, '%_\\') . '%';
    $condicion = "($alias.sku LIKE ? OR $alias.codigo_oem LIKE ?)";
    $tipos = 'ss';
    $params = [$qLike, $qLike];

    $grupos = chatGruposBusqueda($q);
    $orden = ["CASE WHEN $alias.sku = ? THEN 0 WHEN $alias.sku LIKE ? THEN 1 ELSE 2 END"];
    $tiposOrden = 'ss';
    $paramsOrden = [$q, addcslashes($q, '%_\\') . '%'];
    if ($grupos) {
        $todas = [];
        $puntaje = [];
        foreach ($grupos as $grupo) {
            $alternativas = [];
            foreach ($grupo as $posicion => $raiz) {
                $alternativas[] = "($alias.nombre LIKE ? OR $alias.categoria LIKE ? OR $alias.descripcion LIKE ?
                                   OR $alias.sku LIKE ? OR $alias.codigo_oem LIKE ? OR $alias.modelo LIKE ?)";
                $tipos .= 'ssssss';
                array_push($params, "%$raiz%", "%$raiz%", "%$raiz%", "%$raiz%", "%$raiz%", "%$raiz%");
                // Como en el chat: la palabra del cliente en el nombre vale 2 y un sinónimo 1.
                $puntaje[] = "CASE WHEN $alias.nombre LIKE ? THEN " . ($posicion === 0 ? 2 : 1) . ' ELSE 0 END';
                $tiposOrden .= 's';
                $paramsOrden[] = "%$raiz%";
            }
            $todas[] = '(' . implode(' OR ', $alternativas) . ')';
        }
        $condicion = "($condicion OR (" . implode(' AND ', $todas) . '))';
        $orden[] = '(' . implode(' + ', $puntaje) . ') DESC';
    }
    return [
        'condicion' => $condicion, 'tipos' => $tipos, 'params' => $params,
        'orden' => implode(', ', $orden), 'tipos_orden' => $tiposOrden, 'params_orden' => $paramsOrden,
    ];
}

/**
 * Privacidad del chat (F3-7, E5-H18, RNF-017): borra las conversaciones sin mensajes en los
 * últimos $meses meses (por defecto 12). Los mensajes y sus repuestos se borran en cascada.
 * La llama backend/limpiar_chat.php desde el cron de Hostinger. Devuelve cuántas borró.
 */
function chatBorrarConversacionesAntiguas(mysqli $conexion, int $meses = 12): int {
    $meses = max(1, $meses);
    $stmt = $conexion->prepare("DELETE c FROM conversaciones_chat c
                                 WHERE c.creado_en < NOW() - INTERVAL ? MONTH
                                   AND NOT EXISTS (SELECT 1 FROM mensajes_chat m
                                                    WHERE m.id_conversacion = c.id_conversacion
                                                      AND m.creado_en >= NOW() - INTERVAL ? MONTH)");
    if (!$stmt) {
        error_log('[GMG Chat] No se pudo preparar la limpieza de conversaciones: ' . $conexion->error);
        return 0;
    }
    $stmt->bind_param('ii', $meses, $meses);
    $stmt->execute();
    $borradas = $stmt->affected_rows;
    $stmt->close();
    return max(0, $borradas);
}

/** Etiqueta de stock que entiende el cliente (RF-013). */
function chatDisponibilidad(int $stock): string {
    if ($stock <= 0) return 'Sin stock';
    if ($stock <= 3) return 'Últimas unidades';
    return 'Disponible';
}

/**
 * Convierte filas de mensajes_chat (las más nuevas primero) en el historial
 * para la API, en orden cronológico. A cada respuesta del bot le agrega la
 * lista numerada de productos que mostró, así "agrega el segundo" funciona.
 *
 * $filas: [['rol'=>'user'|'bot','contenido'=>..., 'productos'=>"12|FRE-SWI3-PDL|Pastillas...;;13|..."], ...]
 */
function chatHistorialParaAPI(array $filas): array {
    $mensajes = [];
    foreach (array_reverse($filas) as $fila) {
        $texto = mb_substr(trim((string)$fila['contenido']), 0, 1500, 'UTF-8');
        if ($texto === '') {
            continue;
        }
        if ($fila['rol'] === 'bot' && !empty($fila['productos'])) {
            $lineas = [];
            foreach (explode(';;', $fila['productos']) as $i => $producto) {
                $partes = explode('|', $producto, 3);
                if (count($partes) === 3) {
                    $lineas[] = ($i + 1) . ') id ' . $partes[0] . ', SKU ' . $partes[1] . ', ' . $partes[2];
                }
            }
            if ($lineas) {
                $texto .= "\n[Productos mostrados en esta respuesta: " . implode('; ', $lineas) . ']';
            }
        }
        $mensajes[] = ['role' => $fila['rol'] === 'bot' ? 'assistant' : 'user', 'content' => $texto];
    }
    return $mensajes;
}

/** Texto final de una respuesta de la Responses API: solo items "message". */
function chatTextoRespuesta(array $respuesta): string {
    $texto = '';
    foreach ($respuesta['output'] ?? [] as $item) {
        if (($item['type'] ?? '') !== 'message') {
            continue;
        }
        foreach ($item['content'] ?? [] as $parte) {
            if (($parte['type'] ?? '') === 'output_text') {
                $texto .= $parte['text'];
            }
        }
    }
    return trim($texto);
}

/**
 * Historial de la conversación leído desde la base (no desde el navegador).
 * Llamar ANTES de guardar el mensaje actual, así no queda duplicado.
 */
function chatHistorialDesdeBD(mysqli $conexion, int $idConversacion, int $limite = 8): array {
    $sql = "SELECT m.id_mensaje, m.rol, m.contenido,
                   GROUP_CONCAT(CONCAT(r.id_repuesto, '|', r.sku, '|', r.nombre)
                                ORDER BY mr.orden SEPARATOR ';;') AS productos
              FROM mensajes_chat m
              LEFT JOIN mensaje_repuestos mr ON mr.id_mensaje = m.id_mensaje
              LEFT JOIN repuestos r ON r.id_repuesto = mr.id_repuesto
             WHERE m.id_conversacion = ?
             GROUP BY m.id_mensaje, m.rol, m.contenido
             ORDER BY m.id_mensaje DESC
             LIMIT ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('ii', $idConversacion, $limite);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return chatHistorialParaAPI($filas);
}

/** IDs de repuestos que el bot ya mostró en esta conversación (para validar [ADD_TO_CART]). */
function chatIdsMostrados(mysqli $conexion, int $idConversacion): array {
    $stmt = $conexion->prepare("SELECT DISTINCT mr.id_repuesto
                                  FROM mensaje_repuestos mr
                                  JOIN mensajes_chat m ON m.id_mensaje = mr.id_mensaje
                                 WHERE m.id_conversacion = ?");
    $stmt->bind_param('i', $idConversacion);
    $stmt->execute();
    $ids = array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id_repuesto'));
    $stmt->close();
    return $ids;
}
