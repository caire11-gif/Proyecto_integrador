<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

include '../conexion.php';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $proveedor = $input['proveedor'] ?? '';
        $fecha_entrada = $input['fecha_entrada'] ?? '';
        $numero_factura = $input['numero_factura'] ?? '';
        $tipo_comprobante = $input['tipo_comprobante'] ?? '';
        $productos = $input['productos'] ?? [];
        $cantidades = $input['cantidades'] ?? [];
        $precios_caja = $input['precios_caja'] ?? [];
        
        // Validar datos requeridos
        if(empty($proveedor) || empty($fecha_entrada)) {
            throw new Exception("Proveedor y fecha son obligatorios");
        }

        if(empty($productos) || count(array_filter($productos)) == 0) {
            throw new Exception("Debe agregar al menos un producto");
        }

        // Iniciar transacción
        pg_query($conexion, "BEGIN");
        
        // 1. GENERAR UN SOLO CÓDIGO DE COMPRA PARA TODOS LOS PRODUCTOS
        $cod_compra = generarCodigo('COM');
        
        // 2. INSERTAR EN COMPRA (UNA SOLA VEZ)
        $query_compra = "INSERT INTO compra (cod_compra, cod_usuario, cod_proveedor, cod_metodopago, cod_tiporeporte, fecha_compra) 
                        VALUES ('$cod_compra', 'user005', '$proveedor', 'mp001', 'rep001', '$fecha_entrada')";
        
        if(!pg_query($conexion, $query_compra)) {
            throw new Exception("Error al insertar compra: " . pg_last_error($conexion));
        }

        // 3. PROCESAR CADA PRODUCTO (MÚLTIPLES DETALLES PARA LA MISMA COMPRA)
        foreach($productos as $index => $cod_producto) {
            if(!empty($cod_producto)) {
                $cantidad_cajas = intval($cantidades[$index]);
                $precio_por_caja = floatval($precios_caja[$index]);
                
                // Obtener unidades por caja del producto
                $result_unidades = pg_query($conexion, "SELECT unidades_por_caja FROM producto WHERE cod_producto = '$cod_producto'");
                if($result_unidades && pg_num_rows($result_unidades) > 0) {
                    $row_unidades = pg_fetch_assoc($result_unidades);
                    $unidades_por_caja_producto = $row_unidades['unidades_por_caja'];
                } else {
                    $unidades_por_caja_producto = 1;
                }
                
                // CALCULAR PRECIO UNITARIO Y TOTAL CORREGIDOS
                $precio_unitario = $precio_por_caja / $unidades_por_caja_producto;
                
                // CALCULO CORREGIDO: Total = cantidad_cajas * precio_por_caja
                $total = $cantidad_cajas * $precio_por_caja;
                $cantidad_unidades = $cantidad_cajas * $unidades_por_caja_producto;
                
                // Validar datos del producto
                if($cantidad_cajas <= 0) {
                    throw new Exception("La cantidad debe ser mayor a 0");
                }
                if($precio_por_caja < 0) {
                    throw new Exception("El precio por caja no puede ser negativo");
                }

                // Generar código único para cada detalle
                $cod_detallecompra = generarCodigo('DET' . $index);
                $cod_inventario = generarCodigo('INV' . $index);
                $cod_movimiento = generarCodigo('MOV' . $index);
                $cod_historial = generarCodigo('HIS' . $index);
                
                // 4. Insertar en detallecompra (con el MISMO cod_compra para todos)
                $query_detalle = "INSERT INTO detallecompra (cod_detallecompra, cod_compra, cod_producto, cantidad_cajas, precio_unitario, total) 
                                 VALUES ('$cod_detallecompra', '$cod_compra', '$cod_producto', $cantidad_cajas, $precio_unitario, $total)";
                
                if(!pg_query($conexion, $query_detalle)) {
                    throw new Exception("Error al insertar detalle de compra: " . pg_last_error($conexion));
                }
                
                // 5. Actualizar stock en producto
                $unidades_agregadas = $cantidad_cajas * $unidades_por_caja_producto;
                $query_update_stock = "UPDATE producto SET stock = stock + $unidades_agregadas WHERE cod_producto = '$cod_producto'";
                
                if(!pg_query($conexion, $query_update_stock)) {
                    throw new Exception("Error al actualizar stock: " . pg_last_error($conexion));
                }

                // 6. Insertar en registroinventario
                $query_inventario = "INSERT INTO registroinventario (cod_inventario, cod_usuario, fecha_inventario, cod_producto, cod_tipomovimiento, cantidad, precio_unitario, total) 
                                   VALUES ('$cod_inventario', 'user005', '$fecha_entrada', '$cod_producto', 'mov001', $cantidad_unidades, $precio_unitario, $total)";
                
                if(!pg_query($conexion, $query_inventario)) {
                    throw new Exception("Error al insertar en registro inventario: " . pg_last_error($conexion));
                }
                
                // 7. Insertar en movimiento
                $observacion = "Entrada de proveedor - Compra: $cod_compra - $cantidad_cajas cajas ($unidades_agregadas unidades)";
                $query_movimiento = "INSERT INTO movimiento (cod_movimiento, cod_producto, cod_tipomovimiento, fecha_movimiento, cod_usuario, observacion) 
                                   VALUES ('$cod_movimiento', '$cod_producto', 'mov001', '$fecha_entrada', 'user005', '$observacion')";
                
                if(!pg_query($conexion, $query_movimiento)) {
                    throw new Exception("Error al insertar movimiento: " . pg_last_error($conexion));
                }
                
                // 8. Insertar en historialproductos
                $observacion_historial = "Entrada de $cantidad_cajas cajas ($unidades_agregadas unidades) - Compra: $cod_compra - Total: S/ $total";
                $query_historial = "INSERT INTO historialproductos (cod_historialproductos, cod_usuario, cod_producto, cod_tipoaccion, observacion) 
                                  VALUES ('$cod_historial', 'user005', '$cod_producto', 'acc001', '$observacion_historial')";
                
                if(!pg_query($conexion, $query_historial)) {
                    throw new Exception("Error al insertar historial: " . pg_last_error($conexion));
                }
            }
        }
        
        // Confirmar transacción
        pg_query($conexion, "COMMIT");
        
        echo json_encode([
            'success' => true,
            'message' => "Entrada registrada correctamente. Stock actualizado. Código de compra: $cod_compra",
            'cod_compra' => $cod_compra
        ]);
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        pg_query($conexion, "ROLLBACK");
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// Función para generar códigos únicos de exactamente 10 caracteres
function generarCodigo($prefijo) {
    $numero = str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);
    return substr($prefijo, 0, 3) . $numero;
}

pg_close($conexion);
?>