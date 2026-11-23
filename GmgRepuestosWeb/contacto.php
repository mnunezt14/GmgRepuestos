<?php
require_once 'backend/config.php';
require_once 'backend/pagina_publica.php';

gmgCabeceraPublica([
    'titulo'      => 'Contacto',
    'descripcion' => 'Dirección, mapa, horarios y medios de contacto de GMG Repuestos SpA.',
    'canonical'   => gmgUrlBase() . '/contacto.php',
]);
$modelos = gmgDatosModelosPublicos($conexion);

// Los datos de contacto se editan desde el panel (menú Asistente) y son los
// mismos que usan el pie de página y el asistente virtual.
$c = gmgConocimientoTodo($conexion);
$direccion = $c['direccion'];
$urlMapa = $direccion !== ''
    ? 'https://www.google.com/maps?q=' . rawurlencode($direccion) . '&output=embed'
    : '';
$urlMapaAbrir = $direccion !== ''
    ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($direccion)
    : '';
?>

    <main class="container">
        <article class="pagina-info">
            <nav class="pagina-info-migas" aria-label="Ubicación"><a href="index.php">Inicio</a> / Contacto</nav>
            <h1>Contacto</h1>
            <p>Estamos para ayudarte a encontrar el repuesto correcto para tu Suzuki.</p>

            <div class="contacto-grid">
                <div class="contacto-datos">
                    <h2>Dónde encontrarnos</h2>
                    <dl class="contacto-lista">
                        <dt>📍 Dirección</dt>
                        <dd><?php echo $direccion !== '' ? gmgH($direccion) : 'Por confirmar'; ?></dd>

                        <dt>🕒 Horario de atención</dt>
                        <dd><?php echo $c['horario'] !== '' ? gmgH($c['horario']) : 'Por confirmar'; ?></dd>

                        <dt>📞 Teléfono</dt>
                        <dd><?php if ($c['telefono'] !== ''): ?><a href="tel:<?php echo gmgH(preg_replace('/[^0-9+]/', '', $c['telefono'])); ?>"><?php echo gmgH($c['telefono']); ?></a><?php else: ?>Por confirmar<?php endif; ?></dd>

                        <dt>✉️ Correo</dt>
                        <dd><?php if ($c['email'] !== ''): ?><a href="mailto:<?php echo gmgH($c['email']); ?>"><?php echo gmgH($c['email']); ?></a><?php else: ?>Por confirmar<?php endif; ?></dd>
                    </dl>
                    <a class="btn-whatsapp contacto-whatsapp" href="<?php echo gmgH(gmgEnlaceWhatsApp('Hola, tengo una consulta sobre un repuesto')); ?>" target="_blank" rel="noopener">Escribir por WhatsApp</a>
                </div>

                <div class="contacto-mapa">
                    <h2>Mapa</h2>
                    <?php if ($urlMapa !== ''): ?>
                        <iframe title="Mapa con la ubicación de GMG Repuestos" src="<?php echo gmgH($urlMapa); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                        <p class="contacto-mapa-enlace"><a href="<?php echo gmgH($urlMapaAbrir); ?>" target="_blank" rel="noopener">Abrir en Google Maps</a></p>
                    <?php else: ?>
                        <p class="contacto-mapa-vacio">El mapa se mostrará cuando se confirme la dirección de la tienda.</p>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    </main>

<?php gmgPiePublico($conexion, ['modelos' => $modelos]); ?>
