<?php
require_once 'backend/config.php';
require_once 'backend/pagina_publica.php';

gmgCabeceraPublica([
    'titulo'      => 'Quiénes somos',
    'descripcion' => 'GMG Repuestos SpA es una tienda chilena especializada en repuestos para vehículos Suzuki. Conoce cómo trabajamos.',
    'canonical'   => gmgUrlBase() . '/quienes-somos.php',
]);
$modelos = gmgDatosModelosPublicos($conexion);
?>

    <main class="container">
        <article class="pagina-info">
            <nav class="pagina-info-migas" aria-label="Ubicación"><a href="index.php">Inicio</a> / Quiénes somos</nav>
            <h1>Quiénes somos</h1>

            <p><strong>GMG Repuestos SpA</strong> es una tienda chilena especializada en repuestos para vehículos Suzuki. Hoy atendemos a quienes tienen un <?php echo gmgH($modelos['modelosCortosTexto']); ?> y necesitan encontrar la pieza correcta sin perder tiempo.</p>

            <h2>Qué hacemos</h2>
            <p>Vendemos repuestos originales y compatibles para Suzuki. En cada producto indicamos para qué modelos y años sirve, para que compres la pieza que realmente le corresponde a tu auto.</p>

            <h2>Cómo trabajamos</h2>
            <ul>
                <li><strong>Compatibilidad clara.</strong> Cada repuesto muestra los modelos y años en que se puede usar. Si tienes dudas, te recomendamos confirmarlas con nosotros por WhatsApp antes de comprar.</li>
                <li><strong>Asistente Virtual.</strong> Puedes conversar con nuestro asistente para encontrar un repuesto, incluso enviando una foto de la pieza.</li>
                <li><strong>Pago seguro.</strong> Pagas en línea a través de Mercado Pago. GMG Repuestos no guarda los datos de tu tarjeta.</li>
                <li><strong>Atención directa.</strong> Una vez realizada la compra, coordinamos contigo la entrega por WhatsApp.</li>
            </ul>

            <h2>Contáctanos</h2>
            <p>Puedes escribirnos o visitarnos usando los datos de la página de <a href="contacto.php">Contacto</a>. Si quieres conocer tus derechos como cliente, revisa nuestras políticas de <a href="cambios-y-devoluciones.php">cambios y devoluciones</a>, los <a href="terminos-y-condiciones.php">términos y condiciones</a> y la <a href="politica-de-privacidad.php">política de privacidad</a>.</p>
        </article>
    </main>

<?php gmgPiePublico($conexion, ['modelos' => $modelos]); ?>
