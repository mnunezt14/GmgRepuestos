<?php
/**
 * Sistema de avisos por correo - GMG Repuestos
 *
 * Dos funciones principales, pensadas para llamarse desde checkout.php,
 * mercadopago_return.php y admin/pedidos.php:
 *
 *   enviarCorreoNuevoPedido($conexion, $pedidoId)
 *     -> avisa al ADMINISTRADOR que llegó un pedido nuevo, esperando
 *        revisión y aprobación.
 *
 *   enviarCorreoActualizacionPedido($conexion, $pedidoId, $estadoNuevo)
 *     -> avisa al CLIENTE que el estado de su pedido cambió (aprobado,
 *        rechazado, pendiente, etc.).
 *
 * Ambas funciones son "a prueba de fallos": si el correo no se pudo
 * enviar (credenciales SMTP mal puestas, servidor caído, EMAIL_ENABLED
 * en false, cliente sin correo registrado, etc.) simplemente no hacen
 * nada y devuelven ['success' => false, 'error' => '...'] — NUNCA lanzan
 * una excepción hacia quien las llama, para no romper el checkout ni el
 * panel de administrador por un problema de correo.
 */

require_once __DIR__ . '/email_config.php';
require_once __DIR__ . '/SmtpMailer.php';
require_once __DIR__ . '/datos.php';

/**
 * Envía un correo usando la configuración SMTP de email_config.php.
 * Punto único de envío usado por las dos funciones de más abajo.
 */
