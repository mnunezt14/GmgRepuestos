<?php
/**
 * Capa de acceso a datos - GMG Repuestos (base de datos v2)
 *
 * La base v2 está normalizada: un repuesto ya no guarda "modelo", "año" ni
 * "motor" como texto (eso vive en compatibilidades -> vehiculos -> modelos /
 * motores), las fotos están en repuesto_imagenes y los datos del cliente en
 * la tabla clientes.
 *
 * Para no reescribir todas las vistas, estas funciones devuelven subconsultas
 * con los MISMOS nombres de columna que usaban las páginas antes de la
 * migración (id, modelo, ano, motor, imagen_url, nombre_cliente, ...).
 * Uso:  "SELECT * FROM " . gmgSqlProductos() . " p WHERE p.activo = 1"
 *
 * Compatible con PHP 7.2+ y MariaDB 10.4+ / MySQL 8.
 */

/**
 * Repuestos con columnas "planas" (una fila por repuesto).
 *   modelo          -> modelos compatibles, ej: "Baleno, Ciaz, Ertiga, Swift"
 *   ano             -> rango total de años, ej: "2011-2022" o "2019-hoy"
 *   motor           -> motores compatibles, ej: "1.4L K14B"
 *   compatible_con  -> vehículos exactos separados por "; "
 */
function gmgSqlProductos() {
    return "(SELECT
            r.id_repuesto AS id,
            r.id_categoria,
            r.nombre,
            r.descripcion,
            r.sku,
            r.codigo_oem,
            r.marca_fabricante,
            r.precio,
            r.stock,
            r.stock_minimo,
            r.activo,
            c.nombre AS categoria,
            COALESCE((SELECT i1.url FROM repuesto_imagenes i1
                       WHERE i1.id_repuesto = r.id_repuesto AND i1.orden = 1),
                     'img/placeholder-repuesto.svg') AS imagen_url,
            (SELECT i2.url FROM repuesto_imagenes i2
              WHERE i2.id_repuesto = r.id_repuesto AND i2.orden = 2) AS imagen_url_2,
            (SELECT i3.url FROM repuesto_imagenes i3
              WHERE i3.id_repuesto = r.id_repuesto AND i3.orden = 3) AS imagen_url_3,
            (SELECT GROUP_CONCAT(DISTINCT mo.nombre ORDER BY mo.nombre SEPARATOR ', ')
               FROM compatibilidades co
               JOIN vehiculos v ON v.id_vehiculo = co.id_vehiculo
               JOIN modelos mo ON mo.id_modelo = v.id_modelo
              WHERE co.id_repuesto = r.id_repuesto) AS modelo,
            (SELECT CONCAT(MIN(v.anio_desde), '-',
                           IF(SUM(v.anio_hasta IS NULL) > 0, 'hoy', MAX(v.anio_hasta)))
               FROM compatibilidades co
               JOIN vehiculos v ON v.id_vehiculo = co.id_vehiculo
              WHERE co.id_repuesto = r.id_repuesto) AS ano,
            (SELECT GROUP_CONCAT(DISTINCT CONCAT(mt.cilindrada_litros, 'L ', mt.codigo) SEPARATOR ', ')
               FROM compatibilidades co
               JOIN vehiculos v ON v.id_vehiculo = co.id_vehiculo
               JOIN motores mt ON mt.id_motor = v.id_motor
              WHERE co.id_repuesto = r.id_repuesto) AS motor,
            (SELECT GROUP_CONCAT(CONCAT(mo.nombre, ' ', mt.cilindrada_litros,
                                        IF(mt.aspiracion = 'Turbo', ' Turbo', ''), ' ', mt.codigo,
                                        ' (', v.anio_desde, '-', COALESCE(v.anio_hasta, 'hoy'), ')')
                                 ORDER BY mo.nombre, v.anio_desde SEPARATOR '; ')
               FROM compatibilidades co
               JOIN vehiculos v ON v.id_vehiculo = co.id_vehiculo
               JOIN modelos mo ON mo.id_modelo = v.id_modelo
               JOIN motores mt ON mt.id_motor = v.id_motor
              WHERE co.id_repuesto = r.id_repuesto) AS compatible_con
        FROM repuestos r
        JOIN categorias c ON c.id_categoria = r.id_categoria)";
}

