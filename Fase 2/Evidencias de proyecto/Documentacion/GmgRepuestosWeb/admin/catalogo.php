<?php
/**
 * Catálogo: modelos, versiones de vehículo, categorías y motores (historia E10-H12).
 *
 * Permite al administrador ampliar el catálogo sin tocar código:
 *  - Un modelo nuevo (con su foto y su primera versión) aparece solo en la
 *    portada, el buscador, el pie de página y el asistente virtual, en cuanto
 *    tiene al menos un repuesto compatible.
 *  - Una categoría nueva queda disponible al crear o editar productos.
 *  - Los motores se usan al definir las versiones de cada modelo.
 * Cada alta queda registrada en el registro de auditoría.
 */
require_once '../backend/auth.php';
requerirRolAdmin('../'); // solo rol admin (F4-10)
require_once '../backend/config.php';
require_once '../backend/admin_ui.php';
require_once '../backend/auditoria.php';
require_once '../backend/fotos.php';

$carrocerias = ['Hatchback', 'Sedán', 'Crossover', 'SUV', 'Todoterreno', 'Minivan', 'Furgón', 'Camioneta'];
$combustibles = ['Bencina', 'Diésel'];
$aspiraciones = ['Atmosférico', 'Turbo'];
$distribuciones = ['Cadena', 'Correa'];

/** Convierte un nombre en una dirección corta: "Grand Vitara" -> "grand-vitara". */
function gmgCatalogoSlug($texto) {
    $texto = mb_strtolower(trim((string)$texto), 'UTF-8');
    $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    return trim($texto, '-');
}

/** Mensaje que se muestra una sola vez después de guardar (evita reenviar el formulario al recargar). */
function gmgCatalogoAviso($mensaje, $tipo) {
    $_SESSION['gmg_catalogo_aviso'] = ['mensaje' => $mensaje, 'tipo' => $tipo];
    header('Location: catalogo.php');
    exit;
}

/** ¿Ya existe una fila con ese valor? */
function gmgCatalogoExiste($conexion, $sql, $tipos, array $valores) {
    $stmt = $conexion->prepare($sql);
    if (!$stmt) { return false; }
    $stmt->bind_param($tipos, ...$valores);
    $stmt->execute();
    $res = $stmt->get_result();
    $existe = $res && $res->num_rows > 0;
    $stmt->close();
    return $existe;
}

// La foto del modelo se guarda con gmgGuardarFoto() (backend/fotos.php, compartida con los repuestos desde F2-8).

if (!isset($_SESSION['gmg_catalogo_aviso'])) {
    $_SESSION['gmg_catalogo_aviso'] = null;
}
$mensaje = '';
$tipoMensaje = '';
if (!empty($_SESSION['gmg_catalogo_aviso'])) {
    $mensaje = $_SESSION['gmg_catalogo_aviso']['mensaje'];
    $tipoMensaje = $_SESSION['gmg_catalogo_aviso']['tipo'];
    $_SESSION['gmg_catalogo_aviso'] = null;
}

// Datos de apoyo para los formularios.
$marcas = [];
$r = $conexion->query("SELECT id_marca, nombre FROM marcas WHERE activo = 1 ORDER BY nombre");
if ($r) { while ($f = $r->fetch_assoc()) { $marcas[] = $f; } }

$motores = [];
$r = $conexion->query("SELECT id_motor, codigo, variante, cilindrada_cc, cilindros, valvulas, combustible, aspiracion, hibrido, tipo_distribucion FROM motores ORDER BY codigo, variante");
if ($r) { while ($f = $r->fetch_assoc()) { $motores[] = $f; } }

