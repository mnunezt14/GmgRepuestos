<?php
/**
 * Textos del asistente virtual y datos de contacto (historia E5-H9).
 *
 * Desde aquí el equipo de GMG escribe lo que el asistente puede responder
 * (horario, dirección, despacho, medios de pago, garantía, devoluciones).
 * Lo que se guarda aquí también se muestra en la página Contacto y en el pie
 * de página. Si un tema queda vacío, el asistente no inventa la respuesta y
 * ofrece seguir por WhatsApp. Cada cambio queda en el registro de auditoría.
 */
require_once '../backend/auth.php';
requerirRolAdmin('../'); // solo rol admin (F4-10)
require_once '../backend/config.php';
require_once '../backend/admin_ui.php';
require_once '../backend/conocimiento.php';
require_once '../backend/auditoria.php';
require_once '../backend/datos.php';

$temas = gmgTemasConocimiento();
$limites = [
    'horario' => 200, 'direccion' => 200, 'telefono' => 40, 'email' => 150,
    'despacho' => 1500, 'medios_pago' => 1500, 'garantia' => 1500, 'devoluciones' => 1500, 'otros' => 1500,
    'bienvenida' => 400, 'whatsapp' => 20,
];
$mensaje = '';
$tipoMensaje = '';
$errores = [];

$tablaLista = false;
$rExiste = $conexion->query("SHOW TABLES LIKE 'base_conocimiento'");
if ($rExiste && $rExiste->num_rows > 0) {
    $tablaLista = true;
}

$actuales = $tablaLista ? gmgConocimientoTodo($conexion) : array_fill_keys(array_keys($temas), '');
$valoresForm = $actuales;

if ($tablaLista && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!gmgCsrfValido()) {
        $mensaje = 'No se pudo comprobar la seguridad del formulario. Recarga la página e inténtalo de nuevo.';
        $tipoMensaje = 'error';
    } else {
        $nuevos = [];
        foreach ($temas as $clave => $info) {
            $texto = trim((string)($_POST['t_' . $clave] ?? ''));
            $texto = str_replace(["\r\n", "\r"], "\n", $texto);
            $nuevos[$clave] = $texto;
            if (mb_strlen($texto) > $limites[$clave]) {
                $errores[$clave] = 'Máximo ' . $limites[$clave] . ' caracteres (hoy tiene ' . mb_strlen($texto) . ').';
            }
        }
        // WhatsApp de la tienda (F3-8): celular chileno, se guarda como +56 9 1234 5678.
        if ($nuevos['whatsapp'] !== '') {
            $celular = gmgTelefonoChile($nuevos['whatsapp']);
            if ($celular === '') {
                $errores['whatsapp'] = 'Escribe un celular chileno, por ejemplo +56 9 1234 5678.';
            } else {
                $nuevos['whatsapp'] = $celular;
            }
        }
        if ($nuevos['email'] !== '' && !filter_var($nuevos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'El correo no parece válido. Revisa que tenga el formato nombre@dominio.cl';
        }
        $valoresForm = $nuevos;

        if ($errores) {
            $mensaje = 'Revisa los campos marcados. No se guardó ningún cambio.';
            $tipoMensaje = 'error';
        } else {
            $cambiados = 0;
            $fallo = false;
            $stmt = $conexion->prepare("INSERT INTO base_conocimiento (clave, titulo, contenido) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE contenido = VALUES(contenido)");
            foreach ($temas as $clave => $info) {
                if ($nuevos[$clave] === $actuales[$clave]) {
                    continue;
                }
                $titulo = $info['titulo'];
                $contenido = $nuevos[$clave];
                $stmt->bind_param("sss", $clave, $titulo, $contenido);
                if ($stmt->execute()) {
                    $cambiados++;
                    gmgAuditar($conexion, 'conocimiento', 'conocimiento', null,
                        'Texto del asistente "' . $titulo . '" ' . ($actuales[$clave] === '' ? 'escrito' : ($contenido === '' ? 'vaciado' : 'modificado')),
                        $actuales[$clave] !== '' ? $actuales[$clave] : null,
                        $contenido !== '' ? $contenido : null);
                } else {
                    $fallo = true;
                }
            }
            $stmt->close();
            $actuales = gmgConocimientoTodo($conexion);
            $valoresForm = $actuales;
            if ($fallo) {
                $mensaje = 'Algunos textos no se pudieron guardar. Inténtalo de nuevo.';
                $tipoMensaje = 'error';
            } elseif ($cambiados === 0) {
                $mensaje = 'No había cambios para guardar.';
                $tipoMensaje = 'exito';
            } else {
                $mensaje = 'Listo: se guardó ' . ($cambiados === 1 ? '1 texto' : $cambiados . ' textos') . '. El asistente, la página Contacto y el pie de página ya usan la información nueva.';
                $tipoMensaje = 'exito';
            }
        }
    }
}

