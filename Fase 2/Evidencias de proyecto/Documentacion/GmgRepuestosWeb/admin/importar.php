<?php
/**
 * Importar el catálogo desde CSV (F4-5, E6-H8, RF-057). Solo rol admin.
 * Formato de BD_v2/catalogo_repuestos.csv (separador ";"):
 *   sku;categoria;nombre;vehiculos_compatibles;n_vehiculos;precio_referencial_clp;stock;codigo_oem
 * vehiculos_compatibles: textos de vw_vehiculos separados por " | " (ej. "Swift 1.4 K14B (2011-2017)").
 * El SKU que existe se actualiza (categoría, nombre, precio, stock, OEM y vehículos) y el nuevo se crea.
 * Todo o nada: si una fila tiene error no se guarda ninguna y se muestra el informe. "Solo revisar"
 * valida sin guardar. Con CSRF y auditoría (precio y stock con gmgAuditarRepuesto).
 */
require_once '../backend/auth.php';
requerirRolAdmin('../');
require_once '../backend/admin_ui.php';
require_once '../backend/datos.php';
require_once '../backend/auditoria.php';
require_once '../backend/email_helpers.php';

const GMG_CSV_COLUMNAS = ['sku', 'categoria', 'nombre', 'vehiculos_compatibles', 'n_vehiculos', 'precio_referencial_clp', 'stock', 'codigo_oem'];
const GMG_CSV_MAX_BYTES = 2 * 1024 * 1024;
const GMG_CSV_MAX_FILAS = 5000;

/**
 * Lee y valida el CSV. Devuelve [filas válidas, errores]. Cada error: "Fila N: motivo".
 * $categorias: nombre en minúsculas => id; $vehiculos: descripción en minúsculas => id.
 */
function gmgCsvLeer($ruta, array $categorias, array $vehiculos) {
    $contenido = (string)file_get_contents($ruta);
    if (!mb_check_encoding($contenido, 'UTF-8')) {
        $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252'); // CSV guardado por Excel en Windows
    }
    $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
    $lineas = preg_split('/\r\n|\n|\r/', $contenido);
    $filas = [];
    $errores = [];
    $encabezado = array_map(function ($c) { return mb_strtolower(trim($c)); }, str_getcsv((string)array_shift($lineas), ';'));
    if ($encabezado !== GMG_CSV_COLUMNAS) {
        return [[], ['Fila 1: el encabezado debe ser exactamente: ' . implode(';', GMG_CSV_COLUMNAS)]];
    }
    $skus = [];
    foreach ($lineas as $i => $linea) {
        $n = $i + 2;
        if (trim($linea) === '') {
            continue;
        }
        if (count($filas) + count($errores) >= GMG_CSV_MAX_FILAS) {
            $errores[] = "Fila $n: el archivo tiene más de " . GMG_CSV_MAX_FILAS . ' filas.';
            break;
        }
        $c = array_map('trim', str_getcsv($linea, ';'));
        if (count($c) !== count(GMG_CSV_COLUMNAS)) {
            $errores[] = "Fila $n: tiene " . count($c) . ' columnas y deben ser ' . count(GMG_CSV_COLUMNAS) . '.';
            continue;
        }
        [$sku, $categoria, $nombre, $textoVehiculos, , $precio, $stock, $oem] = $c;
        $sku = strtoupper($sku);
        $motivos = [];
        if (!preg_match('/^[A-Z0-9][A-Z0-9-]{1,29}$/', $sku)) { $motivos[] = 'SKU vacío o con caracteres no permitidos (letras, números y guiones, hasta 30)'; }
        if (isset($skus[$sku])) { $motivos[] = "SKU repetido (también en la fila {$skus[$sku]})"; }
        if (!isset($categorias[mb_strtolower($categoria)])) { $motivos[] = "la categoría \"$categoria\" no existe"; }
        if ($nombre === '' || mb_strlen($nombre) > 150) { $motivos[] = 'nombre vacío o de más de 150 caracteres'; }
        if (!ctype_digit($precio)) { $motivos[] = 'precio debe ser un número entero sin puntos'; }
        if (!ctype_digit($stock)) { $motivos[] = 'stock debe ser un número entero de 0 o más'; }
        if (mb_strlen($oem) > 40) { $motivos[] = 'código OEM de más de 40 caracteres'; }
        $idsVehiculos = [];
        foreach (array_filter(array_map('trim', explode('|', $textoVehiculos)), 'strlen') as $vehiculo) {
            if (isset($vehiculos[mb_strtolower($vehiculo)])) {
                $idsVehiculos[] = $vehiculos[mb_strtolower($vehiculo)];
            } else {
                $motivos[] = "el vehículo \"$vehiculo\" no existe en el catálogo";
            }
        }
        $skus[$sku] = $skus[$sku] ?? $n;
        if ($motivos) {
            $errores[] = "Fila $n ($sku): " . implode('; ', $motivos) . '.';
            continue;
        }
        $filas[] = ['sku' => $sku, 'id_categoria' => $categorias[mb_strtolower($categoria)], 'nombre' => $nombre, 'vehiculos' => $idsVehiculos,
                    'precio' => (float)$precio, 'stock' => (int)$stock, 'oem' => $oem !== '' ? $oem : null];
    }
    if (!$filas && !$errores) {
        $errores[] = 'El archivo no tiene filas de repuestos.';
    }
    return [$filas, $errores];
}