$categoriasLista = [];
$r = $conexion->query("SELECT c.id_categoria, c.id_categoria_padre, c.codigo, c.nombre, c.orden,
                              p.nombre AS padre,
                              (SELECT COUNT(*) FROM repuestos re WHERE re.id_categoria = c.id_categoria) AS repuestos
                         FROM categorias c LEFT JOIN categorias p ON p.id_categoria = c.id_categoria_padre
                        ORDER BY c.orden, c.nombre");
if ($r) { while ($f = $r->fetch_assoc()) { $categoriasLista[] = $f; } }

$textoMotor = function ($m) {
    return $m['codigo'] . ($m['variante'] !== '' ? ' ' . $m['variante'] : '') . ' (' . number_format($m['cilindrada_cc'] / 1000, 1, ',', '') . ' L)';
};

// ----------------------------------------------------------------------
// Procesar formularios
// ----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if (!gmgCsrfValido()) {
        gmgCatalogoAviso('No se pudo comprobar la seguridad del formulario. Recarga la página e inténtalo de nuevo.', 'error');
    }

    try {
        if ($accion === 'crear_modelo') {
            $idMarca = (int)($_POST['id_marca'] ?? 0);
            $nombre = trim((string)($_POST['nombre'] ?? ''));
            $carroceria = (string)($_POST['carroceria'] ?? '');
            $orden = (int)($_POST['orden'] ?? 0);
            $imagenRuta = trim((string)($_POST['imagen_ruta'] ?? ''));
            $idMotor = (int)($_POST['id_motor'] ?? 0);
            $generacion = trim((string)($_POST['generacion'] ?? ''));
            $anioDesde = (int)($_POST['anio_desde'] ?? 0);
            $anioHasta = trim((string)($_POST['anio_hasta'] ?? '')) === '' ? null : (int)$_POST['anio_hasta'];

            if ($idMarca <= 0 || $nombre === '' || mb_strlen($nombre) > 50) {
                gmgCatalogoAviso('Elige la marca y escribe el nombre del modelo (hasta 50 letras).', 'error');
            }
            if (!in_array($carroceria, $carrocerias, true)) {
                gmgCatalogoAviso('Elige el tipo de carrocería del modelo.', 'error');
            }
            if ($idMotor <= 0) {
                gmgCatalogoAviso('Elige el motor de la primera versión del modelo. Si no está en la lista, créalo primero en "Motores".', 'error');
            }
            if ($anioDesde < 1970 || $anioDesde > 2100 || ($anioHasta !== null && ($anioHasta < $anioDesde || $anioHasta > 2100))) {
                gmgCatalogoAviso('Revisa los años de la versión: "desde" debe estar entre 1970 y 2100, y "hasta" no puede ser menor que "desde" (déjalo vacío si el modelo sigue a la venta).', 'error');
            }
            if (mb_strlen($generacion) > 60) {
                gmgCatalogoAviso('La generación puede tener hasta 60 letras.', 'error');
            }
            if ($imagenRuta !== '' && !preg_match('#^img/[A-Za-z0-9_\-/.]+\.(jpg|jpeg|png|webp|svg)$#i', $imagenRuta)) {
                gmgCatalogoAviso('La ruta de la foto debe ser parecida a img/modelos/mi-foto.jpg', 'error');
            }
            if (strpos($imagenRuta, '..') !== false) {
                gmgCatalogoAviso('La ruta de la foto no es válida.', 'error');
            }
            if ($orden <= 0) {
                $rMax = $conexion->query("SELECT COALESCE(MAX(orden), 0) + 1 AS siguiente FROM modelos");
                $orden = $rMax ? (int)$rMax->fetch_assoc()['siguiente'] : 1;
            }
            $orden = max(0, min(255, $orden));

            $slug = gmgCatalogoSlug($nombre);
            if ($slug === '') {
                gmgCatalogoAviso('El nombre del modelo debe tener letras o números.', 'error');
            }
            if (gmgCatalogoExiste($conexion, "SELECT 1 FROM modelos WHERE slug = ? OR (id_marca = ? AND nombre = ?)", 'sis', [$slug, $idMarca, $nombre])) {
                gmgCatalogoAviso('Ya existe un modelo con ese nombre.', 'error');
            }

            // Foto: si se sube un archivo, tiene prioridad sobre la ruta escrita.
            if (isset($_FILES['imagen'])) {
                list($rutaSubida, $errorFoto) = gmgGuardarFoto($_FILES['imagen'], 'modelos', $slug);
                if ($errorFoto) {
                    gmgCatalogoAviso($errorFoto, 'error');
                }
                if ($rutaSubida) { $imagenRuta = $rutaSubida; }
            }
            $imagenGuardar = $imagenRuta !== '' ? $imagenRuta : null;
            $generacionGuardar = $generacion !== '' ? $generacion : null;

            $conexion->begin_transaction();
            $stmt = $conexion->prepare("INSERT INTO modelos (id_marca, nombre, slug, tipo_carroceria, imagen_url, orden, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("issssi", $idMarca, $nombre, $slug, $carroceria, $imagenGuardar, $orden);
            $ok = $stmt->execute();
            $idModelo = (int)$conexion->insert_id;
            $stmt->close();
            if ($ok) {
                $stmt = $conexion->prepare("INSERT INTO vehiculos (id_modelo, id_motor, generacion, anio_desde, anio_hasta, activo) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("iisii", $idModelo, $idMotor, $generacionGuardar, $anioDesde, $anioHasta);
                $ok = $stmt->execute();
                $idVehiculo = (int)$conexion->insert_id;
                $stmt->close();
            }
            if (!$ok) {
                $conexion->rollback();
                gmgCatalogoAviso('No se pudo crear el modelo. Revisa los datos e inténtalo de nuevo.', 'error');
            }
            $conexion->commit();
            gmgAuditar($conexion, 'modelo_creado', 'modelo', $idModelo, 'Modelo "' . $nombre . '" creado (' . $carroceria . ')', null, $nombre);
            gmgAuditar($conexion, 'version_creada', 'vehiculo', $idVehiculo, 'Versión de "' . $nombre . '" creada (' . $anioDesde . '-' . ($anioHasta ?? 'hoy') . ')', null, $anioDesde . '-' . ($anioHasta ?? 'hoy'));
            gmgCatalogoAviso('Modelo "' . $nombre . '" creado. Aparecerá en la tienda y en el asistente en cuanto tenga al menos un repuesto compatible: ve a Productos y marca esta versión en los vehículos compatibles.', 'exito');

        } elseif ($accion === 'crear_version') {
            $idModelo = (int)($_POST['id_modelo'] ?? 0);
            $idMotor = (int)($_POST['id_motor'] ?? 0);
            $generacion = trim((string)($_POST['generacion'] ?? ''));
            $anioDesde = (int)($_POST['anio_desde'] ?? 0);
            $anioHasta = trim((string)($_POST['anio_hasta'] ?? '')) === '' ? null : (int)$_POST['anio_hasta'];

            if ($idModelo <= 0 || $idMotor <= 0) {
                gmgCatalogoAviso('Elige el modelo y el motor de la nueva versión.', 'error');
            }
            if ($anioDesde < 1970 || $anioDesde > 2100 || ($anioHasta !== null && ($anioHasta < $anioDesde || $anioHasta > 2100)) || mb_strlen($generacion) > 60) {
                gmgCatalogoAviso('Revisa los años y la generación de la versión.', 'error');
            }
            if (gmgCatalogoExiste($conexion, "SELECT 1 FROM vehiculos WHERE id_modelo = ? AND id_motor = ? AND anio_desde = ?", 'iii', [$idModelo, $idMotor, $anioDesde])) {
                gmgCatalogoAviso('Esa versión ya existe (mismo modelo, motor y año de inicio).', 'error');
            }
            $generacionGuardar = $generacion !== '' ? $generacion : null;
            $stmt = $conexion->prepare("INSERT INTO vehiculos (id_modelo, id_motor, generacion, anio_desde, anio_hasta, activo) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("iisii", $idModelo, $idMotor, $generacionGuardar, $anioDesde, $anioHasta);
            if (!$stmt->execute()) {
                $stmt->close();
                gmgCatalogoAviso('No se pudo crear la versión. Revisa los datos e inténtalo de nuevo.', 'error');
            }
            $idVehiculo = (int)$conexion->insert_id;
            $stmt->close();
            $nombreModelo = '';
            $rM = $conexion->query("SELECT nombre FROM modelos WHERE id_modelo = " . (int)$idModelo);
            if ($rM && ($fm = $rM->fetch_assoc())) { $nombreModelo = $fm['nombre']; }
            gmgAuditar($conexion, 'version_creada', 'vehiculo', $idVehiculo, 'Versión de "' . $nombreModelo . '" creada (' . $anioDesde . '-' . ($anioHasta ?? 'hoy') . ')', null, $anioDesde . '-' . ($anioHasta ?? 'hoy'));
            gmgCatalogoAviso('Versión agregada al modelo ' . $nombreModelo . '. Ya puedes marcarla como compatible al editar productos.', 'exito');

        } elseif ($accion === 'alternar_modelo') {
            $idModelo = (int)($_POST['id_modelo'] ?? 0);
            $stmt = $conexion->prepare("SELECT nombre, activo FROM modelos WHERE id_modelo = ?");
            $stmt->bind_param("i", $idModelo);
            $stmt->execute();
            $res = $stmt->get_result();
            $fila = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if (!$fila) {
                gmgCatalogoAviso('No se encontró el modelo.', 'error');
            }
            $stmt = $conexion->prepare("UPDATE modelos SET activo = 1 - activo WHERE id_modelo = ?");
            $stmt->bind_param("i", $idModelo);
            $stmt->execute();
            $stmt->close();
            $antes = ((int)$fila['activo'] === 1) ? 'visible' : 'oculto';
            $despues = ((int)$fila['activo'] === 1) ? 'oculto' : 'visible';
            gmgAuditar($conexion, 'modelo_estado', 'modelo', $idModelo, 'Modelo "' . $fila['nombre'] . '": ' . $antes . ' a ' . $despues, $antes, $despues);
            gmgCatalogoAviso('El modelo "' . $fila['nombre'] . '" ahora está ' . ($despues === 'visible' ? 'visible' : 'oculto') . ' en la tienda.', 'exito');

        } elseif ($accion === 'crear_categoria') {
            $codigo = strtoupper(trim((string)($_POST['codigo'] ?? '')));
            $nombre = trim((string)($_POST['nombre'] ?? ''));
            $descripcion = trim((string)($_POST['descripcion'] ?? ''));
            $idPadre = (int)($_POST['id_padre'] ?? 0);
            $orden = (int)($_POST['orden'] ?? 0);

            if (!preg_match('/^[A-Z]{3}$/', $codigo)) {
                gmgCatalogoAviso('El código de la categoría son exactamente 3 letras (por ejemplo FRE). Es el comienzo del SKU de sus productos.', 'error');
            }
            if ($nombre === '' || mb_strlen($nombre) > 60 || mb_strlen($descripcion) > 255) {
                gmgCatalogoAviso('Escribe el nombre de la categoría (hasta 60 letras). La descripción puede tener hasta 255.', 'error');
            }
            $slug = gmgCatalogoSlug($nombre);
            if ($slug === '') {
                gmgCatalogoAviso('El nombre de la categoría debe tener letras o números.', 'error');
            }
            if (gmgCatalogoExiste($conexion, "SELECT 1 FROM categorias WHERE codigo = ? OR nombre = ? OR slug = ?", 'sss', [$codigo, $nombre, $slug])) {
                gmgCatalogoAviso('Ya existe una categoría con ese código o con ese nombre.', 'error');
            }
            if ($idPadre > 0 && !gmgCatalogoExiste($conexion, "SELECT 1 FROM categorias WHERE id_categoria = ? AND id_categoria_padre IS NULL", 'i', [$idPadre])) {
                gmgCatalogoAviso('La categoría principal elegida no es válida.', 'error');
            }
            if ($orden <= 0) {
                $rMax = $conexion->query("SELECT COALESCE(MAX(orden), 0) + 1 AS siguiente FROM categorias");
                $orden = $rMax ? (int)$rMax->fetch_assoc()['siguiente'] : 1;
            }
            $orden = max(0, min(255, $orden));
            $padreGuardar = $idPadre > 0 ? $idPadre : null;
            $descripcionGuardar = $descripcion !== '' ? $descripcion : null;
            $stmt = $conexion->prepare("INSERT INTO categorias (id_categoria_padre, codigo, nombre, slug, descripcion, orden) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi", $padreGuardar, $codigo, $nombre, $slug, $descripcionGuardar, $orden);
            if (!$stmt->execute()) {
                $stmt->close();
                gmgCatalogoAviso('No se pudo crear la categoría. Revisa los datos e inténtalo de nuevo.', 'error');
            }
            $idCategoria = (int)$conexion->insert_id;
            $stmt->close();
            gmgAuditar($conexion, 'categoria_creada', 'categoria', $idCategoria, 'Categoría "' . $nombre . '" creada (código ' . $codigo . ')', null, $nombre);
            gmgCatalogoAviso('Categoría "' . $nombre . '" creada. Ya puedes elegirla al crear o editar productos.', 'exito');

        } elseif ($accion === 'crear_motor') {
            $codigo = strtoupper(trim((string)($_POST['codigo'] ?? '')));
            $variante = trim((string)($_POST['variante'] ?? ''));
            $cc = (int)($_POST['cilindrada_cc'] ?? 0);
            $cilindros = (int)($_POST['cilindros'] ?? 0);
            $valvulas = (int)($_POST['valvulas'] ?? 0);
            $combustible = (string)($_POST['combustible'] ?? '');
            $aspiracion = (string)($_POST['aspiracion'] ?? '');
            $hibrido = isset($_POST['hibrido']) ? 1 : 0;
            $distribucion = (string)($_POST['distribucion'] ?? '');

            if ($codigo === '' || mb_strlen($codigo) > 20 || mb_strlen($variante) > 40) {
                gmgCatalogoAviso('Escribe el código del motor (hasta 20 caracteres, por ejemplo K12M). La variante puede tener hasta 40.', 'error');
            }
            if ($cc < 500 || $cc > 5000 || $cilindros < 2 || $cilindros > 8 || $valvulas < 4 || $valvulas > 32) {
                gmgCatalogoAviso('Revisa la cilindrada (entre 500 y 5000 cc), los cilindros (2 a 8) y las válvulas (4 a 32).', 'error');
            }
            if (!in_array($combustible, $combustibles, true) || !in_array($aspiracion, $aspiraciones, true) || !in_array($distribucion, $distribuciones, true)) {
                gmgCatalogoAviso('Elige combustible, aspiración y tipo de distribución.', 'error');
            }
            if (gmgCatalogoExiste($conexion, "SELECT 1 FROM motores WHERE codigo = ? AND variante = ?", 'ss', [$codigo, $variante])) {
                gmgCatalogoAviso('Ya existe un motor con ese código y esa variante.', 'error');
            }
            $stmt = $conexion->prepare("INSERT INTO motores (codigo, variante, cilindrada_cc, cilindros, valvulas, combustible, aspiracion, hibrido, tipo_distribucion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiissis", $codigo, $variante, $cc, $cilindros, $valvulas, $combustible, $aspiracion, $hibrido, $distribucion);
            if (!$stmt->execute()) {
                $stmt->close();
                gmgCatalogoAviso('No se pudo crear el motor. Revisa los datos e inténtalo de nuevo.', 'error');
            }
            $idMotorNuevo = (int)$conexion->insert_id;
            $stmt->close();
            $descMotor = $codigo . ($variante !== '' ? ' ' . $variante : '') . ' (' . $cc . ' cc)';
            gmgAuditar($conexion, 'motor_creado', 'motor', $idMotorNuevo, 'Motor ' . $descMotor . ' creado', null, $descMotor);
            gmgCatalogoAviso('Motor ' . $descMotor . ' creado. Ya está disponible para las versiones de los modelos.', 'exito');
        }
    } catch (mysqli_sql_exception $e) {
        error_log('[GMG Catálogo] ' . $e->getMessage());
        try { $conexion->rollback(); } catch (Throwable $ignorado) { /* no había transacción abierta */ }
        gmgCatalogoAviso('No se pudo guardar el cambio. Revisa los datos e inténtalo de nuevo.', 'error');
    }
}

// ----------------------------------------------------------------------
// Datos para mostrar
// ----------------------------------------------------------------------
$modelosLista = [];
$r = $conexion->query("SELECT mo.id_modelo, ma.nombre AS marca, mo.nombre, mo.tipo_carroceria, mo.imagen_url, mo.orden, mo.activo,
                              (SELECT COUNT(*) FROM vehiculos v WHERE v.id_modelo = mo.id_modelo) AS versiones,
                              (SELECT MIN(v.anio_desde) FROM vehiculos v WHERE v.id_modelo = mo.id_modelo) AS anio_desde,
                              (SELECT IF(SUM(v.anio_hasta IS NULL) > 0, NULL, MAX(v.anio_hasta)) FROM vehiculos v WHERE v.id_modelo = mo.id_modelo) AS anio_hasta,
                              (SELECT COUNT(DISTINCT co.id_repuesto)
                                 FROM vehiculos v
                                 JOIN compatibilidades co ON co.id_vehiculo = v.id_vehiculo
                                 JOIN repuestos re ON re.id_repuesto = co.id_repuesto
                                WHERE v.id_modelo = mo.id_modelo AND v.activo = 1 AND re.activo = 1) AS repuestos
                         FROM modelos mo JOIN marcas ma ON ma.id_marca = mo.id_marca
                        ORDER BY mo.orden, mo.nombre");
if ($r) { while ($f = $r->fetch_assoc()) { $modelosLista[] = $f; } }

gmgAdminCabecera('Catálogo', 'catalogo');
?>

    <main class="admin-main">
        <div class="container">
            <div class="admin-heading">
                <h1>Catálogo</h1>
            </div>

            <p style="margin:-6px 0 16px; font-size:0.9rem; color:#666; max-width:780px;">
                Aquí puedes agregar modelos Suzuki nuevos, sus versiones (motor y años), categorías de repuestos y motores, sin pedir
                ayuda técnica. Todo lo que agregues se muestra en la tienda y en el asistente virtual automáticamente.
                Los repuestos se siguen creando en <a href="productos.php" style="color:var(--color-acento);">Productos</a>.
            </p>

            <?php if ($mensaje): ?>
                <div class="flash-msg flash-<?php echo gmgAdminH($tipoMensaje); ?>"><?php echo gmgAdminH($mensaje); ?></div>
            <?php endif; ?>

            <!-- ============================ MODELOS ============================ -->
            <h2 style="margin:10px 0 12px; font-size:1.2rem;">Modelos</h2>

            <div class="admin-panel" style="margin-bottom:20px;">
                <div class="admin-table-wrap">
                    <?php if (count($modelosLista) === 0): ?>
                        <p class="admin-empty">Todavía no hay modelos creados.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Modelo</th>
                                    <th>Carrocería</th>
                                    <th>Versiones</th>
                                    <th>Repuestos</th>
                                    <th>En la tienda</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modelosLista as $m):
                                    $visible = ((int)$m['activo'] === 1 && (int)$m['repuestos'] > 0); ?>
                                <tr>
                                    <td><strong><?php echo gmgAdminH($m['marca'] . ' ' . $m['nombre']); ?></strong></td>
                                    <td><?php echo gmgAdminH($m['tipo_carroceria']); ?></td>
                                    <td>
                                        <?php echo (int)$m['versiones']; ?><br>
                                        <span style="color:#888; font-size:0.78rem;"><?php echo $m['anio_desde'] ? gmgAdminH($m['anio_desde'] . '-' . ($m['anio_hasta'] ? $m['anio_hasta'] : 'hoy')) : 'Sin años'; ?></span>
                                    </td>
                                    <td><?php echo (int)$m['repuestos']; ?></td>
                                    <td>
                                        <?php if ($visible): ?>
                                            <span class="badge badge-aprobado">Visible</span>
                                        <?php elseif ((int)$m['activo'] !== 1): ?>
                                            <span class="badge badge-rechazado">Oculto</span>
                                        <?php else: ?>
                                            <span class="badge badge-pendiente">Falta un repuesto compatible</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="catalogo.php">
                                            <?php echo gmgCsrfCampo(); ?>
                                            <input type="hidden" name="accion" value="alternar_modelo">
                                            <input type="hidden" name="id_modelo" value="<?php echo (int)$m['id_modelo']; ?>">
                                            <button type="submit" class="link-editar"><?php echo (int)$m['activo'] === 1 ? 'Ocultar' : 'Mostrar'; ?></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <details class="admin-panel" style="padding:16px 20px; margin-bottom:16px;">
                <summary style="cursor:pointer; font-weight:700;">+ Agregar un modelo nuevo</summary>
                <form class="admin-form" method="POST" action="catalogo.php" enctype="multipart/form-data" style="margin-top:14px;">
                    <?php echo gmgCsrfCampo(); ?>
                    <input type="hidden" name="accion" value="crear_modelo">
                    <div class="form-grid">
                        <div>
                            <label for="m-marca">Marca</label>
                            <select id="m-marca" name="id_marca" required>
                                <?php foreach ($marcas as $ma): ?><option value="<?php echo (int)$ma['id_marca']; ?>"><?php echo gmgAdminH($ma['nombre']); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="m-nombre">Nombre del modelo</label>
                            <input type="text" id="m-nombre" name="nombre" maxlength="50" required placeholder="Ej: Jimny">
                        </div>
                        <div>
                            <label for="m-carroceria">Tipo de carrocería</label>
                            <select id="m-carroceria" name="carroceria" required>
                                <?php foreach ($carrocerias as $c): ?><option><?php echo gmgAdminH($c); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="m-orden">Posición en la portada</label>
                            <input type="number" id="m-orden" name="orden" min="1" max="255" placeholder="Vacío = al final">
                        </div>
                        <div>
                            <label for="m-imagen">Foto del modelo (JPG, PNG o WebP, hasta 2 MB)</label>
                            <input type="file" id="m-imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div>
                            <label for="m-imagen-ruta">O ruta de una foto ya subida</label>
                            <input type="text" id="m-imagen-ruta" name="imagen_ruta" maxlength="255" placeholder="Ej: img/jimny.jpg (opcional)">
                        </div>
                    </div>
                    <p style="margin:14px 0 6px; font-weight:700; font-size:0.9rem;">Primera versión del modelo</p>
                    <div class="form-grid">
                        <div>
                            <label for="m-motor">Motor</label>
                            <select id="m-motor" name="id_motor" required>
                                <option value="">Elegir motor</option>
                                <?php foreach ($motores as $mt): ?><option value="<?php echo (int)$mt['id_motor']; ?>"><?php echo gmgAdminH($textoMotor($mt)); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="m-gen">Generación (opcional)</label>
                            <input type="text" id="m-gen" name="generacion" maxlength="60" placeholder="Ej: 3ª generación">
                        </div>
                        <div>
                            <label for="m-desde">Año desde</label>
                            <input type="number" id="m-desde" name="anio_desde" min="1970" max="2100" required placeholder="Ej: 2018">
                        </div>
                        <div>
                            <label for="m-hasta">Año hasta</label>
                            <input type="number" id="m-hasta" name="anio_hasta" min="1970" max="2100" placeholder="Vacío = sigue a la venta">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-admin">Crear modelo</button>
                        </div>
                    </div>
                </form>
            </details>

            <details class="admin-panel" style="padding:16px 20px; margin-bottom:30px;">
                <summary style="cursor:pointer; font-weight:700;">+ Agregar otra versión a un modelo existente</summary>
                <form class="admin-form" method="POST" action="catalogo.php" style="margin-top:14px;">
                    <?php echo gmgCsrfCampo(); ?>
                    <input type="hidden" name="accion" value="crear_version">
                    <div class="form-grid">
                        <div>
                            <label for="v-modelo">Modelo</label>
                            <select id="v-modelo" name="id_modelo" required>
                                <?php foreach ($modelosLista as $m): ?><option value="<?php echo (int)$m['id_modelo']; ?>"><?php echo gmgAdminH($m['marca'] . ' ' . $m['nombre']); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="v-motor">Motor</label>
                            <select id="v-motor" name="id_motor" required>
                                <option value="">Elegir motor</option>
                                <?php foreach ($motores as $mt): ?><option value="<?php echo (int)$mt['id_motor']; ?>"><?php echo gmgAdminH($textoMotor($mt)); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="v-gen">Generación (opcional)</label>
                            <input type="text" id="v-gen" name="generacion" maxlength="60">
                        </div>
                        <div>
                            <label for="v-desde">Año desde</label>
                            <input type="number" id="v-desde" name="anio_desde" min="1970" max="2100" required>
                        </div>
                        <div>
                            <label for="v-hasta">Año hasta</label>
                            <input type="number" id="v-hasta" name="anio_hasta" min="1970" max="2100" placeholder="Vacío = sigue a la venta">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-admin">Agregar versión</button>
                        </div>
                    </div>
                </form>
            </details>

            <!-- ============================ CATEGORÍAS ============================ -->
            <h2 style="margin:10px 0 12px; font-size:1.2rem;">Categorías de repuestos</h2>

            <div class="admin-panel" style="margin-bottom:20px;">
                <div class="admin-table-wrap">
                    <?php if (count($categoriasLista) === 0): ?>
                        <p class="admin-empty">Todavía no hay categorías creadas.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr><th>Código</th><th>Nombre</th><th>Dentro de</th><th>Repuestos</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categoriasLista as $c): ?>
                                <tr>
                                    <td><strong><?php echo gmgAdminH($c['codigo']); ?></strong></td>
                                    <td><?php echo gmgAdminH($c['nombre']); ?></td>
                                    <td><?php echo $c['padre'] !== null ? gmgAdminH($c['padre']) : '<span style="color:#bbb;">Categoría principal</span>'; ?></td>
                                    <td><?php echo (int)$c['repuestos']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <details class="admin-panel" style="padding:16px 20px; margin-bottom:30px;">
                <summary style="cursor:pointer; font-weight:700;">+ Agregar una categoría</summary>
                <form class="admin-form" method="POST" action="catalogo.php" style="margin-top:14px;">
                    <?php echo gmgCsrfCampo(); ?>
                    <input type="hidden" name="accion" value="crear_categoria">
                    <div class="form-grid">
                        <div>
                            <label for="c-codigo">Código (3 letras)</label>
                            <input type="text" id="c-codigo" name="codigo" maxlength="3" required placeholder="Ej: SUS" style="text-transform:uppercase;">
                        </div>
                        <div>
                            <label for="c-nombre">Nombre</label>
                            <input type="text" id="c-nombre" name="nombre" maxlength="60" required placeholder="Ej: Suspensión">
                        </div>
                        <div>
                            <label for="c-padre">Dentro de (opcional)</label>
                            <select id="c-padre" name="id_padre">
                                <option value="0">Es una categoría principal</option>
                                <?php foreach ($categoriasLista as $c): if ($c['id_categoria_padre'] === null): ?>
                                    <option value="<?php echo (int)$c['id_categoria']; ?>"><?php echo gmgAdminH($c['nombre']); ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="c-orden">Posición en la lista</label>
                            <input type="number" id="c-orden" name="orden" min="1" max="255" placeholder="Vacío = al final">
                        </div>
                        <div>
                            <label for="c-desc">Descripción (opcional)</label>
                            <input type="text" id="c-desc" name="descripcion" maxlength="255">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-admin">Crear categoría</button>
                        </div>
                    </div>
                </form>
            </details>

            <!-- ============================ MOTORES ============================ -->
            <h2 style="margin:10px 0 12px; font-size:1.2rem;">Motores</h2>

            <div class="admin-panel" style="margin-bottom:20px;">
                <div class="admin-table-wrap">
                    <?php if (count($motores) === 0): ?>
                        <p class="admin-empty">Todavía no hay motores creados.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr><th>Código</th><th>Variante</th><th>Cilindrada</th><th>Cilindros y válvulas</th><th>Combustible</th><th>Distribución</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($motores as $mt): ?>
                                <tr>
                                    <td><strong><?php echo gmgAdminH($mt['codigo']); ?></strong></td>
                                    <td><?php echo $mt['variante'] !== '' ? gmgAdminH($mt['variante']) : '<span style="color:#bbb;">-</span>'; ?></td>
                                    <td><?php echo gmgAdminH(number_format($mt['cilindrada_cc'] / 1000, 1, ',', '')); ?> L (<?php echo (int)$mt['cilindrada_cc']; ?> cc)</td>
                                    <td><?php echo (int)$mt['cilindros']; ?> cilindros, <?php echo (int)$mt['valvulas']; ?> válvulas</td>
                                    <td><?php echo gmgAdminH($mt['combustible']); ?>, <?php echo gmgAdminH(strtolower($mt['aspiracion'])); ?><?php echo (int)$mt['hibrido'] === 1 ? ', micro híbrido' : ''; ?></td>
                                    <td><?php echo gmgAdminH($mt['tipo_distribucion']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <details class="admin-panel" style="padding:16px 20px; margin-bottom:30px;">
                <summary style="cursor:pointer; font-weight:700;">+ Agregar un motor</summary>
                <form class="admin-form" method="POST" action="catalogo.php" style="margin-top:14px;">
                    <?php echo gmgCsrfCampo(); ?>
                    <input type="hidden" name="accion" value="crear_motor">
                    <div class="form-grid">
                        <div>
                            <label for="mt-codigo">Código del motor</label>
                            <input type="text" id="mt-codigo" name="codigo" maxlength="20" required placeholder="Ej: K12M" style="text-transform:uppercase;">
                        </div>
                        <div>
                            <label for="mt-variante">Variante (opcional)</label>
                            <input type="text" id="mt-variante" name="variante" maxlength="40" placeholder="Ej: Boosterjet Turbo">
                        </div>
                        <div>
                            <label for="mt-cc">Cilindrada (cc)</label>
                            <input type="number" id="mt-cc" name="cilindrada_cc" min="500" max="5000" required placeholder="Ej: 1197">
                        </div>
                        <div>
                            <label for="mt-cil">Cilindros</label>
                            <input type="number" id="mt-cil" name="cilindros" min="2" max="8" required placeholder="Ej: 4">
                        </div>
                        <div>
                            <label for="mt-val">Válvulas</label>
                            <input type="number" id="mt-val" name="valvulas" min="4" max="32" required placeholder="Ej: 16">
                        </div>
                        <div>
                            <label for="mt-comb">Combustible</label>
                            <select id="mt-comb" name="combustible"><?php foreach ($combustibles as $x): ?><option><?php echo gmgAdminH($x); ?></option><?php endforeach; ?></select>
                        </div>
                        <div>
                            <label for="mt-asp">Aspiración</label>
                            <select id="mt-asp" name="aspiracion"><?php foreach ($aspiraciones as $x): ?><option><?php echo gmgAdminH($x); ?></option><?php endforeach; ?></select>
                        </div>
                        <div>
                            <label for="mt-dist">Distribución</label>
                            <select id="mt-dist" name="distribucion"><?php foreach ($distribuciones as $x): ?><option><?php echo gmgAdminH($x); ?></option><?php endforeach; ?></select>
                        </div>
                        <div>
                            <label for="mt-hib">Micro híbrido (Smart Hybrid)</label>
                            <input type="checkbox" id="mt-hib" name="hibrido" value="1">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-admin">Crear motor</button>
                        </div>
                    </div>
                </form>
            </details>
        </div>
    </main>
<?php gmgAdminPie(); ?>
