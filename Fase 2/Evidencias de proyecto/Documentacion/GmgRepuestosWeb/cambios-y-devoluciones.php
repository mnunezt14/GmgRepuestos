<?php
require_once 'backend/config.php';
require_once 'backend/pagina_publica.php';

gmgCabeceraPublica([
    'titulo'      => 'Cambios y devoluciones',
    'descripcion' => 'Política de cambios y devoluciones, garantía legal y derecho de retracto de GMG Repuestos, según la Ley 19.496 del Consumidor.',
    'canonical'   => gmgUrlBase() . '/cambios-y-devoluciones.php',
]);
$modelos = gmgDatosModelosPublicos($conexion);
?>

    <main class="container">
        <article class="pagina-info">
            <nav class="pagina-info-migas" aria-label="Ubicación"><a href="index.php">Inicio</a> / Cambios y devoluciones</nav>
            <h1>Cambios y devoluciones</h1>
            <p>En esta página explicamos tus derechos como cliente, de acuerdo con la <strong>Ley N° 19.496 sobre Protección de los Derechos de los Consumidores</strong>. Si necesitas ayuda para ejercerlos, escríbenos por <a href="<?php echo gmgH(gmgEnlaceWhatsApp('Hola, quiero hacer una consulta sobre un cambio o devolución')); ?>" target="_blank" rel="noopener">WhatsApp</a> indicando tu número de pedido.</p>

            <ul class="pagina-info-indice">
                <li><a href="#garantia-legal">Garantía legal</a></li>
                <li><a href="#derecho-de-retracto">Derecho de retracto</a></li>
                <li><a href="#como-pedir-un-cambio">Cómo pedir un cambio o devolución</a></li>
            </ul>

            <h2 id="garantia-legal">Garantía legal</h2>
            <p>Todos los repuestos que vendemos tienen garantía legal. Si el producto llega con una falla, es distinto a lo que se informó en la tienda o no sirve para el uso que se indicó, tienes <strong>6 meses desde que lo recibes</strong> para pedir, a tu elección:</p>
            <ul>
                <li>la <strong>reparación</strong> gratuita del producto,</li>
                <li>el <strong>cambio</strong> por otro igual, o</li>
                <li>la <strong>devolución del dinero</strong>.</li>
            </ul>
            <p>La garantía legal no cubre fallas causadas por una mala instalación, mal uso o desgaste normal de la pieza.</p>

            <h2 id="derecho-de-retracto">Derecho de retracto</h2>
            <p>Como la compra se hace por Internet, tienes derecho a <strong>arrepentirte de tu compra sin dar explicaciones</strong>, dentro de los <strong>10 días siguientes a la fecha en que recibes el producto</strong>.</p>
            <p>Para que podamos aceptar el retracto, el repuesto debe devolverse en las mismas condiciones en que lo recibiste: sin instalar ni usar, y con su empaque y accesorios originales. Te devolvemos el dinero por el mismo medio de pago usado en la compra.</p>

            <h2 id="como-pedir-un-cambio">Cómo pedir un cambio o devolución</h2>
            <ol>
                <li>Escríbenos por WhatsApp o al correo de la página de <a href="contacto.php">Contacto</a>, indicando tu <strong>número de pedido</strong> y qué necesitas (cambio, reparación o devolución).</li>
                <li>Cuéntanos el motivo y, si el producto tiene una falla, envíanos una foto.</li>
                <li>Coordinamos contigo cómo se entrega el producto y cuándo se realiza el cambio o la devolución del dinero.</li>
            </ol>
            <p>También puedes recurrir al <strong>SERNAC</strong> (www.sernac.cl) si consideras que tus derechos como consumidor no han sido respetados.</p>
        </article>
    </main>

<?php gmgPiePublico($conexion, ['modelos' => $modelos]); ?>
