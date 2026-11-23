<?php
/**
 * Estado de mi pedido: el cliente ve en qué va su compra con su número de
 * pedido y su RUT (no necesita crear una cuenta).
 *
 * Funciona con la base de datos v2 (pedidos + clientes + pedido_detalle).
 * Los datos se piden por formulario (POST) para que el RUT no quede escrito
 * en la dirección de la página ni en el historial del navegador.
 */
require_once 'backend/config.php';
require_once 'backend/datos.php';
require_once 'backend/pagina_publica.php';

$numeroPedido = '';
$rutIngresado = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numeroPedido = isset($_POST['numero']) && is_string($_POST['numero']) ? trim($_POST['numero']) : '';
    $rutIngresado = isset($_POST['rut']) && is_string($_POST['rut']) ? trim($_POST['rut']) : '';
}
$buscado = ($_SERVER['REQUEST_METHOD'] === 'POST');

$pedido = null;
$items = [];
$error = '';
$metodoEntrega = 'Coordinación por WhatsApp';

if ($buscado) {
    $idPedido = (int)$numeroPedido;
    if ($idPedido <= 0 || $rutIngresado === '') {
        $error = 'Ingresa tu número de pedido y tu RUT para buscar.';
    } elseif (gmgLimiteSuperado($conexion, 'mi_pedido', 10, 600)) {
        // Auditoría 15-11: sin límite se podían probar números y RUT sin freno (CP-23).
        $error = 'Hiciste muchas consultas seguidas. Espera unos minutos o escríbenos por WhatsApp.';
    } else {
        $stmt = $conexion->prepare("SELECT * FROM " . gmgSqlPedidos() . " p WHERE p.id = ? LIMIT 1");
        $stmt->bind_param("i", $idPedido);
        $stmt->execute();
        $res = $stmt->get_result();
        $filaPedido = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        // Comparación de RUT normalizada (sin puntos, guion ni mayúsculas). No se
        // distingue si falló el número o el RUT en el mensaje de error, para no
        // dar pistas de qué pedidos existen a quien solo esté adivinando.
        $rutNormalizado = gmgNormalizarRut($rutIngresado);
        if ($filaPedido && $rutNormalizado !== '' && gmgNormalizarRut($filaPedido['rut_cliente']) === $rutNormalizado) {
            $pedido = $filaPedido;
            $stmtItems = $conexion->prepare("SELECT * FROM " . gmgSqlPedidoItems() . " i WHERE i.pedido_id = ?");
            $stmtItems->bind_param("i", $idPedido);
            $stmtItems->execute();
            $resItems = $stmtItems->get_result();
            while ($resItems && ($fila = $resItems->fetch_assoc())) {
                $items[] = $fila;
            }
            $stmtItems->close();

            // Método de entrega (columna agregada por BD_v2/06_sprint1a4_tablas_nuevas.sql).
            $stmtEnt = $conexion->prepare("SELECT metodo_entrega FROM pedidos WHERE id_pedido = ?");
            if ($stmtEnt) {
                $stmtEnt->bind_param("i", $idPedido);
                $stmtEnt->execute();
                $resEnt = $stmtEnt->get_result();
                $filaEnt = $resEnt ? $resEnt->fetch_assoc() : null;
                if ($filaEnt && $filaEnt['metodo_entrega'] !== '') {
                    $metodoEntrega = $filaEnt['metodo_entrega'];
                }
                $stmtEnt->close();
            }
        } else {
            $error = 'No encontramos un pedido con ese número y RUT. Revisa que ambos estén correctos.';
        }
    }
}

function miPedidoBadge($valor, $tipo) {
    $clasesEstado = [
        'aprobado' => 'badge-aprobado',
        'pendiente' => 'badge-pendiente',
        'rechazado' => 'badge-rechazado',
        'error' => 'badge-error',
    ];
    $clasesPago = [
        'recibido' => 'badge-aprobado',
        'pendiente' => 'badge-pendiente',
        'rechazado' => 'badge-rechazado',
    ];
    $etiquetasPago = [
        'recibido' => 'Pago recibido',
        'pendiente' => 'Sin pago confirmado',
        'rechazado' => 'Pago rechazado',
    ];
    if ($tipo === 'pago') {
        $valor = $valor ?: 'pendiente';
        $clase = $clasesPago[$valor] ?? 'badge-pendiente';
        $texto = $etiquetasPago[$valor] ?? ucfirst($valor);
    } else {
        $clase = $clasesEstado[$valor] ?? 'badge-pendiente';
        $texto = ucfirst($valor);
    }
    return '<span class="badge ' . $clase . '">' . gmgH($texto) . '</span>';
}

