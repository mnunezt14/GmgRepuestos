<?php
/**
 * Modelos Suzuki: lista centralizada y utilidades.
 *
 * Desde la base de datos v2 los modelos viven en la tabla `modelos`
 * (nombre, slug, imagen de portada y orden), así que ya no hay una lista
 * escrita a mano en este archivo:
 * - Para agregar un modelo nuevo basta con insertarlo en `modelos` y crear
 *   sus versiones en `vehiculos`.
 * - En la portada solo aparecen los modelos que tienen al menos un repuesto
 *   activo compatible.
 * - La foto de cada modelo es la columna modelos.imagen_url; si está vacía
 *   se usa la imagen genérica de "foto próximamente".
 *
 * Los nombres "completos" (con la marca, ej: "Suzuki Swift") se usan para
 * mostrar; los "cortos" (ej: "Swift") en las URL ?modelo=... y el buscador.
 */

/** Cache por petición: nombre corto => fila de la tabla modelos. */
$GMG_MODELOS_CACHE = null;

function gmgCargarModelos($conexion) {
    global $GMG_MODELOS_CACHE;
    if ($GMG_MODELOS_CACHE !== null) {
        return $GMG_MODELOS_CACHE;
    }
    $GMG_MODELOS_CACHE = [];
    $r = $conexion->query("
        SELECT ma.nombre AS marca, mo.nombre, mo.slug, mo.imagen_url, mo.orden,
               (SELECT COUNT(DISTINCT co.id_repuesto)
                  FROM vehiculos v
                  JOIN compatibilidades co ON co.id_vehiculo = v.id_vehiculo
                  JOIN repuestos r ON r.id_repuesto = co.id_repuesto
                 WHERE v.id_modelo = mo.id_modelo AND v.activo = 1 AND r.activo = 1) AS num_repuestos,
               (SELECT MIN(v.anio_desde) FROM vehiculos v
                 WHERE v.id_modelo = mo.id_modelo AND v.activo = 1) AS anio_desde,
               (SELECT IF(SUM(v.anio_hasta IS NULL) > 0, NULL, MAX(v.anio_hasta)) FROM vehiculos v
                 WHERE v.id_modelo = mo.id_modelo AND v.activo = 1) AS anio_hasta
          FROM modelos mo
          JOIN marcas ma ON ma.id_marca = mo.id_marca
         WHERE mo.activo = 1 AND ma.activo = 1
         ORDER BY mo.orden, mo.nombre");
    if ($r) {
        while ($fila = $r->fetch_assoc()) {
            $GMG_MODELOS_CACHE[$fila['nombre']] = $fila;
        }
    }
    return $GMG_MODELOS_CACHE;
}

/**
 * Datos de la tarjeta de un modelo en la portada: cantidad de repuestos
 * activos y rango de años de sus versiones (ej: "2011-hoy").
 */
function gmgModeloResumen($modeloCompleto) {
    global $GMG_MODELOS_CACHE;
    $corto = gmgModeloCorto($modeloCompleto);
    $fila = is_array($GMG_MODELOS_CACHE) && isset($GMG_MODELOS_CACHE[$corto]) ? $GMG_MODELOS_CACHE[$corto] : null;
    if (!$fila) {
        return ['repuestos' => 0, 'anios' => ''];
    }
    $anios = '';
    if (!empty($fila['anio_desde'])) {
        $anios = $fila['anio_desde'] . '-' . (empty($fila['anio_hasta']) ? 'hoy' : $fila['anio_hasta']);
    }
    return ['repuestos' => (int)$fila['num_repuestos'], 'anios' => $anios];
}

/** "Suzuki Swift" -> "Swift" */
function gmgModeloCorto($modeloCompleto) {
    return trim(preg_replace('/^Suzuki\s+/i', '', (string)$modeloCompleto));
}

/** Imagen de portada de un modelo, o un genérico si todavía no tiene una. */
function gmgModeloImagen($modeloCompleto) {
    global $GMG_MODELOS_CACHE;
    $corto = gmgModeloCorto($modeloCompleto);
    if (is_array($GMG_MODELOS_CACHE) && !empty($GMG_MODELOS_CACHE[$corto]['imagen_url'])) {
        $imagen = $GMG_MODELOS_CACHE[$corto]['imagen_url'];
        // Rendimiento (F4-7): si junto a la JPG o PNG hay una versión WebP (pesa un tercio), se usa esa.
        $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $imagen);
        if ($webp !== $imagen && strpos($webp, '..') === false && is_file(dirname(__DIR__) . '/' . $webp)) {
            return $webp;
        }
        return $imagen;
    }
    return 'img/placeholder-modelo.svg';
}

/**
 * Ordena una lista de nombres de modelo según la columna modelos.orden
 * (los que no estén en la tabla quedan al final, en orden alfabético).
 */
function gmgOrdenarModelos(array $listaModelos) {
    global $GMG_MODELOS_CACHE;
    $orden = is_array($GMG_MODELOS_CACHE) ? array_keys($GMG_MODELOS_CACHE) : [];
    usort($listaModelos, function ($a, $b) use ($orden) {
        $ia = array_search(gmgModeloCorto($a), $orden, true);
        $ib = array_search(gmgModeloCorto($b), $orden, true);
        $ia = ($ia === false) ? PHP_INT_MAX : $ia;
        $ib = ($ib === false) ? PHP_INT_MAX : $ib;
        if ($ia === $ib) {
            return strcasecmp($a, $b);
        }
        return ($ia < $ib) ? -1 : 1;
    });
    return $listaModelos;
}

/**
 * Modelos con al menos un repuesto activo, ya ordenados, con la marca
 * adelante (ej: "Suzuki Swift"). Es lo que se muestra al público.
 */
function gmgModelosConProductos($conexion) {
    $lista = [];
    foreach (gmgCargarModelos($conexion) as $fila) {
        if ((int)$fila['num_repuestos'] > 0) {
            $lista[] = $fila['marca'] . ' ' . $fila['nombre'];
        }
    }
    return $lista;
}

/** Une una lista de textos en formato "A, B y C". */
function gmgUnirConY(array $items) {
    $items = array_values(array_filter($items, function ($i) { return $i !== ''; }));
    $n = count($items);
    if ($n === 0) return '';
    if ($n === 1) return $items[0];
    $ultimo = array_pop($items);
    return implode(', ', $items) . ' y ' . $ultimo;
}
