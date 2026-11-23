<?php
/**
 * Página a la que vuelve el cliente después de pagar en Mercado Pago.
 *
 * Historia E4-H4: se registra cada intento de pago (pasarela, número de
 * transacción, monto, estado, código de autorización y fecha) en la tabla
 * "intentos_pago", y el resultado se confirma consultando directamente a
 * Mercado Pago, sin confiar solo en lo que venga escrito en la dirección.
 */
require_once 'backend/config.php';
require_once 'backend/pagos.php';
require_once 'backend/pagina_publica.php';

$statusUrl = isset($_GET['status']) ? (string)$_GET['status'] : 'unknown';
$payment_id = isset($_GET['payment_id']) ? preg_replace('/\D+/', '', (string)$_GET['payment_id']) : '';
$payment_type = isset($_GET['payment_type']) ? mb_substr((string)$_GET['payment_type'], 0, 40) : '';
$pedido_id = isset($_GET['pedido_id']) ? (int)$_GET['pedido_id'] : 0;

// ¿El pedido existe? Si no, no se registra nada.
$pedidoExiste = false;
if ($pedido_id > 0) {
    $stmtExiste = $conexion->prepare("SELECT 1 FROM pedidos WHERE id_pedido = ?");
    $stmtExiste->bind_param("i", $pedido_id);
    $stmtExiste->execute();
    $resExiste = $stmtExiste->get_result();
    $pedidoExiste = $resExiste && $resExiste->num_rows > 0;
    $stmtExiste->close();
}

// 1) Estado real del pago, consultado a Mercado Pago cuando hay número de pago.
$estadoMp = null;       // approved, pending, rejected...
$verificado = false;
$montoPago = null;
$codigoAutorizacion = null;

if ($payment_id !== '') {
    $pago = gmgConsultarPagoMercadoPago($payment_id);
    if ($pago !== null) {
        if ($pago['referencia_externa'] !== null && (int)$pago['referencia_externa'] !== $pedido_id) {
            // El número de pago pertenece a OTRO pedido: no se le cree.
            error_log('[GMG Pagos] El pago ' . $payment_id . ' no corresponde al pedido ' . $pedido_id);
            $estadoMp = 'pending';
        } else {
            $estadoMp = $pago['estado'];
            $verificado = true;
            $montoPago = $pago['monto'];
            $codigoAutorizacion = $pago['codigo_autorizacion'];
            if (!empty($pago['medio_pago'])) {
                $payment_type = $pago['medio_pago'];
            }
        }
    }
}

// 2) Si no se pudo consultar, se usa lo informado en la dirección de retorno,
// pero con una regla de seguridad: un pago "aprobado" que Mercado Pago no
// confirmó NUNCA se da por recibido (cualquiera podría escribir esa dirección
// a mano). Queda como pendiente de confirmación hasta que se revise en el panel.
if ($estadoMp === null) {
    if (in_array($statusUrl, ['approved', 'success', 'pending', 'in_process'], true)) {
        $estadoMp = 'pending';
    } else {
        $estadoMp = 'rejected';
    }
}

$pagoEstado = gmgTraducirEstadoPago($estadoMp);
$aprobado = ($pagoEstado === 'recibido');
$pendiente = ($pagoEstado === 'pendiente');

$sinPedido = !$pedidoExiste;

if ($aprobado) {
    $estado_mensaje = "¡Pago exitoso! Hemos recibido tu orden de compra y ahora está en revisión.";
} elseif ($pendiente) {
    $estado_mensaje = "Tu pago está pendiente de confirmación. Apenas Mercado Pago lo confirme, tu pedido seguirá su curso. Si pagaste y esto no cambia, escríbenos por WhatsApp indicando tu número de pedido.";
} elseif ($statusUrl === 'unknown') {
    $estado_mensaje = "Transacción no procesada";
} else {
    $estado_mensaje = "El pago fue rechazado o anulado. Inténtalo nuevamente.";
}