$mensaje = '';
$tipoMensaje = '';
$errores = [];
$resumen = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $archivo = $_FILES['csv'] ?? null;
    $soloRevisar = isset($_POST['solo_revisar']);
    if (!gmgCsrfValido()) {
        $mensaje = 'No se pudo comprobar la seguridad del formulario. Recarga la página e inténtalo de nuevo.';
        $tipoMensaje = 'error';
    } elseif (!$archivo || !is_array($archivo) || !is_int($archivo['error'] ?? null) || $archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > GMG_CSV_MAX_BYTES) {
        $mensaje = 'Sube un archivo CSV de hasta 2 MB.';
        $tipoMensaje = 'error';
    } else {
        $categorias = [];
        foreach (gmgCategorias($conexion) as $cat) {
            $categorias[mb_strtolower($cat['nombre'])] = (int)$cat['id_categoria'];
        }
        $vehiculos = [];
        $r = $conexion->query("SELECT id_vehiculo, descripcion FROM vw_vehiculos");
        while ($r && ($f = $r->fetch_assoc())) {
            $vehiculos[mb_strtolower($f['descripcion'])] = (int)$f['id_vehiculo'];
        }
        [$filas, $errores] = gmgCsvLeer($archivo['tmp_name'], $categorias, $vehiculos);
        if ($errores) {
            $mensaje = 'El archivo tiene ' . count($errores) . ' fila' . (count($errores) === 1 ? '' : 's') . ' con error. No se guardó nada: corrige y vuelve a subirlo.';
            $tipoMensaje = 'error';
        } else {
            // Cuáles existen (por SKU) para saber si se crean o se actualizan.
            $existentes = [];
            $r = $conexion->query("SELECT id_repuesto, sku FROM repuestos");
            while ($r && ($f = $r->fetch_assoc())) {
                $existentes[strtoupper($f['sku'])] = (int)$f['id_repuesto'];
            }
            $resumen = ['nuevos' => 0, 'actualizados' => 0];
            foreach ($filas as $fila) {
                $resumen[isset($existentes[$fila['sku']]) ? 'actualizados' : 'nuevos']++;
            }
            if ($soloRevisar) {
                $mensaje = 'Archivo correcto: se crearían ' . $resumen['nuevos'] . ' y se actualizarían ' . $resumen['actualizados'] . ' repuestos. No se guardó nada (solo revisión).';
                $tipoMensaje = 'exito';
            } else {
                $antes = [];
                foreach ($filas as $fila) {
                    if (isset($existentes[$fila['sku']])) {
                        $antes[$fila['sku']] = gmgAuditoriaLeerRepuesto($conexion, $existentes[$fila['sku']]);
                    }
                }
                $conexion->begin_transaction();
                $ok = true;
                $actualizar = $conexion->prepare("UPDATE repuestos SET id_categoria = ?, nombre = ?, precio = ?, stock = ?, codigo_oem = ? WHERE id_repuesto = ?");
                $insertar = $conexion->prepare("INSERT INTO repuestos (id_categoria, nombre, sku, precio, stock, codigo_oem, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $ids = [];
                foreach ($filas as $fila) {
                    if (isset($existentes[$fila['sku']])) {
                        $id = $existentes[$fila['sku']];
                        $actualizar->bind_param('isdisi', $fila['id_categoria'], $fila['nombre'], $fila['precio'], $fila['stock'], $fila['oem'], $id);
                        $ok = $actualizar->execute() && $ok;
                    } else {
                        $insertar->bind_param('issdis', $fila['id_categoria'], $fila['nombre'], $fila['sku'], $fila['precio'], $fila['stock'], $fila['oem']);
                        $ok = $insertar->execute() && $ok;
                        $id = (int)$conexion->insert_id;
                        $ok = $ok && gmgGuardarImagenesRepuesto($conexion, $id, [], $fila['nombre']);
                    }
                    $ok = $ok && gmgGuardarCompatibilidades($conexion, $id, $fila['vehiculos']);
                    $ids[$fila['sku']] = $id;
                    if (!$ok) {
                        break;
                    }
                }
                $actualizar->close();
                $insertar->close();
                if ($ok) {
                    $conexion->commit();
                    foreach ($ids as $sku => $id) {
                        gmgAuditarRepuesto($conexion, $id, $antes[$sku] ?? null, gmgAuditoriaLeerRepuesto($conexion, $id));
                    }
                    gmgAuditar($conexion, 'catalogo_importado', 'repuesto', null, 'Importación CSV: ' . $resumen['nuevos'] . ' nuevos y ' . $resumen['actualizados'] . ' actualizados');
                    $mensaje = 'Importación lista: ' . $resumen['nuevos'] . ' repuestos nuevos y ' . $resumen['actualizados'] . ' actualizados.';
                    // Los que volvieron a tener stock avisan a quienes lo pidieron (F3-9; antes solo desde Productos).
                    $avisados = 0;
                    foreach ($filas as $fila) {
                        if ($fila['stock'] > 0) {
                            $avisados += gmgNotificarReposicion($conexion, $ids[$fila['sku']]);
                        }
                    }
                    if ($avisados > 0) {
                        $mensaje .= ' Se avisó por correo a ' . $avisados . ' cliente' . ($avisados === 1 ? '' : 's') . ' que esperaba' . ($avisados === 1 ? '' : 'n') . ' repuestos sin stock.';
                    }
                    if (gmgErrorCorreo() !== '') {
                        $mensaje .= ' Ojo: no se pudieron enviar los avisos por correo (revisa Panel > Correo).';
                    }
                    $tipoMensaje = 'exito';
                } else {
                    $conexion->rollback();
                    error_log('[GMG Importar] La importación falló y se deshizo: ' . $conexion->error);
                    $mensaje = 'No se pudo completar la importación y no se guardó nada. Inténtalo de nuevo.';
                    $tipoMensaje = 'error';
                }
            }
        }
    }
}

