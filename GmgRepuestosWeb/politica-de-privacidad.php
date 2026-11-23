<?php
require_once 'backend/config.php';
require_once 'backend/pagina_publica.php';

gmgCabeceraPublica([
    'titulo'      => 'Política de privacidad',
    'descripcion' => 'Qué datos personales recopila GMG Repuestos SpA, para qué los usa y cómo puedes ejercer tus derechos.',
    'canonical'   => gmgUrlBase() . '/politica-de-privacidad.php',
]);
$modelos = gmgDatosModelosPublicos($conexion);
$correoContacto = gmgConocimiento($conexion, 'email');
?>

    <main class="container">
        <article class="pagina-info">
            <nav class="pagina-info-migas" aria-label="Ubicación"><a href="index.php">Inicio</a> / Política de privacidad</nav>
            <h1>Política de privacidad</h1>
            <p class="pagina-info-fecha">Última actualización: octubre de 2026</p>
            <p>En <strong>GMG Repuestos SpA</strong> cuidamos tus datos personales. Aquí te contamos, en simple, qué datos pedimos, para qué los usamos y qué derechos tienes, conforme a la Ley N° 19.628 sobre protección de la vida privada y demás normas aplicables en Chile.</p>

            <h2>Qué datos recopilamos</h2>
            <ul>
                <li><strong>Datos de tu compra:</strong> nombre, apellido, RUT, teléfono y correo electrónico, y el detalle de los productos que compras.</li>
                <li><strong>Conversaciones con el Asistente Virtual:</strong> los mensajes que escribes y las fotos de piezas que decides enviar.</li>
                <li><strong>Datos de navegación en tu dispositivo:</strong> guardamos en tu navegador tu carrito y tu conversación con el asistente, para que no los pierdas al cambiar de página.</li>
            </ul>
            <p>No guardamos los datos de tu tarjeta: el pago lo procesa Mercado Pago.</p>

            <h2>Para qué usamos tus datos</h2>
            <ul>
                <li>Registrar y gestionar tu pedido, y avisarte por correo cómo va.</li>
                <li>Coordinar contigo la entrega por WhatsApp o teléfono.</li>
                <li>Atender tus consultas, mejorar las respuestas del asistente y resolver problemas con tu compra.</li>
                <li>Cumplir obligaciones legales y tributarias.</li>
            </ul>
            <p>No vendemos tus datos ni los usamos para enviarte publicidad sin tu consentimiento.</p>

            <h2>Con quién los compartimos</h2>
            <p>Solo con los servicios que necesitamos para funcionar:</p>
            <ul>
                <li><strong>Mercado Pago</strong>, para procesar el pago.</li>
                <li><strong>Proveedor de inteligencia artificial</strong>, que recibe el texto y las fotos que envías al Asistente Virtual para poder responderte.</li>
                <li><strong>Proveedor de hosting y de correo</strong>, donde funciona el sitio y desde donde se envían los avisos.</li>
            </ul>
            <p>Cada uno de ellos trata los datos según sus propias políticas. Te recomendamos no incluir datos personales sensibles en tus mensajes al asistente.</p>

            <h2>Cuánto tiempo guardamos tus datos</h2>
            <p>Los conservamos mientras sea necesario para gestionar tu compra, atender reclamos o garantías y cumplir con las obligaciones legales y tributarias. Después, se eliminan o se dejan sin identificar.</p>
            <p>Las conversaciones con el Asistente Virtual se borran automáticamente 12 meses después del último mensaje.</p>
<?php if (preg_match('/^G-[A-Z0-9]{4,20}$/', (string)gmgEnv('GA4_ID', ''))): // F4-3 ?>
            <p>Medimos las visitas con Google Analytics (páginas vistas, productos agregados al carrito y uso del chat) para mejorar la tienda. No enviamos tu nombre, RUT, correo ni lo que escribes en el chat.</p>
<?php endif; ?>

            <h2 id="tus-derechos">Tus derechos</h2>
            <p>Puedes pedirnos en cualquier momento <strong>acceder</strong> a tus datos, <strong>corregirlos</strong>, <strong>eliminarlos</strong> o <strong>oponerte</strong> a que los usemos para un fin determinado, salvo cuando la ley nos obligue a conservarlos.</p>
            <p><strong>Cómo pedirlo:</strong> escríbenos por <a href="<?php echo gmgH(gmgEnlaceWhatsApp('Hola, quiero ejercer mis derechos sobre mis datos personales (acceso, corrección o eliminación). Mi nombre y RUT son: ')); ?>" target="_blank" rel="noopener">WhatsApp</a><?php if ($correoContacto !== ''): ?> o al correo <a href="mailto:<?php echo gmgH($correoContacto); ?>?subject=<?php echo rawurlencode('Mis datos personales'); ?>"><?php echo gmgH($correoContacto); ?></a><?php endif; ?>, indicando tu nombre, tu RUT y qué necesitas.</p>

            <h2>Seguridad</h2>
            <p>El sitio usa conexión cifrada (HTTPS) y protege el acceso a su sistema de administración. Aun así, ningún sistema en Internet es completamente infalible, por lo que te pedimos cuidar tus propios datos y dispositivos.</p>

            <h2>Cambios a esta política</h2>
            <p>Si actualizamos esta política, publicaremos la nueva versión en esta página con su fecha de actualización.</p>
        </article>
    </main>

<?php gmgPiePublico($conexion, ['modelos' => $modelos]); ?>
