<?php
/**
 * Cambio de estado de un pedido desde el panel - GMG Repuestos (F2-3, E4-H3, RF-033)
 *
 * Al pasar un pedido a "aprobado" se descuenta el stock de sus productos una
 * sola vez: la columna pedidos.stock_descontado_en (BD_v2/07) marca que ya se
 * hizo. El pedido pendiente no reserva stock (F2-2), así que dos pedidos pueden
 * competir por la última unidad: al aprobar se vuelve a leer el stock con
 * bloqueo (FOR UPDATE) y, si no alcanza, el pedido no se aprueba y se le dice
 * al administrador qué falta. El stock nunca queda negativo.
 *
 * Todo (estado, stock y auditoría) va en una transacción: o queda completo o
 * no cambia nada. Requiere backend/auditoria.php cargado.
 */

/**
 * @return array ['ok' => bool, 'mensaje' => string, 'cambio' => bool]
 *               'cambio' es true si el estado realmente cambió (para avisar al cliente).
 */
function gmgPedidoCambiarEstado($conexion, $idPedido, $nuevoEstado) {
    $idPedido = (int)$idPedido;
    $error = ['ok' => false, 'mensaje' => 'No se pudo actualizar el estado.', 'cambio' => false];

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare("SELECT estado, stock_descontado_en FROM pedidos WHERE id_pedido = ? FOR UPDATE");
        if (!$stmt) {
            throw new Exception('No se pudo leer el pedido: ' . $conexion->error);
        }
        $stmt->bind_param("i", $idPedido);
        $stmt->execute();
        $pedido = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$pedido) {
            $conexion->rollback();
            return ['ok' => false, 'mensaje' => 'El pedido #' . $idPedido . ' no existe.', 'cambio' => false];
        }

        $descontar = $nuevoEstado === 'aprobado' && $pedido['stock_descontado_en'] === null;
        if ($descontar) {
            // Productos del pedido con su stock actual, bloqueados hasta el commit.
            // Las líneas de un repuesto ya eliminado (id_repuesto NULL) no descuentan nada.
            $stmt = $conexion->prepare("SELECT d.id_repuesto, d.cantidad, r.sku, r.nombre, r.stock
                                          FROM pedido_detalle d JOIN repuestos r ON r.id_repuesto = d.id_repuesto
                                         WHERE d.id_pedido = ? FOR UPDATE");
            if (!$stmt) {
                throw new Exception('No se pudo leer el stock: ' . $conexion->error);
            }
            $stmt->bind_param("i", $idPedido);
            $stmt->execute();
            $lineas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $faltantes = [];
            foreach ($lineas as $l) {
                if ((int)$l['cantidad'] > (int)$l['stock']) {
                    $faltantes[] = $l['nombre'] . ' (pide ' . (int)$l['cantidad'] . ', quedan ' . max(0, (int)$l['stock']) . ')';
                }
            }
            if ($faltantes) {
                $conexion->rollback();
                return ['ok' => false, 'cambio' => false,
                        'mensaje' => 'No se aprobó el pedido #' . $idPedido . ': no alcanza el stock de ' . implode(', ', $faltantes)
                                   . '. Repón el stock o coordina con el cliente antes de aprobarlo.'];
            }

            $stmt = $conexion->prepare("UPDATE repuestos SET stock = stock - ? WHERE id_repuesto = ? AND stock >= ?");
            foreach ($lineas as $l) {
                $cantidad = (int)$l['cantidad'];
                $idRepuesto = (int)$l['id_repuesto'];
                $stmt->bind_param("iii", $cantidad, $idRepuesto, $cantidad);
                if (!$stmt->execute() || $stmt->affected_rows !== 1) {
                    throw new Exception('No se pudo descontar el stock del repuesto ' . $idRepuesto);
                }
                $stockNuevo = (int)$l['stock'] - $cantidad;
                gmgAuditar($conexion, 'stock', 'repuesto', $idRepuesto,
                    'Stock de ' . $l['sku'] . ': ' . (int)$l['stock'] . ' a ' . $stockNuevo . ' unidades (pedido #' . $idPedido . ' aprobado)',
                    (string)(int)$l['stock'], (string)$stockNuevo);
            }
            $stmt->close();

            $stmt = $conexion->prepare("UPDATE pedidos SET estado = ?, stock_descontado_en = NOW() WHERE id_pedido = ?");
        } else {
            $stmt = $conexion->prepare("UPDATE pedidos SET estado = ? WHERE id_pedido = ?");
        }
        if (!$stmt) {
            throw new Exception('No se pudo preparar el cambio de estado: ' . $conexion->error);
        }
        $stmt->bind_param("si", $nuevoEstado, $idPedido);
        if (!$stmt->execute()) {
            throw new Exception('No se pudo cambiar el estado: ' . $stmt->error);
        }
        $stmt->close();

        $cambio = $pedido['estado'] !== $nuevoEstado;
        if ($cambio) {
            gmgAuditar($conexion, 'estado_pedido', 'pedido', $idPedido,
                'Pedido #' . $idPedido . ': estado ' . $pedido['estado'] . ' a ' . $nuevoEstado,
                $pedido['estado'], $nuevoEstado);
        }
        $conexion->commit();

        $mensaje = 'Estado del pedido #' . $idPedido . ' actualizado.';
        if ($descontar) {
            $mensaje .= ' Se descontó el stock de sus productos.';
        }
        return ['ok' => true, 'mensaje' => $mensaje, 'cambio' => $cambio];
    } catch (Throwable $ex) {
        $conexion->rollback();
        error_log('[GMG Pedidos] Pedido #' . $idPedido . ' a ' . $nuevoEstado . ': ' . $ex->getMessage());
        return $error;
    }
}
