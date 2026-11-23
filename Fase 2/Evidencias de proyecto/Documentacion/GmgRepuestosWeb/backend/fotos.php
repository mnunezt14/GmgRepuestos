<?php
/**
 * Fotos subidas desde el panel - GMG Repuestos
 *
 * Antes vivía en admin/catalogo.php como gmgCatalogoGuardarFoto (fotos de modelos).
 * Desde F2-8 (E6-H2, RF-051, CP-46) la usan también los repuestos, con miniatura.
 * Solo JPG, PNG o WebP reales (se revisa el contenido, no la extensión) de hasta 2 MB,
 * con nombre aleatorio. Archivo interno: bloqueado en backend/.htaccess.
 */

const GMG_FOTO_MAX_BYTES = 2 * 1024 * 1024;
const GMG_FOTO_ANCHO_MINIATURA = 320;

/**
 * Guarda una foto subida en img/<carpeta>/<prefijo>-<aleatorio>.<ext>.
 * Con $miniatura, deja además una copia de 320 px de ancho en img/<carpeta>/min/.
 * Devuelve [ruta relativa al sitio o null si no se subió nada, mensaje de error o null].
 */
function gmgGuardarFoto(array $archivo, $carpeta, $prefijo, $miniatura = false) {
    $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return [null, 'No se pudo subir la foto (puede ser muy pesada). Usa una imagen de hasta 2 MB.'];
    }
    if ((int)$archivo['size'] > GMG_FOTO_MAX_BYTES) {
        return [null, 'La foto pesa más de 2 MB. Reduce su tamaño e inténtalo de nuevo.'];
    }
    $info = @getimagesize($archivo['tmp_name']);
    $permitidos = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($permitidos[$info[2]])) {
        return [null, 'El archivo no es una imagen válida. Usa un JPG, PNG o WebP.'];
    }
    $raiz = dirname(__DIR__);
    if (!is_dir("$raiz/img/$carpeta") && !mkdir("$raiz/img/$carpeta", 0755, true)) {
        return [null, 'No se pudo preparar la carpeta de fotos en el servidor.'];
    }
    $nombre = $prefijo . '-' . bin2hex(random_bytes(8)) . '.' . $permitidos[$info[2]];
    if (!move_uploaded_file($archivo['tmp_name'], "$raiz/img/$carpeta/$nombre")) {
        return [null, 'No se pudo guardar la foto en el servidor.'];
    }
    if ($miniatura && !gmgCrearMiniatura("$raiz/img/$carpeta/$nombre", "$raiz/img/$carpeta/min/$nombre", $info[2])) {
        error_log("[GMG Fotos] No se pudo crear la miniatura de img/$carpeta/$nombre");
    }
    return ["img/$carpeta/$nombre", null];
}

/** Copia reducida a 320 px de ancho (si la foto es más angosta, se copia tal cual). */
function gmgCrearMiniatura($origen, $destino, $tipo) {
    if (!is_dir(dirname($destino)) && !mkdir(dirname($destino), 0755, true)) {
        return false;
    }
    if (!function_exists('imagecreatefromjpeg')) {
        return copy($origen, $destino); // sin GD en el servidor: la miniatura es la misma foto
    }
    $abrir = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
    $imagen = @$abrir[$tipo]($origen);
    if (!$imagen) {
        return false;
    }
    if (imagesx($imagen) > GMG_FOTO_ANCHO_MINIATURA) {
        $reducida = imagescale($imagen, GMG_FOTO_ANCHO_MINIATURA);
        imagedestroy($imagen);
        $imagen = $reducida;
    }
    imagealphablending($imagen, false);
    imagesavealpha($imagen, true);
    $ok = $tipo === IMAGETYPE_JPEG ? imagejpeg($imagen, $destino, 82)
        : ($tipo === IMAGETYPE_PNG ? imagepng($imagen, $destino, 8) : imagewebp($imagen, $destino, 80));
    imagedestroy($imagen);
    return $ok;
}

/** ¿La ruta es una foto de repuesto subida desde el panel (nombre aleatorio)? Solo esas se borran. */
function gmgFotoRepuestoSubida($ruta) {
    return preg_match('#^img/repuestos/rep-[0-9a-f]{16}\.(jpg|png|webp)$#', (string)$ruta) === 1;
}

/** Miniatura de una foto de repuesto subida, si existe; si no, la misma foto. */
function gmgMiniaturaRepuesto($ruta) {
    if (gmgFotoRepuestoSubida($ruta)) {
        $mini = 'img/repuestos/min/' . basename($ruta);
        if (is_file(dirname(__DIR__) . '/' . $mini)) {
            return $mini;
        }
    }
    return $ruta;
}

/** Borra fotos de repuesto subidas desde el panel y sus miniaturas (nunca las fotos de muestra). */
function gmgBorrarFotosRepuesto(array $rutas) {
    $raiz = dirname(__DIR__);
    foreach ($rutas as $ruta) {
        if (gmgFotoRepuestoSubida($ruta)) {
            @unlink("$raiz/$ruta");
            @unlink("$raiz/img/repuestos/min/" . basename($ruta));
        }
    }
}