gmgAdminCabecera('Textos del asistente', 'asistente');
?>

    <main class="admin-main">
        <div class="container">
            <div class="admin-heading">
                <h1>Textos del asistente y datos de contacto</h1>
            </div>

            <p style="margin:-6px 0 16px; font-size:0.9rem; color:#666; max-width:760px;">
                Escribe aquí lo que el asistente virtual puede responder a los clientes. Estos mismos datos aparecen en la página
                Contacto y en el pie de página de la tienda. Si dejas un tema vacío, el asistente no inventará la respuesta: le dirá
                al cliente que siga la conversación por WhatsApp.
            </p>

            <?php if ($mensaje): ?>
                <div class="flash-msg flash-<?php echo $tipoMensaje; ?>"><?php echo gmgAdminH($mensaje); ?></div>
            <?php endif; ?>

            <?php if (!$tablaLista): ?>
                <div class="flash-msg flash-error">
                    Esta función todavía no está activada en la base de datos. Hay que ejecutar una vez el archivo
                    <strong>BD_v2/06_sprint1a4_tablas_nuevas.sql</strong> y luego recargar esta página.
                </div>
            <?php else: ?>

            <form class="admin-form" method="POST" action="conocimiento.php">
                <?php echo gmgCsrfCampo(); ?>
                <?php foreach ($temas as $clave => $info):
                    $corto = in_array($clave, ['horario', 'direccion', 'telefono', 'email', 'whatsapp'], true); ?>
                    <div style="margin-bottom:18px;">
                        <label for="t_<?php echo gmgAdminH($clave); ?>"><?php echo gmgAdminH($info['titulo']); ?></label>
                        <?php if ($corto): ?>
                            <input type="text" id="t_<?php echo gmgAdminH($clave); ?>" name="t_<?php echo gmgAdminH($clave); ?>" maxlength="<?php echo (int)$limites[$clave]; ?>" value="<?php echo gmgAdminH($valoresForm[$clave]); ?>">
                        <?php else: ?>
                            <textarea id="t_<?php echo gmgAdminH($clave); ?>" name="t_<?php echo gmgAdminH($clave); ?>" rows="4" maxlength="<?php echo (int)$limites[$clave]; ?>" style="width:100%;"><?php echo gmgAdminH($valoresForm[$clave]); ?></textarea>
                        <?php endif; ?>
                        <small style="display:block; color:#888; margin-top:4px;"><?php echo gmgAdminH($info['ayuda']); ?><?php echo $valoresForm[$clave] === '' ? ' <strong style="color:#b45309;">(Sin configurar)</strong>' : ''; ?></small>
                        <?php if (isset($errores[$clave])): ?><small style="display:block; color:#c0392b; margin-top:4px;"><?php echo gmgAdminH($errores[$clave]); ?></small><?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="form-actions">
                    <button type="submit" class="btn-admin">Guardar cambios</button>
                    <a href="auditoria.php?entidad=conocimiento" class="btn-admin btn-secundario">Ver quién los cambió</a>
                </div>
            </form>

            <?php endif; ?>
        </div>
    </main>
<?php gmgAdminPie(); ?>