function emailEnviar($destinatarioEmail, $destinatarioNombre, $asunto, $htmlBody) {
    if (!EMAIL_ENABLED) {
        return ['success' => false, 'error' => 'EMAIL_ENABLED está en false en backend/email_config.php'];
    }
    if (empty($destinatarioEmail) || !filter_var($destinatarioEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Correo de destino inválido o vacío'];
    }
    // El remitente es la misma casilla con que se inicia sesión en el SMTP: Hostinger rechaza enviar
    // "desde" otra dirección (antes era Ventas@ fijo aunque SMTP_USER fuera otra casilla).
    $remitente = filter_var(SMTP_USER, FILTER_VALIDATE_EMAIL) ? SMTP_USER : SMTP_FROM_EMAIL;
    try {
        $mailer = new SmtpMailer(SMTP_HOST, SMTP_PORT, SMTP_SECURE, SMTP_USER, SMTP_PASS);
        $resultado = $mailer->enviar($destinatarioEmail, $destinatarioNombre, $asunto, $htmlBody, $remitente, SMTP_FROM_NAME);
    } catch (Exception $e) {
        $resultado = ['success' => false, 'error' => $e->getMessage()];
    }
    if (empty($resultado['success'])) {
        error_log('[GMG Correo] No se pudo enviar "' . $asunto . '": ' . ($resultado['error'] ?? 'sin detalle'));
        gmgErrorCorreo((string)($resultado['error'] ?? 'sin detalle'));
    }
    return $resultado;
}

/** Último error del servidor de correo en esta petición ('' si no hubo): el panel lo muestra en vez de callarlo. */
function gmgErrorCorreo($nuevo = null) {
    static $ultimo = '';
    if ($nuevo !== null) {
        $ultimo = $nuevo;
    }
    return $ultimo;
}

/**
 * Envuelve el contenido interno en la plantilla visual de GMG Repuestos
 * (negro/rojo, igual a la identidad del sitio), lista para enviarse por
 * correo. $contenidoHtml es el HTML del cuerpo del mensaje (ya con sus
 * propios <p>, <table>, etc.).
 */
function emailPlantillaBase($contenidoHtml) {
    $anio = date('Y');
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GMG Repuestos</title>
</head>
<body style="margin:0; padding:0; background-color:#F5F5F5; font-family: Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F5F5F5; padding: 24px 0;">
<tr>
<td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#FFFFFF; max-width:600px; width:100%; border-radius:8px; overflow:hidden;">
<tr>
<td style="background-color:#111111; padding: 22px 30px;">
<span style="font-size:1.4rem; font-weight:bold; color:#FFFFFF; letter-spacing:0.5px;">GMG <span style="color:#D32F2F;">Repuestos</span></span>
</td>
</tr>
<tr>
<td style="padding: 30px; color:#333333; font-size:0.95rem; line-height:1.55;">
{$contenidoHtml}
</td>
</tr>
<tr>
<td style="background-color:#F5F5F5; padding: 16px 30px; text-align:center; color:#999999; font-size:0.75rem;">
GMG Repuestos SpA &middot; {$anio} &middot; Este es un correo automático, por favor no respondas directamente a este mensaje.
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
HTML;
}

/**
 * Obtiene el pedido y sus items desde la base de datos. Devuelve null si
 * no existe.
 */
function emailObtenerPedidoConItems($conexion, $pedidoId) {
    $stmt = $conexion->prepare("SELECT * FROM " . gmgSqlPedidos() . " pv WHERE pv.id = ? LIMIT 1");
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $res = $stmt->get_result();
    $pedido = $res->fetch_assoc();
    $stmt->close();

    if (!$pedido) {
        return null;
    }

    $items = [];
    $stmtItems = $conexion->prepare("SELECT * FROM " . gmgSqlPedidoItems() . " pi WHERE pi.pedido_id = ?");
    $stmtItems->bind_param("i", $pedidoId);
    $stmtItems->execute();
    $resItems = $stmtItems->get_result();
    while ($fila = $resItems->fetch_assoc()) {
        $items[] = $fila;
    }
    $stmtItems->close();

    $pedido['items'] = $items;
    return $pedido;
}

/**
 * Arma la tabla HTML con el detalle de productos de un pedido, reutilizada
 * tanto en el correo al administrador como en el correo al cliente.
 */
function emailTablaItems(array $items) {
    if (empty($items)) {
        return '<p style="color:#888;">Sin productos registrados.</p>';
    }
    $filas = '';
    foreach ($items as $item) {
        $nombre = htmlspecialchars($item['nombre_producto']);
        $cantidad = (int)$item['cantidad'];
        $precioUnit = number_format((float)$item['precio_unitario'], 0, ',', '.');
        $subtotal = number_format((float)$item['precio_unitario'] * $cantidad, 0, ',', '.');
        $filas .= "<tr><td style=\"padding:8px 0; border-bottom:1px solid #eee;\">{$nombre}</td><td style=\"padding:8px 0; border-bottom:1px solid #eee; text-align:center;\">{$cantidad}</td><td style=\"padding:8px 0; border-bottom:1px solid #eee; text-align:right;\">\${$precioUnit}</td><td style=\"padding:8px 0; border-bottom:1px solid #eee; text-align:right;\">\${$subtotal}</td></tr>";
    }
    return <<<HTML
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 14px 0; font-size:0.88rem;">
<tr style="color:#888;">
<td style="padding-bottom:6px; border-bottom:2px solid #111;">Producto</td>
<td style="padding-bottom:6px; border-bottom:2px solid #111; text-align:center;">Cant.</td>
<td style="padding-bottom:6px; border-bottom:2px solid #111; text-align:right;">Precio</td>
<td style="padding-bottom:6px; border-bottom:2px solid #111; text-align:right;">Subtotal</td>
</tr>
{$filas}
</table>
HTML;
}

/**
 * AVISO AL ADMINISTRADOR: pedido nuevo esperando revisión y aprobación.
 * Llamar justo después de insertar el pedido y sus items en la base de
 * datos (ej. al final de checkout.php).
 */
function enviarCorreoNuevoPedido($conexion, $pedidoId) {
    $pedido = emailObtenerPedidoConItems($conexion, $pedidoId);
    if (!$pedido) {
        return ['success' => false, 'error' => "Pedido #$pedidoId no encontrado"];
    }

    $total = number_format((float)$pedido['total'], 0, ',', '.');
    $nombreCliente = htmlspecialchars(trim(($pedido['nombre_cliente'] ?? '') . ' ' . ($pedido['apellido_cliente'] ?? '')));
    $rut = htmlspecialchars($pedido['rut_cliente'] ?: '—');
    $telefono = htmlspecialchars($pedido['telefono_cliente'] ?: '—');
    $emailCliente = htmlspecialchars($pedido['email_cliente'] ?: '—');
    $metodoPago = htmlspecialchars($pedido['metodo_pago'] ?: '—');
    $tablaItems = emailTablaItems($pedido['items']);
    $linkPanel = htmlspecialchars(ADMIN_PANEL_URL);

    $contenido = <<<HTML
<h2 style="color:#111; margin-top:0;">Nuevo pedido esperando revisión</h2>
<p>Se registró el pedido <strong>#{$pedidoId}</strong> y quedó con estado <strong style="color:#D32F2F;">pendiente</strong>, esperando tu revisión y aprobación.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 16px 0; font-size:0.9rem;">
<tr><td style="padding:4px 0; color:#888; width:140px;">Cliente:</td><td style="padding:4px 0;"><strong>{$nombreCliente}</strong></td></tr>
<tr><td style="padding:4px 0; color:#888;">RUT:</td><td style="padding:4px 0;">{$rut}</td></tr>
<tr><td style="padding:4px 0; color:#888;">Teléfono:</td><td style="padding:4px 0;">{$telefono}</td></tr>
<tr><td style="padding:4px 0; color:#888;">Correo:</td><td style="padding:4px 0;">{$emailCliente}</td></tr>
<tr><td style="padding:4px 0; color:#888;">Método de pago:</td><td style="padding:4px 0;">{$metodoPago}</td></tr>
</table>
{$tablaItems}
<p style="text-align:right; font-size:1.1rem; margin-top:10px;"><strong>Total: \${$total}</strong></p>
<p style="margin-top:24px;"><a href="{$linkPanel}" style="display:inline-block; background:#111111; color:#fff; padding:10px 20px; border-radius:4px; text-decoration:none; font-weight:bold;">Revisar pedido en el panel</a></p>
HTML;

    $html = emailPlantillaBase($contenido);
    $asunto = "Nuevo pedido #{$pedidoId} — esperando revisión";

    return emailEnviar(ADMIN_NOTIFICATION_EMAIL, 'GMG Repuestos - Panel', $asunto, $html);
}

/**
 * AVISO AL CLIENTE: cambio de estado de su pedido (aprobado, rechazado,
 * pendiente, error). Llamar justo después de actualizar el estado en la
 * base de datos (mercadopago_return.php y admin/pedidos.php).
 */
function enviarCorreoActualizacionPedido($conexion, $pedidoId, $estadoNuevo) {
    $pedido = emailObtenerPedidoConItems($conexion, $pedidoId);
    if (!$pedido) {
        return ['success' => false, 'error' => "Pedido #$pedidoId no encontrado"];
    }
    if (empty($pedido['email_cliente'])) {
        return ['success' => false, 'error' => "El pedido #$pedidoId no tiene correo de cliente registrado"];
    }

    $nombreCliente = trim(($pedido['nombre_cliente'] ?? '') . ' ' . ($pedido['apellido_cliente'] ?? ''));
    $total = number_format((float)$pedido['total'], 0, ',', '.');
    $tablaItems = emailTablaItems($pedido['items']);
    $whatsapp = htmlspecialchars(function_exists('gmgNumeroWhatsApp') ? gmgNumeroWhatsApp() : EMAIL_WHATSAPP_NUMBER);
    $linkWhatsapp = "https://wa.me/{$whatsapp}?text=" . rawurlencode("Hola, quiero coordinar mi pedido #{$pedidoId}");

    switch ($estadoNuevo) {
        case 'aprobado':
            $asunto = "Tu pedido #{$pedidoId} fue aprobado";
            $tituloColor = '#25D366';
            $titulo = '¡Tu pedido fue aprobado!';
            $mensaje = 'Hemos confirmado el pago de tu pedido. El siguiente paso es coordinar la entrega o retiro directamente por WhatsApp.';
            break;
        case 'rechazado':
            $asunto = "Tu pedido #{$pedidoId} fue rechazado";
            $tituloColor = '#D32F2F';
            $titulo = 'Tu pedido fue rechazado';
            $mensaje = 'El pago de tu pedido no pudo ser procesado o fue rechazado. Si crees que esto es un error, o quieres intentarlo nuevamente, escríbenos por WhatsApp.';
            break;
        case 'pendiente':
            $asunto = "Tu pedido #{$pedidoId} está pendiente";
            $tituloColor = '#FF9800';
            $titulo = 'Tu pedido está pendiente';
            $mensaje = 'Tu pedido quedó pendiente de confirmación de pago. Te avisaremos apenas cambie de estado.';
            break;
        case 'error':
            $asunto = "Hubo un problema con tu pedido #{$pedidoId}";
            $tituloColor = '#D32F2F';
            $titulo = 'Hubo un problema con tu pedido';
            $mensaje = 'Ocurrió un problema técnico al procesar tu pedido. Escríbenos por WhatsApp y lo resolvemos contigo directamente.';
            break;
        default:
            $asunto = "Actualización de tu pedido #{$pedidoId}";
            $tituloColor = '#111111';
            $titulo = 'Actualización de tu pedido';
            $mensaje = 'El estado de tu pedido cambió a: ' . htmlspecialchars($estadoNuevo) . '.';
    }

    $contenido = <<<HTML
<h2 style="color:{$tituloColor}; margin-top:0;">{$titulo}</h2>
<p>Hola {$nombreCliente}, este es un aviso sobre tu pedido <strong>#{$pedidoId}</strong>.</p>
<p>{$mensaje}</p>
{$tablaItems}
<p style="text-align:right; font-size:1.1rem; margin-top:10px;"><strong>Total: \${$total}</strong></p>
<p style="margin-top:24px;"><a href="{$linkWhatsapp}" style="display:inline-block; background:#25D366; color:#fff; padding:10px 20px; border-radius:4px; text-decoration:none; font-weight:bold;">Coordinar por WhatsApp</a></p>
HTML;

    $html = emailPlantillaBase($contenido);

    return emailEnviar($pedido['email_cliente'], $nombreCliente, $asunto, $html);
}

/**
 * Correo de reposición (F3-9, E7-H3, RF-064): cuando un repuesto vuelve a tener stock, avisa
 * por correo a quienes lo pidieron (avisos_stock pendientes cuyo contacto es un correo) y los
 * marca como notificados. Los avisos por celular quedan pendientes para escribirles por
 * WhatsApp desde el panel "Avisos de stock". Si un correo falla, ese aviso queda pendiente.
 * $enviar: función de envío (por defecto emailEnviar; las pruebas pasan una falsa).
 * Devuelve cuántos correos se enviaron.
 */
function gmgNotificarReposicion($conexion, $idRepuesto, ?callable $enviar = null) {
    $enviar = $enviar ?? 'emailEnviar';
    $stmt = $conexion->prepare("SELECT nombre, sku, precio, stock FROM repuestos WHERE id_repuesto = ? AND activo = 1");
    $stmt->bind_param('i', $idRepuesto);
    $stmt->execute();
    $repuesto = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$repuesto || (int)$repuesto['stock'] <= 0) {
        return 0;
    }

    $stmt = $conexion->prepare("SELECT id_aviso, contacto FROM avisos_stock WHERE id_repuesto = ? AND notificado = 0 AND contacto LIKE '%@%'");
    $stmt->bind_param('i', $idRepuesto);
    $stmt->execute();
    $avisos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if (!$avisos) {
        return 0;
    }

    // Dirección pública del sitio (desde el panel, gmgUrlBase() termina en /admin).
    $base = preg_replace('#/admin$#', '', gmgUrlBase());
    $nombre = htmlspecialchars($repuesto['nombre'], ENT_QUOTES, 'UTF-8');
    $sku = htmlspecialchars($repuesto['sku'], ENT_QUOTES, 'UTF-8');
    $precio = number_format((float)$repuesto['precio'], 0, ',', '.');
    $enlace = htmlspecialchars($base . '/producto.php?id=' . (int)$idRepuesto, ENT_QUOTES, 'UTF-8');
    $whatsapp = htmlspecialchars('https://wa.me/' . preg_replace('/\D/', '', function_exists('gmgNumeroWhatsApp') ? gmgNumeroWhatsApp() : EMAIL_WHATSAPP_NUMBER) . '?text='
        . rawurlencode('Hola, me avisaron que llegó el repuesto ' . $repuesto['nombre'] . ' (SKU ' . $repuesto['sku'] . ')'), ENT_QUOTES, 'UTF-8');
    $contenido = <<<HTML
<h2 style="color:#111; margin-top:0;">¡Volvió a llegar tu repuesto!</h2>
<p>Hola, nos pediste que te avisáramos cuando tuviéramos stock de <strong>{$nombre}</strong> (SKU {$sku}). Ya está disponible a <strong>\${$precio}</strong>.</p>
<p>Las unidades son limitadas, así que te recomendamos asegurarlo pronto.</p>
<p style="margin-top:24px;"><a href="{$enlace}" style="display:inline-block; background:#D32F2F; color:#fff; padding:10px 20px; border-radius:4px; text-decoration:none; font-weight:bold;">Ver el repuesto</a>
&nbsp;<a href="{$whatsapp}" style="display:inline-block; background:#25D366; color:#0B3D1E; padding:10px 20px; border-radius:4px; text-decoration:none; font-weight:bold;">Consultar por WhatsApp</a></p>
<p style="color:#888; font-size:0.8rem;">Recibes este correo porque dejaste tu contacto en GMG Repuestos para este aviso. No te volveremos a escribir por este repuesto.</p>
HTML;
    $html = emailPlantillaBase($contenido);
    $asunto = 'Ya tenemos stock: ' . $repuesto['nombre'];

    $enviados = 0;
    $marcar = $conexion->prepare("UPDATE avisos_stock SET notificado = 1 WHERE id_aviso = ?");
    foreach ($avisos as $aviso) {
        $resultado = $enviar($aviso['contacto'], '', $asunto, $html);
        if (!empty($resultado['success'])) {
            $idAviso = (int)$aviso['id_aviso'];
            $marcar->bind_param('i', $idAviso);
            $marcar->execute();
            $enviados++;
        } else {
            error_log('[GMG Reposición] No se pudo avisar el aviso #' . (int)$aviso['id_aviso'] . ': ' . ($resultado['error'] ?? 'sin detalle'));
        }
    }
    $marcar->close();
    return $enviados;
}

