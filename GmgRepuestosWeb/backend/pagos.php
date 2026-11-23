<?php
/**
 * Registro de intentos de pago - GMG Repuestos (historia E4-H4)
 *
 * Cada vez que un cliente vuelve desde Mercado Pago, se guarda un registro
 * con la pasarela, el identificador de la transacción, el monto, el estado,
 * el código de autorización y la fecha. Un mismo pedido puede tener varios
 * intentos (por ejemplo, uno rechazado y uno aprobado): todos se conservan
 * en la tabla "intentos_pago" para poder conciliar los cobros.
 *
 * La tabla se crea con BD_v2/06_sprint1a4_tablas_nuevas.sql. Si todavía no
 * existe, estas funciones solo dejan un aviso en el registro de errores y la
 * tienda sigue funcionando.
 */

/** Traduce el estado de Mercado Pago al estado de pago que usa el sitio. */
function gmgTraducirEstadoPago($estadoMercadoPago) {
    switch (strtolower((string)$estadoMercadoPago)) {
        case 'approved':
        case 'success':
            return 'recibido';
        case 'pending':
        case 'in_process':
        case 'authorized':
        case 'in_mediation':
            return 'pendiente';
        default:
            return 'rechazado';
    }
}

/**
 * Consulta directamente a Mercado Pago los datos reales de un pago.
 * Así el resultado NO depende de lo que venga escrito en la dirección de
 * retorno (que cualquiera podría alterar).
 *
 * Devuelve un arreglo con estado, monto, medio de pago, código de autorización
 * y referencia externa; o null si no se pudo consultar.
 */
function gmgConsultarPagoMercadoPago($idPago) {
    $idPago = preg_replace('/\D+/', '', (string)$idPago);
    if ($idPago === '' || !defined('MP_ACCESS_TOKEN') || MP_ACCESS_TOKEN === '') {
        return null;
    }

    $ch = curl_init('https://api.mercadopago.com/v1/payments/' . $idPago);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => gmgCurlVerificarSsl(),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . MP_ACCESS_TOKEN,
            'Accept: application/json',
        ],
    ]);
    $respuesta = curl_exec($ch);
    $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $codigoHttp !== 200 || !$respuesta) {
        error_log('[GMG Pagos] No se pudo consultar el pago ' . $idPago . ' en Mercado Pago (HTTP ' . $codigoHttp . ') ' . $error);
        return null;
    }
    $datos = json_decode($respuesta, true);
    if (!is_array($datos) || !isset($datos['status'])) {
        return null;
    }
    return [
        'estado'              => (string)$datos['status'],
        'monto'               => isset($datos['transaction_amount']) ? (float)$datos['transaction_amount'] : null,
        'medio_pago'          => isset($datos['payment_type_id']) ? (string)$datos['payment_type_id'] : null,
        'codigo_autorizacion' => isset($datos['authorization_code']) ? (string)$datos['authorization_code'] : null,
        'referencia_externa'  => isset($datos['external_reference']) ? (string)$datos['external_reference'] : null,
    ];
}

/**
 * Guarda un intento de pago.
 *
 * $d: id_transaccion, monto, estado, medio_pago, codigo_autorizacion, verificado.
 * Si el mismo pago con el mismo estado ya estaba registrado (por ejemplo, el
 * cliente recargó la página) no se duplica. Devuelve true si quedó guardado.
 */
function gmgRegistrarIntentoPago($conexion, $idPedido, array $d) {
    $idPedido = (int)$idPedido;
    if ($idPedido <= 0) {
        return false;
    }

    $pasarela = 'Mercado Pago';
    $idTransaccion = isset($d['id_transaccion']) && $d['id_transaccion'] !== '' ? mb_substr((string)$d['id_transaccion'], 0, 60) : null;
    $estado = mb_substr((string)($d['estado'] ?? 'unknown'), 0, 30);
    $resultado = gmgTraducirEstadoPago($estado);
    $medioPago = isset($d['medio_pago']) && $d['medio_pago'] !== '' ? mb_substr((string)$d['medio_pago'], 0, 40) : null;
    $codigoAutorizacion = isset($d['codigo_autorizacion']) && $d['codigo_autorizacion'] !== '' ? mb_substr((string)$d['codigo_autorizacion'], 0, 40) : null;
    $verificado = !empty($d['verificado']) ? 1 : 0;
    $monto = isset($d['monto']) ? (float)$d['monto'] : null;

    // Si la pasarela no informó el monto, se usa el total del pedido.
    if ($monto === null) {
        $stmtTotal = $conexion->prepare("SELECT total FROM pedidos WHERE id_pedido = ?");
        if (!$stmtTotal) {
            return false;
        }
        $stmtTotal->bind_param("i", $idPedido);
        $stmtTotal->execute();
        $res = $stmtTotal->get_result();
        $fila = $res ? $res->fetch_assoc() : null;
        $stmtTotal->close();
        if (!$fila) {
            return false;
        }
        $monto = (float)$fila['total'];
    }

    // Retorno sin número de transacción (por ejemplo, el cliente vuelve sin
    // haber pagado): si el último intento del pedido es idéntico y reciente,
    // fue una recarga de la página y no se registra otra vez.
    if ($idTransaccion === null) {
        $stmtUlt = $conexion->prepare("SELECT 1 FROM intentos_pago WHERE id_pedido = ? AND id_transaccion IS NULL AND estado = ? AND fecha >= (NOW() - INTERVAL 2 MINUTE) LIMIT 1");
        if (!$stmtUlt) {
            error_log('[GMG Pagos] No se pudo revisar intentos_pago (¿falta ejecutar BD_v2/06_sprint1a4_tablas_nuevas.sql?): ' . $conexion->error);
            return false;
        }
        $stmtUlt->bind_param("is", $idPedido, $estado);
        $stmtUlt->execute();
        $resUlt = $stmtUlt->get_result();
        $repetido = $resUlt && $resUlt->num_rows > 0;
        $stmtUlt->close();
        if ($repetido) {
            return true;
        }
    }

    $stmt = $conexion->prepare("INSERT IGNORE INTO intentos_pago (id_pedido, pasarela, id_transaccion, monto, estado, resultado, medio_pago, codigo_autorizacion, verificado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log('[GMG Pagos] No se pudo guardar el intento de pago (¿falta ejecutar BD_v2/06_sprint1a4_tablas_nuevas.sql?): ' . $conexion->error);
        return false;
    }
    $stmt->bind_param("issdssssi", $idPedido, $pasarela, $idTransaccion, $monto, $estado, $resultado, $medioPago, $codigoAutorizacion, $verificado);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}