// 3) Registrar el resultado del PAGO en un campo aparte (pago_estado). El
// campo "estado" del pedido (Pendiente/Aprobado/Rechazado) es la revisión
// manual del administrador y a propósito NO se toca aquí: que Mercado
// Pago confirme el pago no aprueba el pedido solo, siempre requiere que
// el administrador lo revise y apruebe desde el panel (admin/pedidos.php).
// Un pedido con pago recibido nunca vuelve a "rechazado" por un intento posterior.
if ($pedidoExiste) {
    $referencia = $payment_id !== '' ? $payment_id : null;
    $stmt = $conexion->prepare("UPDATE pedidos SET pago_estado = IF(pago_estado = 'recibido', 'recibido', ?), referencia_pago = COALESCE(?, referencia_pago) WHERE id_pedido = ?");
    $stmt->bind_param("ssi", $pagoEstado, $referencia, $pedido_id);
    $stmt->execute();
    $stmt->close();

    // Cada intento queda registrado con sus datos de transacción.
    gmgRegistrarIntentoPago($conexion, $pedido_id, [
        'id_transaccion'      => $payment_id,
        'monto'               => $montoPago,
        'estado'              => $estadoMp,
        'medio_pago'          => $payment_type,
        'codigo_autorizacion' => $codigoAutorizacion,
        'verificado'          => $verificado,
    ]);
}

gmgCabeceraPublica([
    'titulo'      => 'Resultado del pago',
    'descripcion' => 'Resultado de tu pago en GMG Repuestos.',
    'robots'      => 'noindex, nofollow',
]);
$modelos = gmgDatosModelosPublicos($conexion);
?>

    <main class="container">
        <div class="result-container">
            <?php if ($sinPedido): ?>
                <div class="icon pending" aria-hidden="true">❔</div>
                <h1>No encontramos tu pedido</h1>
                <p>No pudimos relacionar esta visita con un pedido. Si hiciste un pago, escríbenos por WhatsApp con tu comprobante y lo revisamos.</p>
                <a href="<?php echo gmgH(gmgEnlaceWhatsApp('Hola, hice un pago y no veo mi pedido')); ?>" class="btn-volver btn-volver-whatsapp" target="_blank" rel="noopener">Escribir por WhatsApp</a>
                <a href="index.php" class="btn-volver">Volver a la tienda</a>
            <?php elseif ($aprobado): ?>
                <div class="icon success" aria-hidden="true">✔️</div>
                <h1>¡Compra Confirmada!</h1>
                <p><?php echo gmgH($estado_mensaje); ?></p>
                <div class="details">
                    <p><strong>Nº de pedido:</strong> #<?php echo (int)$pedido_id; ?></p>
                    <p><strong>Nº de Operación (Mercado Pago):</strong> <?php echo gmgH($payment_id); ?></p>
                    <p><strong>Método:</strong> <?php echo gmgH($payment_type); ?></p>
                </div>
                <p>Guarda tu número de pedido: puedes revisar su estado en cualquier momento en <a href="mi-pedido.php">Estado de mi pedido</a>. En breve nos comunicaremos contigo, o puedes hablarnos por WhatsApp para coordinar tu compra.</p>
                <a href="<?php echo gmgH(gmgEnlaceWhatsApp('Hola, acabo de comprar en la página (Pedido #' . (int)$pedido_id . ')')); ?>" class="btn-volver btn-volver-whatsapp" target="_blank" rel="noopener">Coordinar por WhatsApp</a>
            <?php elseif ($pendiente): ?>
                <div class="icon pending" aria-hidden="true">⏳</div>
                <h1>Pago Pendiente</h1>
                <p><strong>Nº de pedido:</strong> #<?php echo (int)$pedido_id; ?></p>
                <p><?php echo gmgH($estado_mensaje); ?></p>
                <a href="index.php" class="btn-volver">Volver a la tienda</a>
            <?php else: ?>
                <div class="icon error" aria-hidden="true">❌</div>
                <h1>Pago Fallido</h1>
                <?php if ($pedido_id > 0): ?><p><strong>Nº de pedido:</strong> #<?php echo (int)$pedido_id; ?></p><?php endif; ?>
                <p><?php echo gmgH($estado_mensaje); ?></p>
                <a href="index.php" class="btn-volver">Intentar nuevamente</a>
            <?php endif; ?>
        </div>
    </main>

<?php gmgPiePublico($conexion, ['modelos' => $modelos]); ?>
