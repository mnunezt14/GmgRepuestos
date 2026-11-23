<?php
require_once 'backend/config.php';
require_once 'backend/pagina_publica.php';

gmgCabeceraPublica([
    'titulo'      => 'Términos y condiciones',
    'descripcion' => 'Términos y condiciones de uso y de compra en la tienda en línea de GMG Repuestos SpA.',
    'canonical'   => gmgUrlBase() . '/terminos-y-condiciones.php',
]);
$modelos = gmgDatosModelosPublicos($conexion);
?>

    <main class="container">
        <article class="pagina-info">
            <nav class="pagina-info-migas" aria-label="Ubicación"><a href="index.php">Inicio</a> / Términos y condiciones</nav>
            <h1>Términos y condiciones</h1>
            <p class="pagina-info-fecha">Última actualización: octubre de 2026</p>
            <p>Estos términos explican cómo funciona la tienda en línea de <strong>GMG Repuestos SpA</strong> y qué esperamos de quienes la usan. Al hacer una compra en este sitio, declaras que los leíste y los aceptas.</p>

            <h2>1. Productos y precios</h2>
            <ul>
                <li>Los precios están en pesos chilenos (CLP) y son los que se muestran en el sitio al momento de pagar.</li>
                <li>La disponibilidad depende del stock. Si un producto se agota entre que lo agregas al carrito y pagas, te avisaremos para ofrecerte una alternativa o la devolución de tu dinero.</li>
                <li>Si hay un error evidente en un precio, podemos corregirlo y avisarte antes de confirmar tu compra.</li>
            </ul>

            <h2>2. Compatibilidad con tu vehículo</h2>
            <p>Cada repuesto indica los modelos y años de vehículo para los que sirve. Es importante que <strong>confirmes la compatibilidad con nosotros por WhatsApp antes de comprar</strong>, para evitar errores. Si compraste un repuesto que no corresponde a lo que informamos, puedes pedir un cambio según nuestra <a href="cambios-y-devoluciones.php">política de cambios y devoluciones</a>.</p>

            <h2>3. Cómo se realiza una compra</h2>
            <ol>
                <li>Agregas productos al carrito (o usas "Comprar ahora") e ingresas tus datos de contacto.</li>
                <li>Pagas en línea a través de <strong>Mercado Pago</strong>. GMG Repuestos no recibe ni guarda los datos de tu tarjeta.</li>
                <li>Tu compra queda registrada como un pedido con un número único. Nuestro equipo lo revisa y, si todo está correcto, lo aprueba y te avisamos por correo.</li>
                <li>Coordinamos contigo la entrega por WhatsApp (ver <a href="despacho.php">Despacho</a>).</li>
            </ol>

            <h2>4. Cambios, devoluciones, garantía y retracto</h2>
            <p>Tienes los derechos que entrega la Ley N° 19.496 del Consumidor, entre ellos la garantía legal y el derecho de retracto en compras por Internet. Los explicamos en la página de <a href="cambios-y-devoluciones.php">Cambios y devoluciones</a>.</p>

            <h2>5. Asistente Virtual</h2>
            <p>El Asistente Virtual usa inteligencia artificial para ayudarte a buscar repuestos. Aunque busca en nuestro catálogo real, <strong>puede equivocarse</strong>. Antes de comprar, confirma siempre la compatibilidad de la pieza con tu vehículo. Si el asistente no tiene un dato, te lo dirá y podrás continuar por WhatsApp.</p>

            <h2>6. Uso del sitio</h2>
            <p>Te pedimos usar el sitio solo para comprar o consultar repuestos, entregar datos verdaderos y no intentar dañar su funcionamiento. Los textos, imágenes y el diseño del sitio pertenecen a GMG Repuestos SpA o se usan con autorización.</p>

            <h2>7. Tus datos</h2>
            <p>Cómo cuidamos tus datos personales se explica en la <a href="politica-de-privacidad.php">Política de privacidad</a>.</p>

            <h2>8. Cambios a estos términos</h2>
            <p>Podemos actualizar estos términos. La versión vigente es la publicada en esta página y se aplica a las compras realizadas desde su fecha de actualización.</p>

            <h2>9. Ley aplicable y contacto</h2>
            <p>Estos términos se rigen por las leyes de la República de Chile, en especial la Ley N° 19.496. Para cualquier consulta, escríbenos desde la página de <a href="contacto.php">Contacto</a>.</p>
        </article>
    </main>

<?php gmgPiePublico($conexion, ['modelos' => $modelos]); ?>
