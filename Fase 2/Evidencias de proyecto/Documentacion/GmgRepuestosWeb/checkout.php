<?php
/**
 * Registro del pedido y creación del pago en Mercado Pago.
 *
 * Historia E3-H7: cada compra se registra como un pedido con número único,
 * estado inicial "pendiente" (pendiente de pago), fecha, datos del cliente,
 * método de entrega y método de pago. Con la base de datos v2 el pedido se
 * guarda en las tablas clientes, pedidos y pedido_detalle, todo dentro de una
 * transacción: o se guarda completo o no se guarda nada.
 */
header('Content-Type: application/json; charset=utf-8');
require_once 'backend/config.php';
require_once 'backend/datos.php';
require_once 'backend/email_helpers.php';

// Límite por IP (F3-3, RNF-035): 10 intentos de compra cada 10 minutos.
if (gmgLimiteSuperado($conexion, 'checkout', 10, 600)) {
    echo json_encode(['success' => false, 'message' => 'Hiciste muchos intentos de pago seguidos. Espera unos minutos o escríbenos por WhatsApp.']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Carrito vacío']);
    exit;
}

// --- Datos del cliente (obligatorios; GMG Repuestos no hace envíos,
//     por eso NO se pide dirección, solo datos de contacto) ---
// Solo texto: un campo enviado como arreglo cortaba la página en PHP 8 (hallazgo de F2-7).
$cliente = isset($data['cliente']) && is_array($data['cliente']) ? $data['cliente'] : [];
$campoCliente = function ($clave) use ($cliente) {
    return isset($cliente[$clave]) && is_string($cliente[$clave]) ? trim($cliente[$clave]) : '';
};
$nombreCliente = $campoCliente('nombre');
$apellidoCliente = $campoCliente('apellido');
$rutCliente = $campoCliente('rut');
$telefonoCliente = $campoCliente('telefono');
$emailCliente = $campoCliente('email');

if ($nombreCliente === '' || $apellidoCliente === '' || $rutCliente === '' || $telefonoCliente === '' || $emailCliente === '') {
    echo json_encode(['success' => false, 'message' => 'Faltan datos del cliente (nombre, apellido, RUT, teléfono y correo son obligatorios).']);
    exit;
}

// Celular chileno (F2-7): se guarda siempre como "+56 9 1234 5678".
$telefonoCliente = gmgTelefonoChile($telefonoCliente);
if ($telefonoCliente === '') {
    echo json_encode(['success' => false, 'message' => 'El teléfono no es válido. Ingresa un celular chileno, por ejemplo +56 9 1234 5678.']);
    exit;
}

if (!filter_var($emailCliente, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'El correo electrónico ingresado no es válido.']);
    exit;
}

if (!gmgRutValido($rutCliente)) {
    echo json_encode(['success' => false, 'message' => 'El RUT ingresado no es válido. Revisa que esté completo y con guion (ej: 12.345.678-9).']);
    exit;
}

// El cliente debe haber aceptado los términos y condiciones (Ley 19.496, E9-H1)
if (empty($data['acepta_terminos'])) {
    echo json_encode(['success' => false, 'message' => 'Debes aceptar los términos y condiciones y la política de privacidad para continuar.']);
    exit;
}

// --- Productos: se juntan las líneas repetidas y se leen los precios REALES
//     de la base de datos (nunca se confía en el precio que manda el navegador) ---
$cantidadesPedidas = [];
foreach ($data['items'] as $itemReq) {
    $idItem = (int)($itemReq['id'] ?? 0);
    $cantidadItem = (int)($itemReq['quantity'] ?? 0);
    if ($idItem > 0 && $cantidadItem > 0) {
        $cantidadesPedidas[$idItem] = ($cantidadesPedidas[$idItem] ?? 0) + min($cantidadItem, 999);
    }
}
if (count($cantidadesPedidas) === 0) {
    echo json_encode(['success' => false, 'message' => 'Monto o productos inválidos']);
    exit;
}

$idsList = implode(',', array_map('intval', array_keys($cantidadesPedidas)));
$resultado = $conexion->query("SELECT p.id, p.nombre, p.sku, p.precio, p.imagen_url FROM " . gmgSqlProductos() . " p WHERE p.activo = 1 AND p.id IN ($idsList)");

