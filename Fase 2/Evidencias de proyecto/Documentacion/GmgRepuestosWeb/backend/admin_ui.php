<?php
/**
 * Piezas comunes del panel de administración - GMG Repuestos
 *
 * Mantiene en un solo lugar el menú del panel, para que cuando se agregue
 * una pantalla nueva aparezca en todas las páginas sin repetir el código.
 */

/** Escapa texto para mostrarlo en HTML. */
function gmgAdminH($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

/**
 * Código de seguridad de los formularios del panel. Evita que otra página
 * web envíe cambios al panel en nombre del administrador sin que lo sepa.
 */
function gmgCsrfToken() {
    gmgIniciarSesion(); // cookie protegida (F3-1)
    if (empty($_SESSION['gmg_csrf'])) {
        $_SESSION['gmg_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['gmg_csrf'];
}

/** Campo oculto con el código de seguridad, para poner dentro de cada formulario. */
function gmgCsrfCampo() {
    return '<input type="hidden" name="csrf" value="' . gmgAdminH(gmgCsrfToken()) . '">';
}

/** ¿El formulario recibido trae el código de seguridad correcto? */
function gmgCsrfValido() {
    $enviado = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';
    return $enviado !== '' && hash_equals(gmgCsrfToken(), $enviado);
}

/**
 * Imprime los enlaces del menú del panel.
 * $activo es la clave de la pantalla actual: dashboard, productos, pedidos,
 * pagos, codigos, catalogo, asistente, avisos.
 */
function gmgAdminEnlaces($activo = '') {
    $enlaces = [
        'dashboard' => ['index.php', 'Dashboard'],
        'productos' => ['productos.php', 'Productos'],
        'pedidos'   => ['pedidos.php', 'Pedidos'],
        'pagos'     => ['pagos.php', 'Pagos'],
        'codigos'   => ['codigos.php', 'Códigos'],
        'catalogo'  => ['catalogo.php', 'Catálogo'],
        'asistente' => ['conocimiento.php', 'Asistente'],
        'avisos'    => ['avisos.php', 'Avisos de stock'],
        'banners'   => ['banners.php', 'Banners'],
        'reportes'  => ['reportes.php', 'Reportes'],
        'usuarios'  => ['usuarios.php', 'Usuarios'],
        'correo'    => ['correo_prueba.php', 'Correo'],
    ];
    // El rol vendedor solo ve estas pantallas (F4-10, E1-H7).
    $deVendedor = ['dashboard', 'productos', 'pedidos', 'avisos'];
    foreach ($enlaces as $clave => $datos) {
        if (function_exists('gmgEsAdminCompleto') && !gmgEsAdminCompleto() && !in_array($clave, $deVendedor, true)) {
            continue;
        }
        echo '                <a href="' . gmgAdminH($datos[0]) . '"' . ($clave === $activo ? ' class="active"' : '') . '>' . gmgAdminH($datos[1]) . "</a>\n";
    }
}

/**
 * Imprime el inicio de una pantalla del panel (head + barra superior).
 * Se usa en las pantallas nuevas; las anteriores conservan su propio encabezado
 * y solo llaman a gmgAdminEnlaces().
 */
function gmgAdminCabecera($titulo, $activo) {
    ?>
<!DOCTYPE html>
<html lang="es-CL">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo gmgAdminH($titulo); ?> - Panel administrador - GMG Repuestos</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo gmgVersionArchivo('css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo gmgVersionArchivo('css/admin.css'); ?>">
</head>
<body class="admin-body">

    <div class="admin-topbar">
        <div class="container">
            <div class="admin-brand">GMG <span>Admin</span></div>
            <nav class="admin-nav">
<?php gmgAdminEnlaces($activo); ?>
                <span class="admin-user">Hola, <?php echo gmgAdminH(nombreAdminActual()); ?></span>
                <a href="../logout.php" class="admin-logout">Cerrar sesión</a>
            </nav>
        </div>
    </div>
<?php
}

/** Cierra la pantalla del panel. */
function gmgAdminPie() {
    echo "</body>\n</html>\n";
}
