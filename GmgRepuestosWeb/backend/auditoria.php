<?php
/**
 * Registro de auditoría del panel - GMG Repuestos (historia E10-H7)
 *
 * Deja constancia de QUIÉN hizo cada cambio importante, QUÉ cambió y CUÁNDO:
 *   - precios y stock de los repuestos,
 *   - estado de los pedidos,
 *   - códigos de descuento,
 *   - cambios al catálogo (modelos y categorías) y a la base de conocimiento.
 *
 * El registro es solo de agregar: no existe ninguna función ni pantalla del
 * panel para editarlo o borrarlo. La tabla se crea con
 * BD_v2/06_sprint1a4_tablas_nuevas.sql (y el script 07 agrega, opcionalmente,
 * un candado dentro de la propia base de datos).
 *
 * Si la tabla todavía no existe, solo se deja un aviso en el registro de
 * errores y el panel sigue funcionando.
 */

/** Formatea un monto en pesos chilenos: 12990 -> "$12.990". */
function gmgAuditoriaPesos($monto) {
    return '$' . number_format((float)$monto, 0, ',', '.');
}

/**
 * Agrega una línea al registro de auditoría.
 *
 * @param mysqli      $conexion
 * @param string      $accion      Ej: precio, stock, estado_pedido, codigo_creado
 * @param string      $entidad     repuesto, pedido, codigo_descuento, modelo, categoria, conocimiento
 * @param int|null    $idEntidad   Identificador del registro modificado
 * @param string      $detalle     Texto legible de lo que pasó
 * @param string|null $anterior    Valor antes del cambio
 * @param string|null $nuevo       Valor después del cambio
 * @return bool true si quedó registrado
 */
function gmgAuditar($conexion, $accion, $entidad, $idEntidad, $detalle, $anterior = null, $nuevo = null) {
    gmgIniciarSesion(); // cookie protegida (F3-1)
    $idAdmin = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
    $nombreAdmin = isset($_SESSION['admin_nombre']) && $_SESSION['admin_nombre'] !== '' ? (string)$_SESSION['admin_nombre'] : 'Administrador';
    $ip = isset($_SERVER['REMOTE_ADDR']) ? mb_substr((string)$_SERVER['REMOTE_ADDR'], 0, 45) : null;

    $accion = mb_substr((string)$accion, 0, 40);
    $entidad = mb_substr((string)$entidad, 0, 30);
    $detalle = mb_substr((string)$detalle, 0, 255);
    $anterior = $anterior !== null ? mb_substr((string)$anterior, 0, 255) : null;
    $nuevo = $nuevo !== null ? mb_substr((string)$nuevo, 0, 255) : null;
    $nombreAdmin = mb_substr($nombreAdmin, 0, 100);
    $idEntidad = $idEntidad !== null ? (int)$idEntidad : null;

    $stmt = $conexion->prepare("INSERT INTO auditoria (id_admin, admin_nombre, accion, entidad, id_entidad, detalle, valor_anterior, valor_nuevo, ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log('[GMG Auditoría] No se pudo registrar el cambio (¿falta ejecutar BD_v2/06_sprint1a4_tablas_nuevas.sql?): ' . $conexion->error);
        return false;
    }
    $stmt->bind_param("isssissss", $idAdmin, $nombreAdmin, $accion, $entidad, $idEntidad, $detalle, $anterior, $nuevo, $ip);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/** Lee precio, stock y datos básicos de un repuesto (para comparar antes y después de guardar). */
function gmgAuditoriaLeerRepuesto($conexion, $idRepuesto) {
    $stmt = $conexion->prepare("SELECT id_repuesto, sku, nombre, precio, stock, activo FROM repuestos WHERE id_repuesto = ?");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("i", $idRepuesto);
    $stmt->execute();
    $res = $stmt->get_result();
    $fila = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $fila ?: null;
}

/**
 * Registra los cambios de precio y de stock de un repuesto comparando cómo
 * estaba ($antes, de gmgAuditoriaLeerRepuesto) con cómo quedó ($despues).
 * Si $antes es null el repuesto es nuevo y se registra su precio y stock inicial.
 */
function gmgAuditarRepuesto($conexion, $idRepuesto, $antes, $despues) {
    if (!$despues) {
        return;
    }
    $sku = $despues['sku'];
    if ($antes === null) {
        gmgAuditar($conexion, 'repuesto_creado', 'repuesto', $idRepuesto,
            "Repuesto $sku creado con precio " . gmgAuditoriaPesos($despues['precio']) . " y stock " . (int)$despues['stock'],
            null, 'precio ' . gmgAuditoriaPesos($despues['precio']) . ', stock ' . (int)$despues['stock']);
        return;
    }
    if ((float)$antes['precio'] !== (float)$despues['precio']) {
        gmgAuditar($conexion, 'precio', 'repuesto', $idRepuesto,
            "Precio de $sku: " . gmgAuditoriaPesos($antes['precio']) . ' a ' . gmgAuditoriaPesos($despues['precio']),
            gmgAuditoriaPesos($antes['precio']), gmgAuditoriaPesos($despues['precio']));
    }
    if ((int)$antes['stock'] !== (int)$despues['stock']) {
        gmgAuditar($conexion, 'stock', 'repuesto', $idRepuesto,
            "Stock de $sku: " . (int)$antes['stock'] . ' a ' . (int)$despues['stock'] . ' unidades',
            (string)(int)$antes['stock'], (string)(int)$despues['stock']);
    }
}

/** Lee un código de descuento (para comparar antes y después de cambiarlo). */
function gmgAuditoriaLeerCodigo($conexion, $idCodigo) {
    $stmt = $conexion->prepare("SELECT id_codigo, codigo, tipo, valor, activo FROM codigos_descuento WHERE id_codigo = ?");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("i", $idCodigo);
    $stmt->execute();
    $res = $stmt->get_result();
    $fila = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $fila ?: null;
}
