<?php
/**
 * Lectura de claves y ajustes desde el archivo ".env" - GMG Repuestos
 *
 * Historias que cubre: E10-H4 (claves fuera del código y del repositorio) y
 * E10-H11 (ambientes separados: pruebas y producción).
 *
 * Idea simple: las contraseñas y claves (base de datos, Mercado Pago,
 * correo, inteligencia artificial) ya no se escriben dentro de los archivos
 * PHP. Viven en un archivo aparte llamado ".env" que NO se sube a Git y que
 * en el hosting conviene dejar FUERA de public_html.
 *
 * Cada ambiente (pruebas / producción) tiene su propio archivo ".env", con
 * su propia base de datos y sus propias claves (sandbox de Mercado Pago y
 * clave de IA de pruebas en el ambiente de pruebas).
 *
 * Orden en que se busca el archivo:
 *   1. La ruta indicada en la variable de entorno GMG_ENV_FILE (opcional).
 *   2. Una carpeta más arriba que public_html (lo más seguro en Hostinger).
 *   3. La raíz del sitio (queda bloqueado por el .htaccess de la raíz).
 */

/** Rutas donde se intenta encontrar el archivo .env, en orden de prioridad. */
function gmgRutasEnv() {
    $rutas = [];
    $personalizada = getenv('GMG_ENV_FILE');
    if ($personalizada) {
        $rutas[] = $personalizada;
    }
    $raiz = dirname(__DIR__);             // raíz del sitio (donde está index.php)
    $rutas[] = dirname($raiz) . '/.env';  // una carpeta más arriba (fuera del sitio público)
    $rutas[] = $raiz . '/.env';           // dentro del sitio (bloqueado por .htaccess)
    return $rutas;
}

/**
 * Carga el archivo .env una sola vez y devuelve sus valores como arreglo
 * clave => valor. Si no existe devuelve un arreglo vacío (y deja un aviso
 * en el registro de errores de PHP).
 */
function gmgCargarEnv() {
    static $valores = null;
    if ($valores !== null) {
        return $valores;
    }
    $valores = [];

    foreach (gmgRutasEnv() as $ruta) {
        if (!is_readable($ruta) || !is_file($ruta)) {
            continue;
        }
        $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) {
                continue;
            }
            list($clave, $valor) = explode('=', $linea, 2);
            $clave = trim($clave);
            $valor = trim($valor);
            // Quita comillas si el valor viene entre comillas simples o dobles
            if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'") && substr($valor, -1) === $valor[0]) {
                $valor = substr($valor, 1, -1);
            }
            if ($clave !== '') {
                $valores[$clave] = $valor;
            }
        }
        return $valores;
    }

    error_log('[GMG] No se encontró el archivo .env. Copia .env.example como .env y completa los datos (ver docs/procedimiento_de_despliegue.md).');
    return $valores;
}

/** Valor de una clave del .env (o $defecto si no existe o está vacía). */
function gmgEnv($clave, $defecto = null) {
    $valores = gmgCargarEnv();
    if (isset($valores[$clave]) && $valores[$clave] !== '') {
        return $valores[$clave];
    }
    return $defecto;
}

/** Ambiente actual: 'produccion' o 'pruebas' (clave APP_ENV del .env). */
function gmgAmbiente() {
    return strtolower((string)gmgEnv('APP_ENV', 'produccion')) === 'pruebas' ? 'pruebas' : 'produccion';
}

/** ¿Se está ejecutando en un computador local de desarrollo? */
function gmgEsLocal() {
    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    return $host === 'localhost' || $host === '127.0.0.1' || $host === '::1' || $host === '[::1]' || $host === '';
}

/**
 * ¿Las llamadas a servicios externos (Mercado Pago, IA) deben verificar el
 * certificado del servidor? Siempre sí, salvo que se pida lo contrario en el
 * .env (CURL_VERIFICAR_SSL=0) para diagnosticar un hosting con problemas.
 */
function gmgCurlVerificarSsl() {
    return (string)gmgEnv('CURL_VERIFICAR_SSL', '1') !== '0';
}

/**
 * Versión de un archivo (para que el navegador recargue CSS/JS solo cuando cambian).
 * Vive aquí porque config.php la carga en todas las páginas, públicas y del panel.
 */
function gmgVersionArchivo($rutaRelativa) {
    $ruta = dirname(__DIR__) . '/' . $rutaRelativa;
    return is_file($ruta) ? filemtime($ruta) : 1;
}