gmgCabeceraPublica([
    'titulo'      => 'Estado de mi pedido',
    'descripcion' => 'Revisa en qué va tu pedido de GMG Repuestos con tu número de pedido y tu RUT.',
    'robots'      => 'noindex, nofollow',
    'css'         => ['css/admin.css'],
]);
$modelos = gmgDatosModelosPublicos($conexion);
?>

    <main class="container">
        <div class="seguimiento-container">
            <h1 class="seguimiento-titulo">Estado de mi pedido</h1>
            <p class="seguimiento-ayuda">Ingresa tu número de pedido (te lo mostramos al terminar tu compra) y tu RUT para ver en qué va.</p>

            <?php if ($error): ?>
                <div class="seguimiento-error" role="alert"><?php echo gmgH($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="mi-pedido.php">
                <div class="seguimiento-form-row">
                    <div>
                        <label for="numero">Número de pedido</label>
                        <input type="number" min="1" id="numero" name="numero" placeholder="Ej: 5" value="<?php echo gmgH($numeroPedido); ?>" required>
                    </div>
                    <div>
                        <label for="rut">RUT</label>
                        <input type="text" id="rut" name="rut" placeholder="Ej: 12.345.678-9" value="<?php echo gmgH($rutIngresado); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn-admin seguimiento-boton">Buscar pedido</button>
            </form>

            <?php if ($pedido): ?>
                <div class="seguimiento-detalle">
                    <p><strong>Pedido #<?php echo (int)$pedido['id']; ?></strong> (<?php echo gmgH(date('d-m-Y H:i', strtotime($pedido['fecha']))); ?>)</p>
                    <p>
                        Estado del pedido: <?php echo miPedidoBadge($pedido['estado'], 'estado'); ?>
                        &nbsp; Pago: <?php echo miPedidoBadge($pedido['pago_estado'] ?? 'pendiente', 'pago'); ?>
                    </p>
                    <p class="seguimiento-ayuda">Entrega: <?php echo gmgH($metodoEntrega); ?></p>

                    <div class="seguimiento-tabla-wrap">
                        <table class="seguimiento-items-table">
                            <thead>
                                <tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo gmgH($item['nombre_producto']); ?></td>
                                        <td><?php echo (int)$item['cantidad']; ?></td>
                                        <td>$<?php echo number_format($item['precio_unitario'], 0, ',', '.'); ?></td>
                                        <td>$<?php echo number_format($item['precio_unitario'] * $item['cantidad'], 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($items)): ?>
                                    <tr><td colspan="4" class="seguimiento-vacio">Sin productos registrados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <p class="seguimiento-total"><strong>Total: $<?php echo number_format($pedido['total'], 0, ',', '.'); ?></strong></p>

                    <?php if ($pedido['estado'] === 'pendiente'): ?>
                        <p class="seguimiento-ayuda">Tu pedido está siendo revisado por nuestro equipo. Te avisaremos por correo apenas cambie de estado.</p>
                    <?php elseif ($pedido['estado'] === 'rechazado'): ?>
                        <p class="seguimiento-ayuda">Este pedido fue rechazado. Si tienes dudas, escríbenos por WhatsApp.</p>
                    <?php endif; ?>

                    <a href="<?php echo gmgH(gmgEnlaceWhatsApp('Hola, quiero consultar por mi pedido #' . (int)$pedido['id'])); ?>" class="btn-whatsapp seguimiento-whatsapp" target="_blank" rel="noopener">Consultar por WhatsApp</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

<?php gmgPiePublico($conexion, ['modelos' => $modelos]); ?>