/**
 * Pedidos con los datos del cliente y el código de descuento ya unidos,
 * con los nombres de columna anteriores (id, nombre_cliente, rut_cliente...).
 */
function gmgSqlPedidos() {
    return "(SELECT
            pe.id_pedido AS id,
            pe.id_cliente,
            pe.id_codigo_descuento,
            pe.fecha,
            pe.estado,
            pe.pago_estado,
            pe.metodo_pago,
            pe.referencia_pago,
            pe.descuento_monto,
            pe.total,
            cl.nombre   AS nombre_cliente,
            cl.apellido AS apellido_cliente,
            cl.rut      AS rut_cliente,
            cl.telefono AS telefono_cliente,
            cl.email    AS email_cliente,
            cd.codigo   AS codigo_descuento
        FROM pedidos pe
        JOIN clientes cl ON cl.id_cliente = pe.id_cliente
        LEFT JOIN codigos_descuento cd ON cd.id_codigo = pe.id_codigo_descuento)";
}

/**
 * Líneas de pedido con los nombres de columna anteriores
 * (pedido_id, producto_id, nombre_producto).
 */
function gmgSqlPedidoItems() {
    return "(SELECT
            d.id_detalle      AS id,
            d.id_pedido       AS pedido_id,
            d.id_repuesto     AS producto_id,
            d.sku,
            d.nombre_repuesto AS nombre_producto,
            d.cantidad,
            d.precio_unitario
        FROM pedido_detalle d)";
}

/**
 * Condición SQL: el repuesto (columna $columnaId) sirve para el modelo dado.
 * Acepta el nombre ("Swift", "Grand Vitara"), el slug ("grand-vitara") o el
 * nombre con la marca ("Suzuki Swift").
 */
function gmgCondicionModelo($modelo, $columnaId = 'p.id') {
    // Sentencia preparada (F3-4, RNF-030): devuelve [condición, tipos, parámetros].
    $modelo = trim(preg_replace('/^Suzuki\s+/i', '', (string)$modelo));
    return ["$columnaId IN (SELECT co.id_repuesto
                             FROM compatibilidades co
                             JOIN vehiculos v ON v.id_vehiculo = co.id_vehiculo
                             JOIN modelos mo ON mo.id_modelo = v.id_modelo
                            WHERE mo.nombre = ? OR mo.slug = ?)", 'ss', [$modelo, $modelo]];
}

/**
 * Registra una búsqueda de la tienda sin resultados (F3-10, E2-H10, RF-017): suma 1 al término
 * con su modelo y categoría (tabla del script BD_v2/08). Sin IP ni datos personales; cada IP
 * registra como máximo 30 términos cada 10 minutos para que nadie llene la tabla.
 */
function gmgRegistrarBusquedaSinResultado($conexion, $termino, $modelo = '', $categoria = '') {
    $termino = mb_substr(mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string)$termino))), 0, 100);
    if ($termino === '' || gmgLimiteSuperado($conexion, 'busqueda_vacia', 30, 600)) {
        return;
    }
    $modelo = mb_substr(trim((string)$modelo), 0, 50);
    $categoria = mb_substr(trim((string)$categoria), 0, 80);
    $stmt = $conexion->prepare("INSERT INTO busquedas_sin_resultado (termino, modelo, categoria) VALUES (?, ?, ?)
                                ON DUPLICATE KEY UPDATE veces = veces + 1, ultima_vez = NOW()");
    if (!$stmt) {
        error_log('[GMG Búsquedas] No se pudo registrar (¿falta BD_v2/08?): ' . $conexion->error);
        return;
    }
    $stmt->bind_param('sss', $termino, $modelo, $categoria);
    $stmt->execute();
    $stmt->close();
}

