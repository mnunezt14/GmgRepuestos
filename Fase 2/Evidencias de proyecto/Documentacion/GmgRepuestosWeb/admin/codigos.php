<?php
require_once '../backend/auth.php';
requerirRolAdmin('../'); // solo rol admin (F4-10)
require_once '../backend/config.php';
require_once '../backend/admin_ui.php';
require_once '../backend/auditoria.php';

$mensaje = '';
$tipoMensaje = '';

// --- Procesar acciones (crear / eliminar / activar-desactivar) ---
// Solo se aceptan los formularios del propio panel (código de seguridad CSRF, INC-04).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !gmgCsrfValido()) {
    $mensaje = 'No se pudo comprobar la seguridad del formulario. Recarga la página e inténtalo de nuevo.';
    $tipoMensaje = 'error';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $tipo = ($_POST['tipo'] ?? '') === 'monto' ? 'monto' : 'porcentaje';
        $valor = (float)($_POST['valor'] ?? 0);

        if ($codigo === '' || $valor <= 0) {
            $mensaje = 'El código y el valor del descuento son obligatorios.';
            $tipoMensaje = 'error';
        } else {
            $stmt = $conexion->prepare("INSERT INTO codigos_descuento (codigo, tipo, valor, activo) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("ssd", $codigo, $tipo, $valor);
            if ($stmt->execute()) {
                $idNuevoCodigo = (int)$conexion->insert_id;
                $descripcionValor = $tipo === 'porcentaje' ? (float)$valor . '%' : gmgAuditoriaPesos($valor);
                gmgAuditar($conexion, 'codigo_creado', 'codigo_descuento', $idNuevoCodigo,
                    'Código ' . $codigo . ' creado (' . $descripcionValor . ')', null, $descripcionValor);
                $mensaje = 'Código "' . $codigo . '" creado correctamente.';
                $tipoMensaje = 'exito';
            } else {
                $mensaje = 'No se pudo crear el código (¿ya existe?).';
                $tipoMensaje = 'error';
            }
            $stmt->close();
        }
    } elseif ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        $codigoBorrado = gmgAuditoriaLeerCodigo($conexion, $id);
        // Base v2: un código que ya se usó en pedidos no se puede borrar
        // (la llave foránea lo protege para no perder el historial).
        $stmt = $conexion->prepare("DELETE FROM codigos_descuento WHERE id_codigo = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            if ($codigoBorrado) {
                gmgAuditar($conexion, 'codigo_eliminado', 'codigo_descuento', $id,
                    'Código ' . $codigoBorrado['codigo'] . ' eliminado', $codigoBorrado['codigo'], null);
            }
            $mensaje = 'Código eliminado.';
            $tipoMensaje = 'exito';
        } else {
            $mensaje = 'Este código ya se usó en pedidos, así que no se puede eliminar. Desactívalo para que no se pueda volver a usar.';
            $tipoMensaje = 'error';
        }
        $stmt->close();
    } elseif ($accion === 'alternar') {
        $id = (int)($_POST['id'] ?? 0);
        $codigoAntes = gmgAuditoriaLeerCodigo($conexion, $id);
        $stmt = $conexion->prepare("UPDATE codigos_descuento SET activo = 1 - activo WHERE id_codigo = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            if ($codigoAntes) {
                $quedaActivo = ((int)$codigoAntes['activo'] === 1) ? 'inactivo' : 'activo';
                gmgAuditar($conexion, 'codigo_estado', 'codigo_descuento', $id,
                    'Código ' . $codigoAntes['codigo'] . ': ' . ((int)$codigoAntes['activo'] === 1 ? 'activo' : 'inactivo') . ' a ' . $quedaActivo,
                    ((int)$codigoAntes['activo'] === 1 ? 'activo' : 'inactivo'), $quedaActivo);
            }
            $mensaje = 'Estado del código actualizado.';
            $tipoMensaje = 'exito';
        }
        $stmt->close();
    }
}

$codigos = [];
$r = $conexion->query("SELECT id_codigo AS id, codigo, tipo, valor, activo, vigente_hasta, creado_en FROM codigos_descuento ORDER BY creado_en DESC");
if ($r) {
    while ($fila = $r->fetch_assoc()) {
        $codigos[] = $fila;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Códigos de descuento - Panel administrador - GMG Repuestos</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo gmgVersionArchivo('css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo gmgVersionArchivo('css/admin.css'); ?>">
</head>
<body class="admin-body">

    <div class="admin-topbar">
        <div class="container">
            <div class="admin-brand">GMG <span>Admin</span></div>
            <nav class="admin-nav">
<?php require_once '../backend/admin_ui.php'; gmgAdminEnlaces('codigos'); ?>
                <span class="admin-user">Hola, <?php echo htmlspecialchars(nombreAdminActual()); ?></span>
                <a href="../logout.php" class="admin-logout">Cerrar sesión</a>
            </nav>
        </div>
    </div>

    <main class="admin-main">
        <div class="container">
            <div class="admin-heading">
                <h1>Códigos de descuento</h1>
            </div>

            <?php if ($mensaje): ?>
                <div class="flash-msg flash-<?php echo $tipoMensaje; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <form class="admin-form" method="POST" action="codigos.php" style="margin-bottom:30px;">
                <?php echo gmgCsrfCampo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-grid">
                    <div>
                        <label for="codigo">Código</label>
                        <input type="text" id="codigo" name="codigo" placeholder="Ej: VERANO10" required style="text-transform:uppercase;">
                    </div>
                    <div>
                        <label for="tipo">Tipo</label>
                        <select id="tipo" name="tipo">
                            <option value="porcentaje">Porcentaje (%)</option>
                            <option value="monto">Monto fijo (CLP)</option>
                        </select>
                    </div>
                    <div>
                        <label for="valor">Valor</label>
                        <input type="number" id="valor" name="valor" min="1" step="1" required placeholder="Ej: 10">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-admin">+ Crear código</button>
                    </div>
                </div>
            </form>

            <div class="admin-panel">
                <div class="admin-table-wrap">
                    <?php if (count($codigos) === 0): ?>
                        <p class="admin-empty">No hay códigos de descuento creados todavía.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Tipo</th>
                                    <th>Valor</th>
                                    <th>Estado</th>
                                    <th>Creado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($codigos as $c): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($c['codigo']); ?></strong></td>
                                    <td><?php echo $c['tipo'] === 'porcentaje' ? 'Porcentaje' : 'Monto fijo'; ?></td>
                                    <td><?php echo $c['tipo'] === 'porcentaje' ? htmlspecialchars($c['valor']) . '%' : '$' . number_format($c['valor'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if ((int)$c['activo'] === 1): ?>
                                            <span class="badge badge-aprobado">Activo</span>
                                        <?php else: ?>
                                            <span class="badge badge-rechazado">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($c['creado_en']))); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <form method="POST" action="codigos.php">
                                                <?php echo gmgCsrfCampo(); ?>
                                                <input type="hidden" name="accion" value="alternar">
                                                <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                                                <button type="submit" class="link-editar"><?php echo (int)$c['activo'] === 1 ? 'Desactivar' : 'Activar'; ?></button>
                                            </form>
                                            <form method="POST" action="codigos.php" onsubmit="return confirm('¿Eliminar este código de descuento?');">
                                                <?php echo gmgCsrfCampo(); ?>
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                                                <button type="submit" class="link-eliminar">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
