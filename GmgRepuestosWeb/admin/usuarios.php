<?php
/**
 * Usuarios del panel (F4-10: E1-H6 cuentas, E1-H7 roles). Solo rol admin.
 * Crear cuentas (con correo para restablecer la contraseña, F3-2) y cambiar nombre, correo, rol y
 * estado. Nadie puede desactivarse ni quitarse el rol admin a sí mismo, y siempre queda al menos
 * un admin activo (así el panel nunca queda sin dueño). Todo con CSRF y auditoría.
 */
require_once '../backend/auth.php';
requerirRolAdmin('../');
require_once '../backend/admin_ui.php';
require_once '../backend/auditoria.php';

$roles = ['admin' => 'Administrador (todo el panel)', 'vendedor' => 'Vendedor (productos, pedidos y avisos)'];
$mensaje = '';
$tipoMensaje = '';
$miId = (int)$_SESSION['admin_id'];

function gmgUsuarioCampo($nombre) {
    return isset($_POST[$nombre]) && is_string($_POST[$nombre]) ? trim($_POST[$nombre]) : '';
}
function gmgAdminsActivos($conexion, $excepto = 0) {
    $stmt = $conexion->prepare("SELECT COUNT(*) FROM administradores WHERE activo = 1 AND rol = 'admin' AND id_admin <> ?");
    $stmt->bind_param('i', $excepto);
    $stmt->execute();
    $n = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $n;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = gmgUsuarioCampo('accion');
    $nombre = mb_substr(gmgUsuarioCampo('nombre'), 0, 100);
    $email = mb_strtolower(mb_substr(gmgUsuarioCampo('email'), 0, 150));
    $rol = gmgUsuarioCampo('rol');
    if (!gmgCsrfValido()) {
        $mensaje = 'No se pudo comprobar la seguridad del formulario. Recarga la página e inténtalo de nuevo.';
        $tipoMensaje = 'error';
    } elseif ($nombre === '' || !isset($roles[$rol]) || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $mensaje = 'Revisa los datos: el nombre es obligatorio, el rol debe ser uno de la lista y el correo debe ser válido.';
        $tipoMensaje = 'error';
    } elseif ($accion === 'crear') {
        $usuario = mb_strtolower(gmgUsuarioCampo('usuario'));
        $clave = isset($_POST['clave']) && is_string($_POST['clave']) ? $_POST['clave'] : '';
        if (!preg_match('/^[a-z0-9._-]{3,50}$/', $usuario)) {
            $mensaje = 'El usuario debe tener de 3 a 50 letras, números, puntos, guiones o guiones bajos (sin espacios).';
            $tipoMensaje = 'error';
        } elseif (mb_strlen($clave) < 10) {
            $mensaje = 'La contraseña inicial debe tener al menos 10 caracteres.';
            $tipoMensaje = 'error';
        } else {
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $emailGuardar = $email !== '' ? $email : null;
            $stmt = $conexion->prepare("INSERT INTO administradores (usuario, nombre, email, rol, password_hash, activo) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param('sssss', $usuario, $nombre, $emailGuardar, $rol, $hash);
            if (@$stmt->execute()) {
                gmgAuditar($conexion, 'usuario_creado', 'administrador', (int)$conexion->insert_id, 'Usuario "' . $usuario . '" creado con rol ' . $rol, null, $rol);
                $mensaje = 'Usuario "' . $usuario . '" creado. Entrégale su contraseña inicial en persona o por WhatsApp.';
                $tipoMensaje = 'exito';
            } else {
                $mensaje = 'No se pudo crear: ese usuario ya existe.';
                $tipoMensaje = 'error';
            }
            $stmt->close();
        }
    } elseif ($accion === 'editar') {
        $id = (int)gmgUsuarioCampo('id');
        $activo = isset($_POST['activo']) ? 1 : 0;
        $stmt = $conexion->prepare("SELECT usuario, nombre, email, rol, activo FROM administradores WHERE id_admin = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $antes = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $pierdeAdmin = $antes && $antes['rol'] === 'admin' && (int)$antes['activo'] === 1 && ($rol !== 'admin' || $activo === 0);
        if (!$antes) {
            $mensaje = 'Ese usuario ya no existe.';
            $tipoMensaje = 'error';
        } elseif ($id === $miId && ($rol !== 'admin' || $activo === 0)) {
            $mensaje = 'No puedes quitarte el rol de administrador ni desactivar tu propia cuenta.';
            $tipoMensaje = 'error';
        } elseif ($pierdeAdmin && gmgAdminsActivos($conexion, $id) === 0) {
            $mensaje = 'Debe quedar al menos un administrador activo.';
            $tipoMensaje = 'error';
        } else {
            $emailGuardar = $email !== '' ? $email : null;
            $stmt = $conexion->prepare("UPDATE administradores SET nombre = ?, email = ?, rol = ?, activo = ? WHERE id_admin = ?");
            $stmt->bind_param('sssii', $nombre, $emailGuardar, $rol, $activo, $id);
            $stmt->execute();
            $stmt->close();
            $cambios = [];
            if ($antes['rol'] !== $rol) { $cambios[] = 'rol ' . $antes['rol'] . ' a ' . $rol; }
            if ((int)$antes['activo'] !== $activo) { $cambios[] = $activo ? 'activado' : 'desactivado'; }
            if ((string)$antes['email'] !== (string)$emailGuardar) { $cambios[] = 'correo cambiado'; }
            if ($antes['nombre'] !== $nombre) { $cambios[] = 'nombre cambiado'; }
            if ($cambios) {
                gmgAuditar($conexion, 'usuario_editado', 'administrador', $id, 'Usuario "' . $antes['usuario'] . '": ' . implode(', ', $cambios));
            }
            $mensaje = $cambios ? 'Usuario "' . $antes['usuario'] . '" actualizado.' : 'No hubo cambios.';
            $tipoMensaje = 'exito';
        }
    }
}

$usuarios = [];
$r = $conexion->query("SELECT id_admin, usuario, nombre, email, rol, activo, ultimo_acceso FROM administradores ORDER BY activo DESC, usuario");
if ($r) {
    $usuarios = $r->fetch_all(MYSQLI_ASSOC);
} else {
    $mensaje = 'Falta ejecutar el script BD_v2/09_cuentas_banners.sql en la base de datos.';
    $tipoMensaje = 'error';
}

gmgAdminCabecera('Usuarios', 'usuarios');
?>
    <main class="admin-main">
        <div class="container">
            <div class="admin-heading"><h1>Usuarios del panel</h1></div>
            <?php if ($mensaje): ?>
                <div class="flash-msg flash-<?php echo gmgAdminH($tipoMensaje); ?>"><?php echo gmgAdminH($mensaje); ?></div>
            <?php endif; ?>
            <p class="form-ayuda">El correo sirve para recuperar la contraseña ("¿Olvidaste tu contraseña?" en el acceso). Sin correo, la cuenta no puede recuperarla sola.</p>

            <div class="admin-panel">
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Usuario</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Activo</th><th>Último acceso</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($usuarios as $u): $form = 'usuario-' . (int)$u['id_admin']; ?>
                            <tr>
                                <td><strong><?php echo gmgAdminH($u['usuario']); ?></strong><?php echo (int)$u['id_admin'] === $miId ? ' <span class="badge badge-aprobado">Tú</span>' : ''; ?></td>
                                <td><input class="input-rapido" style="width:150px" form="<?php echo $form; ?>" name="nombre" maxlength="100" required value="<?php echo gmgAdminH($u['nombre']); ?>" aria-label="Nombre de <?php echo gmgAdminH($u['usuario']); ?>"></td>
                                <td><input class="input-rapido" style="width:190px" type="email" form="<?php echo $form; ?>" name="email" maxlength="150" value="<?php echo gmgAdminH($u['email'] ?? ''); ?>" aria-label="Correo de <?php echo gmgAdminH($u['usuario']); ?>"></td>
                                <td>
                                    <select class="input-rapido" style="width:auto" form="<?php echo $form; ?>" name="rol" aria-label="Rol de <?php echo gmgAdminH($u['usuario']); ?>">
                                        <?php foreach ($roles as $valor => $texto): ?>
                                            <option value="<?php echo $valor; ?>" <?php echo $u['rol'] === $valor ? 'selected' : ''; ?>><?php echo $valor === 'admin' ? 'Administrador' : 'Vendedor'; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="checkbox" form="<?php echo $form; ?>" name="activo" value="1" <?php echo (int)$u['activo'] === 1 ? 'checked' : ''; ?> aria-label="Cuenta de <?php echo gmgAdminH($u['usuario']); ?> activa"></td>
                                <td><?php echo $u['ultimo_acceso'] ? gmgAdminH(date('d-m-Y H:i', strtotime($u['ultimo_acceso']))) : '—'; ?></td>
                                <td>
                                    <form method="POST" action="usuarios.php" id="<?php echo $form; ?>">
                                        <?php echo gmgCsrfCampo(); ?>
                                        <input type="hidden" name="accion" value="editar">
                                        <input type="hidden" name="id" value="<?php echo (int)$u['id_admin']; ?>">
                                        <button type="submit" class="btn-rapido">Guardar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <h2 class="admin-subtitulo">Nuevo usuario</h2>
            <form class="admin-form" method="POST" action="usuarios.php">
                <?php echo gmgCsrfCampo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-grid">
                    <div><label for="n_usuario">Usuario</label><input id="n_usuario" name="usuario" required maxlength="50" pattern="[a-zA-Z0-9._\-]{3,50}" autocomplete="off"></div>
                    <div><label for="n_nombre">Nombre</label><input id="n_nombre" name="nombre" required maxlength="100"></div>
                    <div><label for="n_email">Correo (para recuperar la contraseña)</label><input id="n_email" type="email" name="email" maxlength="150"></div>
                    <div><label for="n_rol">Rol</label>
                        <select id="n_rol" name="rol"><?php foreach ($roles as $valor => $texto): ?><option value="<?php echo $valor; ?>"><?php echo gmgAdminH($texto); ?></option><?php endforeach; ?></select>
                    </div>
                    <div><label for="n_clave">Contraseña inicial (mínimo 10 caracteres)</label><input id="n_clave" type="password" name="clave" minlength="10" required autocomplete="new-password"></div>
                    <div class="form-actions"><button type="submit" class="btn-admin">Crear usuario</button></div>
                </div>
            </form>
        </div>
    </main>
<?php gmgAdminPie(); ?>