/**
 * Normaliza un RUT chileno al formato que guarda la tabla clientes:
 * sin puntos ni espacios, con guion y K mayúscula. "21.740.488-6" -> "21740488-6".
 * Devuelve '' si no parece un RUT.
 */
function gmgNormalizarRut($rut) {
    $limpio = preg_replace('/[^0-9K]/', '', strtoupper((string)$rut));
    if (strlen($limpio) < 2 || strlen($limpio) > 11) {
        return '';
    }
    return substr($limpio, 0, -1) . '-' . substr($limpio, -1);
}

/**
 * Celular chileno (F2-7, RF-021): acepta "+56 9 1234 5678", "56912345678", "9 1234 5678"
 * o "912345678" (con espacios, guiones, puntos o paréntesis) y lo devuelve como
 * "+56 9 1234 5678". Devuelve '' si no es un celular (9 dígitos que empiezan con 9).
 */
function gmgTelefonoChile($telefono) {
    $digitos = preg_replace('/\D/', '', (string)$telefono);
    if (strlen($digitos) === 11 && strpos($digitos, '56') === 0) {
        $digitos = substr($digitos, 2);
    }
    if (!preg_match('/^9\d{8}$/', $digitos)) {
        return '';
    }
    return '+56 9 ' . substr($digitos, 1, 4) . ' ' . substr($digitos, 5);
}

/**
 * ¿El RUT chileno es válido? Revisa el formato y el dígito verificador
 * (cálculo "módulo 11"). Acepta con o sin puntos: "12.345.678-5".
 */
function gmgRutValido($rut) {
    $normalizado = gmgNormalizarRut($rut);
    if ($normalizado === '' || !preg_match('/^\d{6,9}-[0-9K]$/', $normalizado)) {
        return false;
    }
    list($cuerpo, $dv) = explode('-', $normalizado);
    $suma = 0;
    $multiplicador = 2;
    for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
        $suma += (int)$cuerpo[$i] * $multiplicador;
        $multiplicador = $multiplicador === 7 ? 2 : $multiplicador + 1;
    }
    $resto = 11 - ($suma % 11);
    $esperado = $resto === 11 ? '0' : ($resto === 10 ? 'K' : (string)$resto);
    return $dv === $esperado;
}

/**
 * Busca el cliente por RUT; si existe actualiza sus datos de contacto y si
 * no, lo crea. Devuelve id_cliente o 0 si falla.
 */
function gmgObtenerOCrearCliente($conexion, $rut, $nombre, $apellido, $email, $telefono) {
    $rut = gmgNormalizarRut($rut);
    if ($rut === '') {
        return 0;
    }
    $nombre = mb_substr($nombre, 0, 80);
    $apellido = mb_substr($apellido, 0, 80);
    $email = mb_substr($email, 0, 150);
    $telefono = mb_substr($telefono, 0, 20);

    $stmt = $conexion->prepare("SELECT id_cliente FROM clientes WHERE rut = ? LIMIT 1");
    $stmt->bind_param("s", $rut);
    $stmt->execute();
    $res = $stmt->get_result();
    $fila = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if ($fila) {
        $idCliente = (int)$fila['id_cliente'];
        $upd = $conexion->prepare("UPDATE clientes SET nombre = ?, apellido = ?, email = ?, telefono = ? WHERE id_cliente = ?");
        $upd->bind_param("ssssi", $nombre, $apellido, $email, $telefono, $idCliente);
        $upd->execute();
        $upd->close();
        return $idCliente;
    }

    $ins = $conexion->prepare("INSERT INTO clientes (rut, nombre, apellido, email, telefono) VALUES (?, ?, ?, ?, ?)");
    $ins->bind_param("sssss", $rut, $nombre, $apellido, $email, $telefono);
    $ok = $ins->execute();
    $idCliente = $ok ? (int)$conexion->insert_id : 0;
    $ins->close();
    return $idCliente;
}