gmgAdminCabecera('Importar catálogo', 'productos');
?>
    <main class="admin-main">
        <div class="container">
            <div class="admin-heading">
                <h1>Importar catálogo (CSV)</h1>
                <a href="productos.php" class="btn-admin btn-secundario">← Volver a Productos</a>
            </div>
            <?php if ($mensaje): ?>
                <div class="flash-msg flash-<?php echo gmgAdminH($tipoMensaje); ?>"><?php echo gmgAdminH($mensaje); ?></div>
            <?php endif; ?>
            <?php if ($errores): ?>
                <div class="admin-panel"><ul class="importar-errores">
                    <?php foreach (array_slice($errores, 0, 200) as $error): ?><li><?php echo gmgAdminH($error); ?></li><?php endforeach; ?>
                    <?php if (count($errores) > 200): ?><li>... y <?php echo count($errores) - 200; ?> más.</li><?php endif; ?>
                </ul></div>
            <?php endif; ?>

            <p class="form-ayuda">Columnas (separadas por punto y coma, en este orden): <code><?php echo gmgAdminH(implode(';', GMG_CSV_COLUMNAS)); ?></code>.
                Los vehículos van separados por " | " y escritos como en el panel (ej. <code>Swift 1.4 K14B (2011-2017)</code>). El precio y el stock, números enteros sin puntos.
                Un SKU que ya existe se actualiza; uno nuevo se crea. Si una fila tiene error, no se guarda ninguna. Para partir del catálogo actual: Reportes > "Descargar catálogo con stock" (mismo formato).</p>

            <form class="admin-form" method="POST" action="importar.php" enctype="multipart/form-data">
                <?php echo gmgCsrfCampo(); ?>
                <div class="form-grid">
                    <div class="form-full"><label for="csv">Archivo CSV (hasta 2 MB)</label><input type="file" id="csv" name="csv" accept=".csv,text/csv" required></div>
                    <div class="form-actions">
                        <button type="submit" name="solo_revisar" value="1" class="btn-admin btn-secundario">Solo revisar</button>
                        <button type="submit" class="btn-admin">Importar</button>
                    </div>
                </div>
            </form>
        </div>
    </main>
<?php gmgAdminPie(); ?>