$productosDb = [];
if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $productosDb[(int)$fila['id']] = $fila;
    }
}

$baseSitio = gmgUrlBase();
$mpItems = [];
foreach ($cantidadesPedidas as $id => $cantidad) {
    if (!isset($productosDb[$id])) {
        continue;
    }
    $item = [
        "id" => (string)$id,
        "title" => $productosDb[$id]['nombre'],
        "description" => "Repuesto Suzuki",
        "quantity" => $cantidad,
        "currency_id" => "CLP",
        "unit_price" => (float)$productosDb[$id]['precio']
    ];
    // Mercado Pago pide una dirección completa para la foto. Solo se envía si
    // el sitio es público (en un computador local la foto no sería alcanzable).
    $foto = (string)$productosDb[$id]['imagen_url'];
    if ($foto !== '' && !gmgEsLocal()) {
        $item["picture_url"] = preg_match('#^https?://#i', $foto) ? $foto : $baseSitio . '/' . ltrim($foto, '/');
    }
    $mpItems[] = $item;
}

if (count($mpItems) === 0) {
    echo json_encode(['success' => false, 'message' => 'Monto o productos inválidos']);
    exit;
}

$subtotalPedido = 0;
foreach ($mpItems as $mpItem) {
    $subtotalPedido += $mpItem['unit_price'] * $mpItem['quantity'];
}

// --- Código de descuento (opcional). Se vuelve a validar en el
//     servidor: nunca se confía en el descuento que venga del navegador. ---
$codigoDescuento = trim($data['codigo_descuento'] ?? '');
$montoDescuento = 0;
$idCodigoDescuento = null;

if ($codigoDescuento !== '') {
    $stmtCod = $conexion->prepare("SELECT id_codigo, tipo, valor FROM codigos_descuento WHERE codigo = ? AND activo = 1 AND (vigente_hasta IS NULL OR vigente_hasta >= CURDATE()) LIMIT 1");
    $stmtCod->bind_param("s", $codigoDescuento);
    $stmtCod->execute();
    $resCod = $stmtCod->get_result();
    if ($resCod && $resCod->num_rows === 1) {
        $filaCod = $resCod->fetch_assoc();
        $idCodigoDescuento = (int)$filaCod['id_codigo'];
        if ($filaCod['tipo'] === 'porcentaje') {
            $montoDescuento = $subtotalPedido * ((float)$filaCod['valor'] / 100);
        } else {
            $montoDescuento = (float)$filaCod['valor'];
        }
        if ($montoDescuento > $subtotalPedido) {
            $montoDescuento = $subtotalPedido;
        }
        $montoDescuento = round($montoDescuento);
    } else {
        // Código inválido o vencido: se ignora silenciosamente el descuento (no se
        // bloquea la compra), el total final ya no lo incluye.
        $codigoDescuento = '';
    }
    $stmtCod->close();
}

$totalPedido = $subtotalPedido - $montoDescuento;

// Si hay descuento, se prorratea sobre cada línea de producto para que
// Mercado Pago cobre exactamente el total con descuento (MP no admite
// líneas de precio negativo para mostrar el descuento como ítem aparte).
if ($montoDescuento > 0 && $subtotalPedido > 0) {
    $factor = $totalPedido / $subtotalPedido;
    $acumulado = 0;
    foreach ($mpItems as $i => $mpItem) {
        $esUltimo = ($i === count($mpItems) - 1);
        if ($esUltimo) {
            // Ajusta el último ítem para que la suma cuadre exacta (evita
            // diferencias de $1-2 por redondeo).
            $lineaTotal = $totalPedido - $acumulado;
        } else {
            $lineaTotal = round(($mpItem['unit_price'] * $mpItem['quantity']) * $factor);
            $acumulado += $lineaTotal;
        }
        $mpItems[$i]['unit_price'] = $mpItem['quantity'] > 0 ? round($lineaTotal / $mpItem['quantity'], 2) : $mpItem['unit_price'];
    }
}

// --- Registrar el pedido en la base de datos ANTES de contactar a Mercado Pago,
//     para tener un número de pedido que el panel de administración pueda mostrar y
//     para poder marcar el pago como recibido/rechazado cuando el cliente vuelva. ---
$metodoEntrega = 'Coordinación por WhatsApp';
$pedidoId = 0;
$errorRegistro = false;