/** Categorías ordenadas: [['id_categoria'=>..., 'nombre'=>...], ...] */
function gmgCategorias($conexion) {
    $lista = [];
    $r = $conexion->query("SELECT id_categoria, nombre FROM categorias ORDER BY orden, nombre");
    if ($r) {
        while ($f = $r->fetch_assoc()) {
            $lista[] = $f;
        }
    }
    return $lista;
}

/**
 * Vehículos agrupados por modelo, para los casilleros de compatibilidad del
 * panel admin: ['Swift' => [['id_vehiculo'=>6, 'descripcion'=>'Swift 1.2 K12B (2011-2017)'], ...], ...]
 */
function gmgVehiculosPorModelo($conexion) {
    $grupos = [];
    $r = $conexion->query("SELECT vv.id_vehiculo, vv.modelo, vv.descripcion, vv.generacion
                             FROM vw_vehiculos vv
                             JOIN modelos mo ON mo.id_modelo = vv.id_modelo
                            ORDER BY mo.orden, vv.anio_desde, vv.motor_codigo");
    if ($r) {
        while ($f = $r->fetch_assoc()) {
            $grupos[$f['modelo']][] = $f;
        }
    }
    return $grupos;
}

/** IDs de vehículos compatibles con un repuesto. */
function gmgVehiculosDeRepuesto($conexion, $idRepuesto) {
    $ids = [];
    $stmt = $conexion->prepare("SELECT id_vehiculo FROM compatibilidades WHERE id_repuesto = ?");
    $stmt->bind_param("i", $idRepuesto);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($f = $res->fetch_assoc())) {
        $ids[] = (int)$f['id_vehiculo'];
    }
    $stmt->close();
    return $ids;
}

/** Reemplaza la lista de vehículos compatibles de un repuesto. */
function gmgGuardarCompatibilidades($conexion, $idRepuesto, array $idsVehiculos) {
    $idsVehiculos = array_values(array_unique(array_filter(array_map('intval', $idsVehiculos))));

    $del = $conexion->prepare("DELETE FROM compatibilidades WHERE id_repuesto = ?");
    $del->bind_param("i", $idRepuesto);
    $ok = $del->execute();
    $del->close();

    if (count($idsVehiculos) > 0) {
        $ins = $conexion->prepare("INSERT INTO compatibilidades (id_repuesto, id_vehiculo) VALUES (?, ?)");
        foreach ($idsVehiculos as $idVehiculo) {
            $ins->bind_param("ii", $idRepuesto, $idVehiculo);
            $ok = $ins->execute() && $ok;
        }
        $ins->close();
    }
    return $ok;
}

/** Reemplaza las fotos (hasta 3) de un repuesto, en orden. */
function gmgGuardarImagenesRepuesto($conexion, $idRepuesto, array $urls, $textoAlt = '') {
    $urls = array_values(array_filter(array_map('trim', $urls), 'strlen'));
    $urls = array_slice($urls, 0, 3);
    if (count($urls) === 0) {
        $urls = ['img/placeholder-repuesto.svg'];
    }
    $textoAlt = mb_substr((string)$textoAlt, 0, 150);

    $del = $conexion->prepare("DELETE FROM repuesto_imagenes WHERE id_repuesto = ?");
    $del->bind_param("i", $idRepuesto);
    $ok = $del->execute();
    $del->close();

    $ins = $conexion->prepare("INSERT INTO repuesto_imagenes (id_repuesto, url, texto_alt, orden) VALUES (?, ?, ?, ?)");
    foreach ($urls as $i => $url) {
        $orden = $i + 1;
        $url = mb_substr($url, 0, 255);
        $ins->bind_param("issi", $idRepuesto, $url, $textoAlt, $orden);
        $ok = $ins->execute() && $ok;
    }
    $ins->close();
    return $ok;
}