/**
 * Aviso interno al aprobar un pedido (F4-6, E3-H8, E7-H1, RF-026, RF-062): llega al correo de la tienda
 * con el cliente, su contacto y los productos, para coordinar la entrega por WhatsApp. Al crearse el
 * pedido (pendiente) solo se avisa "pedido nuevo"; este correo es el de "ya se puede entregar".
 */
function enviarCorreoPedidoAprobadoInterno($conexion, $pedidoId, ?callable $enviar = null) {
    $enviar = $enviar ?? 'emailEnviar'; // las pruebas pasan uno falso
    $pedido = emailObtenerPedidoConItems($conexion, $pedidoId);
    if (!$pedido) {
        return ['success' => false, 'error' => "Pedido #$pedidoId no encontrado"];
    }
    $cliente = htmlspecialchars(trim(($pedido['nombre_cliente'] ?? '') . ' ' . ($pedido['apellido_cliente'] ?? '')), ENT_QUOTES, 'UTF-8');
    $telefono = htmlspecialchars($pedido['telefono_cliente'] ?: '—', ENT_QUOTES, 'UTF-8');
    $correo = htmlspecialchars($pedido['email_cliente'] ?: '—', ENT_QUOTES, 'UTF-8');
    $total = number_format((float)$pedido['total'], 0, ',', '.');
    $tablaItems = emailTablaItems($pedido['items']);
    $digitos = preg_replace('/\D/', '', (string)($pedido['telefono_cliente'] ?? ''));
    $enlaceCliente = $digitos !== '' ? 'https://wa.me/' . $digitos . '?text=' . rawurlencode("Hola, te escribimos de GMG Repuestos por tu pedido #{$pedidoId} para coordinar la entrega.") : '';
    $boton = $enlaceCliente !== '' ? '<p style="margin-top:20px;"><a href="' . htmlspecialchars($enlaceCliente, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block; background:#25D366; color:#0B3D1E; padding:10px 20px; border-radius:4px; text-decoration:none; font-weight:bold;">Escribir al cliente por WhatsApp</a></p>' : '';
    $contenido = <<<HTML
<h2 style="color:#111; margin-top:0;">Pedido #{$pedidoId} aprobado: coordinar la entrega</h2>
<p>Cliente: <strong>{$cliente}</strong> · Teléfono: {$telefono} · Correo: {$correo}</p>
{$tablaItems}
<p style="text-align:right; font-size:1.1rem;"><strong>Total: \${$total}</strong></p>
<p>El stock de estos productos ya se descontó al aprobar.</p>
{$boton}
HTML;
    return $enviar(ADMIN_NOTIFICATION_EMAIL, 'GMG Repuestos - Panel', "Pedido #{$pedidoId} aprobado: coordinar entrega", emailPlantillaBase($contenido));
}