// La columna metodo_entrega la agrega el script BD_v2/06_sprint1a4_tablas_nuevas.sql.
// Si ese script todavía no se ejecutó, el pedido se registra igual (sin ese dato).
$tieneMetodoEntrega = false;
$resColumna = $conexion->query("SHOW COLUMNS FROM pedidos LIKE 'metodo_entrega'");
if ($resColumna && $resColumna->num_rows > 0) {
    $tieneMetodoEntrega = true;
}

$conexion->begin_transaction();
try {
    // Stock (E3-H6, INC-10, RF-024): se lee dentro de la transacción y con bloqueo
    // (FOR UPDATE), así una compra simultánea no trabaja con un stock viejo. Si no
    // alcanza, no se crea el pedido y se dice qué producto falta y cuántos quedan.
    // El stock se descuenta al aprobar el pedido (F2-3), no aquí.
    $listaStock = implode(',', array_map('intval', array_keys($productosDb)));
    $resStock = $conexion->query("SELECT id_repuesto, stock FROM repuestos WHERE id_repuesto IN ($listaStock) FOR UPDATE");
    if (!$resStock) {
        throw new Exception('No se pudo revisar el stock');
    }
    $faltantes = [];
    while ($filaStock = $resStock->fetch_assoc()) {
        $idStock = (int)$filaStock['id_repuesto'];
        if ($cantidadesPedidas[$idStock] > (int)$filaStock['stock']) {
            $faltantes[] = ['id' => $idStock, 'nombre' => $productosDb[$idStock]['nombre'], 'disponible' => max(0, (int)$filaStock['stock'])];
        }
    }
    if ($faltantes) {
        $conexion->rollback();
        $detalle = implode(', ', array_map(function ($f) {
            return $f['nombre'] . ($f['disponible'] > 0 ? ' (quedan ' . $f['disponible'] . ')' : ' (sin stock)');
        }, $faltantes));
        echo json_encode(['success' => false,
                          'message' => 'No hay stock suficiente de: ' . $detalle . '. Ajusta la cantidad e inténtalo de nuevo.',
                          'sin_stock' => $faltantes], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idCliente = gmgObtenerOCrearCliente($conexion, $rutCliente, $nombreCliente, $apellidoCliente, $emailCliente, $telefonoCliente);
    if ($idCliente <= 0) {
        throw new Exception('No se pudo registrar al cliente');
    }

    if ($tieneMetodoEntrega) {
        $stmtPedido = $conexion->prepare("INSERT INTO pedidos (id_cliente, id_codigo_descuento, estado, pago_estado, metodo_pago, metodo_entrega, descuento_monto, total) VALUES (?, ?, 'pendiente', 'pendiente', 'Mercado Pago', ?, ?, ?)");
        $stmtPedido->bind_param("iisdd", $idCliente, $idCodigoDescuento, $metodoEntrega, $montoDescuento, $totalPedido);
    } else {
        $stmtPedido = $conexion->prepare("INSERT INTO pedidos (id_cliente, id_codigo_descuento, estado, pago_estado, metodo_pago, descuento_monto, total) VALUES (?, ?, 'pendiente', 'pendiente', 'Mercado Pago', ?, ?)");
        $stmtPedido->bind_param("iidd", $idCliente, $idCodigoDescuento, $montoDescuento, $totalPedido);
    }
    if (!$stmtPedido->execute()) {
        throw new Exception('No se pudo registrar el pedido');
    }
    $pedidoId = (int)$conexion->insert_id;
    $stmtPedido->close();

    // Líneas del pedido: se guarda una "foto" del SKU, nombre y precio de catálogo
    // (sin descuento) para que el pedido histórico no cambie si el catálogo cambia.
    $stmtItem = $conexion->prepare("INSERT INTO pedido_detalle (id_pedido, id_repuesto, sku, nombre_repuesto, cantidad, precio_unitario) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($cantidadesPedidas as $productoId => $cantidad) {
        if (!isset($productosDb[$productoId])) {
            continue;
        }
        $skuProducto = $productosDb[$productoId]['sku'];
        $nombreProducto = $productosDb[$productoId]['nombre'];
        $precioUnitario = (float)$productosDb[$productoId]['precio'];
        $stmtItem->bind_param("iissid", $pedidoId, $productoId, $skuProducto, $nombreProducto, $cantidad, $precioUnitario);
        if (!$stmtItem->execute()) {
            throw new Exception('No se pudo registrar una línea del pedido');
        }
    }
    $stmtItem->close();

    $conexion->commit();
} catch (Throwable $e) {
    $conexion->rollback();
    $pedidoId = 0;
    $errorRegistro = true;
    error_log('[GMG Checkout] ' . $e->getMessage() . ' | ' . $conexion->error);
}

if ($errorRegistro || $pedidoId <= 0) {
    echo json_encode(['success' => false, 'message' => 'No pudimos registrar tu pedido. Inténtalo nuevamente en unos minutos.']);
    exit;
}

// Aviso al administrador: pedido nuevo esperando revisión y aprobación (F4-6).
// Se envía DESPUÉS de responder al navegador: el cliente no espera al servidor de correo
// (hasta 15 s si está lento). Si el correo falla, no afecta la compra.
register_shutdown_function(function () use ($conexion, $pedidoId) {
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } elseif (function_exists('litespeed_finish_request')) {
        litespeed_finish_request(); // Hostinger (LiteSpeed)
    }
    try {
        enviarCorreoNuevoPedido($conexion, $pedidoId);
    } catch (Throwable $e) {
        error_log('[GMG Checkout] No se pudo enviar el aviso de pedido nuevo: ' . $e->getMessage());
    }
});

// Preparar solicitud a Mercado Pago
$base_url = $baseSitio;

$payload = [
    "items" => $mpItems,
    "payer" => [
        "name" => $nombreCliente,
        "surname" => $apellidoCliente,
        "phone" => [
            "number" => $telefonoCliente
        ]
    ],
    "external_reference" => (string)$pedidoId,
    "back_urls" => [
        "success" => $base_url . "/mercadopago_return.php?status=success&pedido_id=" . $pedidoId,
        "failure" => $base_url . "/mercadopago_return.php?status=failure&pedido_id=" . $pedidoId,
        "pending" => $base_url . "/mercadopago_return.php?status=pending&pedido_id=" . $pedidoId
    ],
    "auto_return" => "approved",
    "payment_methods" => [
        "installments" => 6
    ],
    "statement_descriptor" => "GMG REPUESTOS"
];

$url = 'https://api.mercadopago.com/checkout/preferences';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => gmgCurlVerificarSsl(),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . MP_ACCESS_TOKEN
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error || $httpCode >= 400 || empty($response)) {
    // El detalle técnico queda solo en el registro de errores del servidor.
    error_log('[GMG Checkout] Error al contactar Mercado Pago (pedido ' . $pedidoId . ', HTTP ' . $httpCode . ') ' . $error . ' ' . mb_substr((string)$response, 0, 300));
    $stmtErr = $conexion->prepare("UPDATE pedidos SET estado = 'error' WHERE id_pedido = ?");
    $stmtErr->bind_param("i", $pedidoId);
    $stmtErr->execute();
    $stmtErr->close();
    echo json_encode(['success' => false, 'message' => 'No pudimos conectar con Mercado Pago en este momento. Inténtalo nuevamente en unos minutos.']);
    exit;
}

$decoded = json_decode($response, true);
if (isset($decoded['init_point'])) {
    if (isset($decoded['id'])) {
        $preferenceId = $decoded['id'];
        $stmtRef = $conexion->prepare("UPDATE pedidos SET referencia_pago = ? WHERE id_pedido = ?");
        $stmtRef->bind_param("si", $preferenceId, $pedidoId);
        $stmtRef->execute();
        $stmtRef->close();
    }
    echo json_encode([
        'success' => true,
        'init_point' => $decoded['init_point']
    ]);
} else {
    $stmtErr = $conexion->prepare("UPDATE pedidos SET estado = 'error' WHERE id_pedido = ?");
    $stmtErr->bind_param("i", $pedidoId);
    $stmtErr->execute();
    $stmtErr->close();
    echo json_encode(['success' => false, 'message' => 'Mercado Pago no respondió como esperábamos. Inténtalo nuevamente en unos minutos.']);
}
