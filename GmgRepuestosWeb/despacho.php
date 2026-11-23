<?php
require_once 'backend/config.php';
require_once 'backend/pagina_publica.php';

gmgCabeceraPublica([
    'titulo'      => 'Despacho',
    'descripcion' => 'Cómo se entrega tu compra en GMG Repuestos: la entrega se coordina por WhatsApp y el costo se informa antes de pagar.',
    'canonical'   => gmgUrlBase() . '/despacho.php',
]);
$modelos = gmgDatosModelosPublicos($conexion);
$despacho = gmgConocimiento($conexion, 'despacho');
?>

    <main class="container">
        <article class="pagina-info">
            <nav class="pagina-info-migas" aria-label="Ubicación"><a href="index.php">Inicio</a> / Despacho</nav>
            <h1>Despacho y entrega</h1>

            <?php if ($despacho !== ''): ?>
                <p class="pagina-info-destacado"><?php echo nl2br(gmgH($despacho)); ?></p>
            <?php endif; ?>

            <h2>Cómo funciona</h2>
            <ol>
                <li><strong>Eliges y pagas.</strong> Agregas los repuestos al carrito y pagas en línea con Mercado Pago.</li>
                <li><strong>Revisamos tu pedido.</strong> Confirmamos que tu compra esté correcta y que el repuesto sirva para tu vehículo.</li>
                <li><strong>Coordinamos la entrega.</strong> Te contactamos por WhatsApp para acordar cómo y cuándo recibes tu pedido.</li>
            </ol>

            <h2>Lo que debes saber antes de pagar</h2>
            <ul>
                <li>GMG Repuestos <strong>no realiza envíos propios</strong>: la entrega se acuerda directamente contigo.</li>
                <li>El total que pagas en la tienda corresponde <strong>solo a los repuestos</strong>. En el carrito aparece como "Despacho: se coordina por WhatsApp", sin cobro adicional en ese momento.</li>
                <li>Si la entrega requiere un traslado con costo, te lo informamos por WhatsApp <strong>antes de entregar el pedido</strong>.</li>
                <li>Cuando recibas tu pedido, revisa que el repuesto sea el que compraste. Si algo no está bien, mira nuestra política de <a href="cambios-y-devoluciones.php">cambios y devoluciones</a>.</li>
            </ul>

            <p><a class="btn-whatsapp" href="<?php echo gmgH(gmgEnlaceWhatsApp('Hola, quiero coordinar la entrega de mi pedido')); ?>" target="_blank" rel="noopener">Coordinar por WhatsApp</a></p>
        </article>
    </main>

<?php gmgPiePublico($conexion, ['modelos' => $modelos]); ?>
