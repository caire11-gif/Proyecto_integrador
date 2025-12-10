<?php
session_start();
if (!isset($_SESSION['nombreusuarioadmin'])) {
    header("HTTP/1.1 403 Forbidden");
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
if(!$conexion){
    header("HTTP/1.1 500 Internal Server Error");
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// FUNCIÓN PARA NORMALIZAR PRECIOS CON MÁS PRECISIÓN
function normalizarPrecio($precio) {
    return round(floatval($precio), 6);
}

// FUNCIÓN PARA FORMATEAR CANTIDADES (sin decimales innecesarios)
function formatearCantidad($cantidad) {
    $cantidad_float = floatval($cantidad);
    
    // Si es un número entero (sin parte decimal)
    if (intval($cantidad_float) == $cantidad_float) {
        return intval($cantidad_float);
    }
    // Si tiene parte decimal
    else {
        // Redondear a 2 decimales máximo
        return number_format($cantidad_float, 2, '.', '');
    }
}

// FUNCIÓN MEJORADA - CON SALDO INICIAL INCLUIDO Y CALCULO DE COSTO DE VENTAS
function procesarKardexProductoCompleto($conexion, $cod_producto, $fecha_inicio = null, $fecha_fin = null) {
    // Obtener TODOS los movimientos del producto (o del rango de fechas)
    $query = "SELECT 
                TO_CHAR(ri.fecha_inventario, 'YYYY-MM-DD HH24:MI:SS') as fecha_completa,
                TO_CHAR(ri.fecha_inventario, 'YYYY-MM-DD') AS fecha,
                EXTRACT(YEAR FROM ri.fecha_inventario) as año,
                EXTRACT(MONTH FROM ri.fecha_inventario) as mes,
                tm.nombre AS tipomovimiento_nombre,
                tm.cod_tipomovimiento,
                ri.cantidad AS cantidad,
                ri.precio_unitario AS precio_unitario,
                ri.total AS total_movimiento,
                ri.cod_inventario,
                ri.cod_notacredito,
                nc.cod_detalleventa,
                nc.cod_detallecompra
            FROM registroinventario ri
            JOIN tipomovimiento tm ON ri.cod_tipomovimiento = tm.cod_tipomovimiento
            LEFT JOIN notacredito nc ON ri.cod_notacredito = nc.cod_notacredito
            WHERE ri.cod_producto = '$cod_producto'";
    
    $fecha_condicion = "";
    if ($fecha_inicio && $fecha_fin) {
        // Asegurar que las fechas tengan formato correcto
        $fecha_inicio_formatted = date('Y-m-d', strtotime($fecha_inicio));
        $fecha_fin_formatted = date('Y-m-d', strtotime($fecha_fin));
        $fecha_condicion = " AND ri.fecha_inventario >= '$fecha_inicio_formatted 00:00:00' 
                            AND ri.fecha_inventario <= '$fecha_fin_formatted 23:59:59'";
        $query .= $fecha_condicion;
    }
    
    $query .= " ORDER BY ri.fecha_inventario ASC, ri.cod_inventario ASC";
    
    $result = pg_query($conexion, $query);
    $todos_movimientos = [];
    
    if($result && pg_num_rows($result) > 0) {
        while($row = pg_fetch_assoc($result)) {
            $todos_movimientos[] = $row;
        }
    }
    
    // ====== CALCULAR SALDO INICIAL SI HAY FILTRO DE FECHAS ======
    $saldo_inicial_row = null;
    $lotes = [];
    $saldo_total = 0;
    $historial_salidas = [];
    
    if ($fecha_inicio && $fecha_inicio_formatted) {
        // 1. Obtener movimientos ANTERIORES al filtro
        $query_anteriores = "SELECT 
                TO_CHAR(ri.fecha_inventario, 'YYYY-MM-DD HH24:MI:SS') as fecha_completa,
                TO_CHAR(ri.fecha_inventario, 'YYYY-MM-DD') AS fecha,
                tm.nombre AS tipomovimiento_nombre,
                tm.cod_tipomovimiento,
                ri.cantidad AS cantidad,
                ri.precio_unitario AS precio_unitario,
                ri.total AS total_movimiento,
                ri.cod_inventario,
                ri.cod_notacredito,
                nc.cod_detalleventa,
                nc.cod_detallecompra
            FROM registroinventario ri
            JOIN tipomovimiento tm ON ri.cod_tipomovimiento = tm.cod_tipomovimiento
            LEFT JOIN notacredito nc ON ri.cod_notacredito = nc.cod_notacredito
            WHERE ri.cod_producto = '$cod_producto'
            AND ri.fecha_inventario < '$fecha_inicio_formatted 00:00:00'
            ORDER BY ri.fecha_inventario ASC, ri.cod_inventario ASC";
        
        $result_anteriores = pg_query($conexion, $query_anteriores);
        $movimientos_anteriores = [];
        
        if($result_anteriores && pg_num_rows($result_anteriores) > 0) {
            while($row = pg_fetch_assoc($result_anteriores)) {
                $movimientos_anteriores[] = $row;
            }
        }
        
        // 2. Procesar movimientos anteriores para calcular estado inicial
        $lotes_temp = [];
        $saldo_temp = 0;
        $historial_temp = [];
        
        foreach($movimientos_anteriores as $mov) {
            $es_nota_credito = !empty($mov['cod_notacredito']);
            $es_nota_credito_venta = $es_nota_credito && !empty($mov['cod_detalleventa']);
            $es_nota_credito_compra = $es_nota_credito && !empty($mov['cod_detallecompra']);
            
            $es_entrada = stripos($mov['tipomovimiento_nombre'], 'entrada') !== false || 
                         $es_nota_credito_venta;
            $es_salida = stripos($mov['tipomovimiento_nombre'], 'salida') !== false || 
                        $es_nota_credito_compra;
            
            $precio_unitario = normalizarPrecio($mov['precio_unitario']);
            $total_movimiento = normalizarPrecio($mov['total_movimiento']);
            
            if($es_entrada) {
                // ENTRADA
                if ($es_nota_credito_venta) {
                    // NOTA DE CRÉDITO DE VENTA (devolución)
                    $cantidad_devolver = $mov['cantidad'];
                    $restante_devolver = $cantidad_devolver;
                    
                    foreach(array_reverse($historial_temp) as $detalle_salida) {
                        if ($restante_devolver <= 0) break;
                        
                        foreach(array_reverse($detalle_salida) as $lote_consumido) {
                            if ($restante_devolver <= 0) break;
                            
                            $devolver_lote = min($lote_consumido['cantidad'], $restante_devolver);
                            
                            if ($devolver_lote > 0) {
                                $precio_lote = normalizarPrecio($lote_consumido['costo_unitario']);
                                
                                $lote_existente = null;
                                foreach($lotes_temp as $precio_lote_existente => $cantidad_lote_existente) {
                                    if (normalizarPrecio($precio_lote_existente) == $precio_lote) {
                                        $lote_existente = $precio_lote_existente;
                                        break;
                                    }
                                }
                                
                                if ($lote_existente !== null) {
                                    $lotes_temp[$lote_existente] += $devolver_lote;
                                } else {
                                    $lotes_temp[$precio_lote] = $devolver_lote;
                                }
                                
                                $restante_devolver -= $devolver_lote;
                            }
                        }
                    }
                    
                } else {
                    // ENTRADA NORMAL
                    $lote_existente = null;
                    foreach($lotes_temp as $precio_lote => $cantidad_lote) {
                        if (normalizarPrecio($precio_lote) == $precio_unitario) {
                            $lote_existente = $precio_lote;
                            break;
                        }
                    }
                    
                    if ($lote_existente !== null) {
                        $lotes_temp[$lote_existente] += $mov['cantidad'];
                    } else {
                        $lotes_temp[$mov['precio_unitario']] = $mov['cantidad'];
                    }
                    
                    $saldo_temp += $total_movimiento;
                }
                
            } else if($es_salida) {
                // SALIDA
                $cantidad_salida = $mov['cantidad'];
                $costo_salida_total = 0;
                $restante = $cantidad_salida;
                $detalle_salidas_anterior = [];
                
                if ($es_nota_credito_compra) {
                    $precio_compra = normalizarPrecio($mov['precio_unitario']);
                    
                    $lotes_mismo_precio = [];
                    foreach($lotes_temp as $precio_lote => $cantidad_lote) {
                        if (normalizarPrecio($precio_lote) == $precio_compra && $cantidad_lote > 0) {
                            $lotes_mismo_precio[$precio_lote] = $cantidad_lote;
                        }
                    }
                    
                    if (!empty($lotes_mismo_precio)) {
                        foreach($lotes_mismo_precio as $precio => &$cantidad) {
                            if($restante <= 0) break;
                            
                            $usar = min($cantidad, $restante);
                            $costo_lote = $usar * normalizarPrecio($precio);
                            $costo_salida_total += $costo_lote;
                            
                            $detalle_salidas_anterior[] = [
                                'cantidad' => $usar,
                                'costo_unitario' => $precio,
                                'costo_total' => $costo_lote
                            ];
                            
                            $lotes_temp[$precio] -= $usar;
                            $restante -= $usar;
                        }
                    }
                } else {
                    // SALIDA NORMAL: PEPS
                    uksort($lotes_temp, function($a, $b) {
                        return normalizarPrecio($a) <=> normalizarPrecio($b);
                    });
                    
                    foreach($lotes_temp as $precio => &$cantidad) {
                        if($restante <= 0) break;
                        
                        if($cantidad > 0) {
                            $usar = min($cantidad, $restante);
                            $costo_lote = $usar * normalizarPrecio($precio);
                            $costo_salida_total += $costo_lote;
                            
                            $detalle_salidas_anterior[] = [
                                'cantidad' => $usar,
                                'costo_unitario' => $precio,
                                'costo_total' => $costo_lote
                            ];
                            
                            $cantidad -= $usar;
                            $restante -= $usar;
                        }
                    }
                }
                
                $lotes_temp = array_filter($lotes_temp, function($cantidad) {
                    return $cantidad > 0;
                });
                
                if($restante <= 0) {
                    // CORRECCIÓN IMPORTANTE: Recalcular el costo total exacto
                    $costo_salida_total_recalc = 0;
                    foreach($detalle_salidas_anterior as $detalle) {
                        $costo_salida_total_recalc += $detalle['cantidad'] * normalizarPrecio($detalle['costo_unitario']);
                    }
                    
                    $saldo_temp -= $costo_salida_total_recalc; // Usar el valor recalculado
                    
                    if (!$es_nota_credito_compra) {
                        $historial_temp[] = $detalle_salidas_anterior;
                    }
                }
            }
        }
        
        // 3. Preparar saldo inicial - RECALCULAR EXACTAMENTE
        $lotes = $lotes_temp;
        
        // Recalcular EXACTAMENTE el saldo total desde los lotes
        $saldo_total_recalculado = 0;
        foreach($lotes as $p => $c) {
            if($c > 0) {
                $saldo_total_recalculado += $c * normalizarPrecio($p);
            }
        }
        
        $saldo_total = $saldo_total_recalculado; // Usar el saldo recalculado
        $historial_salidas = $historial_temp;
        
        // Crear fila de saldo inicial SOLO si hay datos anteriores
        if (!empty($lotes) || $saldo_total > 0) {
            // Generar texto para unidades y costos del saldo inicial
            $saldo_unidades_texto = "";
            $saldo_costos_texto = "";
            
            foreach($lotes as $p => $c) {
                if($c > 0) {
                    if ($saldo_unidades_texto) $saldo_unidades_texto .= "\n";
                    if ($saldo_costos_texto) $saldo_costos_texto .= "\n";
                    $saldo_unidades_texto .= formatearCantidad($c) . " und";
                    $saldo_costos_texto .= "S/ " . number_format(normalizarPrecio($p), 2);
                }
            }
            
            $saldo_inicial_row = [
                'fecha' => $fecha_inicio_formatted,
                'tipo' => 'SALDO INICIAL',
                'entradas_cantidad' => 0,
                'entradas_costo_unitario' => 0,
                'entradas_costo_total' => 0,
                'salidas_cantidad' => 0,
                'salidas_costo_unitario' => 0,
                'salidas_costo_total' => 0,
                'saldo_costo_total' => $saldo_total,
                'saldo_unidades_texto' => $saldo_unidades_texto ?: "0 und",
                'saldo_costos_texto' => $saldo_costos_texto ?: "S/ 0.00",
                'es_saldo_inicial' => true,
                'error_stock' => false
            ];
        }
    }
    
    // ====== PROCESAR MOVIMIENTOS DEL PERÍODO FILTRADO ======
    $kardex_completo = [];
    $ultimo_saldo = $saldo_total; // Inicializar con saldo inicial
    $costo_ventas_total = 0; // Costo de ventas de TODO el período
    
    // Agregar saldo inicial como primera fila si existe
    if ($saldo_inicial_row) {
        $kardex_completo[] = $saldo_inicial_row;
    }
    
    // Procesar movimientos del período
    foreach($todos_movimientos as $mov) {
        $es_nota_credito = !empty($mov['cod_notacredito']);
        $es_nota_credito_venta = $es_nota_credito && !empty($mov['cod_detalleventa']);
        $es_nota_credito_compra = $es_nota_credito && !empty($mov['cod_detallecompra']);
        
        $es_entrada = stripos($mov['tipomovimiento_nombre'], 'entrada') !== false || 
                     $es_nota_credito_venta;
        $es_salida = stripos($mov['tipomovimiento_nombre'], 'salida') !== false || 
                    $es_nota_credito_compra;
        
        $precio_unitario = normalizarPrecio($mov['precio_unitario']);
        $total_movimiento = normalizarPrecio($mov['total_movimiento']);
        
        // Variable para costo de ventas de este movimiento
        $costo_ventas_movimiento = 0;
        
        if($es_entrada) {
            // ENTRADA
            if ($es_nota_credito_venta) {
                // NOTA DE CRÉDITO DE VENTA (devolución) - RESTA del costo de ventas
                $cantidad_devolver = $mov['cantidad'];
                $restante_devolver = $cantidad_devolver;
                $detalle_entrada = [];
                $costo_devolucion = 0;
                
                foreach(array_reverse($historial_salidas) as $detalle_salida) {
                    if ($restante_devolver <= 0) break;
                    
                    foreach(array_reverse($detalle_salida) as $lote_consumido) {
                        if ($restante_devolver <= 0) break;
                        
                        $devolver_lote = min($lote_consumido['cantidad'], $restante_devolver);
                        
                        if ($devolver_lote > 0) {
                            $precio_lote = normalizarPrecio($lote_consumido['costo_unitario']);
                            $costo_devolucion += $devolver_lote * $precio_lote;
                            
                            $lote_existente = null;
                            foreach($lotes as $precio_lote_existente => $cantidad_lote_existente) {
                                if (normalizarPrecio($precio_lote_existente) == $precio_lote) {
                                    $lote_existente = $precio_lote_existente;
                                    break;
                                }
                            }
                            
                            if ($lote_existente !== null) {
                                $lotes[$lote_existente] += $devolver_lote;
                            } else {
                                $lotes[$precio_lote] = $devolver_lote;
                            }
                            
                            $detalle_entrada[] = [
                                'cantidad' => $devolver_lote,
                                'costo_unitario' => $precio_lote,
                                'costo_total' => $devolver_lote * $precio_lote
                            ];
                            
                            $restante_devolver -= $devolver_lote;
                        }
                    }
                }
                
                $total_movimiento = 0;
                foreach($detalle_entrada as $detalle) {
                    $total_movimiento += $detalle['costo_total'];
                }
                
                if (count($detalle_entrada) > 0) {
                    $precio_unitario = $detalle_entrada[0]['costo_unitario'];
                }
                
                // NOTA DE CRÉDITO VENTA: RESTA del costo de ventas (porque es devolución)
                $costo_ventas_movimiento = -$costo_devolucion;
                $costo_ventas_total += $costo_ventas_movimiento; // Restar del total del período
                
            } else {
                // ENTRADA NORMAL - no afecta costo de ventas
                $lote_existente = null;
                foreach($lotes as $precio_lote => $cantidad_lote) {
                    if (normalizarPrecio($precio_lote) == $precio_unitario) {
                        $lote_existente = $precio_lote;
                        break;
                    }
                }
                
                if ($lote_existente !== null) {
                    $lotes[$lote_existente] += $mov['cantidad'];
                } else {
                    $lotes[$mov['precio_unitario']] = $mov['cantidad'];
                }
            }
            
            $saldo_total += $total_movimiento;
            
            // Generar texto para saldo (unidades y costos)
            $saldo_unidades_texto = "";
            $saldo_costos_texto = "";
            
            foreach($lotes as $p => $c) {
                if($c > 0) {
                    if ($saldo_unidades_texto) $saldo_unidades_texto .= "\n";
                    if ($saldo_costos_texto) $saldo_costos_texto .= "\n";
                    $saldo_unidades_texto .= formatearCantidad($c) . " und";
                    $saldo_costos_texto .= "S/ " . number_format(normalizarPrecio($p), 2);
                }
            }
            
            $tipo = 'ENTRADA';
            if ($es_nota_credito_venta) {
                $tipo = 'NOTA CRÉDITO VENTA';
            }
            
            $kardex_completo[] = [
                'fecha' => $mov['fecha'],
                'tipo' => $tipo,
                'entradas_cantidad' => $mov['cantidad'],
                'entradas_costo_unitario' => $precio_unitario,
                'entradas_costo_total' => $total_movimiento,
                'salidas_cantidad' => 0,
                'salidas_costo_unitario' => 0,
                'salidas_costo_total' => 0,
                'saldo_costo_total' => $saldo_total,
                'saldo_unidades_texto' => $saldo_unidades_texto,
                'saldo_costos_texto' => $saldo_costos_texto,
                'es_saldo_inicial' => false,
                'error_stock' => false
            ];
            
            $ultimo_saldo = $saldo_total; // Actualizar último saldo
            
        } else if($es_salida) {
            // SALIDA
            $cantidad_salida = $mov['cantidad'];
            $costo_salida_total = 0;
            $restante = $cantidad_salida;
            $detalle_salidas = [];
            
            if ($es_nota_credito_compra) {
                // NOTA CRÉDITO COMPRA: Descontar del lote con mismo precio de compra
                // PERO NO AFECTA EL COSTO DE VENTAS (solo afecta inventario)
                $precio_compra = normalizarPrecio($mov['precio_unitario']);
                
                $lotes_mismo_precio = [];
                foreach($lotes as $precio_lote => $cantidad_lote) {
                    if (normalizarPrecio($precio_lote) == $precio_compra && $cantidad_lote > 0) {
                        $lotes_mismo_precio[$precio_lote] = $cantidad_lote;
                    }
                }
                
                if (!empty($lotes_mismo_precio)) {
                    foreach($lotes_mismo_precio as $precio => &$cantidad) {
                        if($restante <= 0) break;
                        
                        $usar = min($cantidad, $restante);
                        $costo_lote = $usar * normalizarPrecio($precio);
                        $costo_salida_total += $costo_lote;
                        
                        $detalle_salidas[] = [
                            'cantidad' => $usar,
                            'costo_unitario' => $precio,
                            'costo_total' => $costo_lote
                        ];
                        
                        $lotes[$precio] -= $usar;
                        $restante -= $usar;
                    }
                } else {
                    uksort($lotes, function($a, $b) {
                        return normalizarPrecio($a) <=> normalizarPrecio($b);
                    });
                    
                    foreach($lotes as $precio => &$cantidad) {
                        if($restante <= 0) break;
                        
                        if($cantidad > 0) {
                            $usar = min($cantidad, $restante);
                            $costo_lote = $usar * normalizarPrecio($precio);
                            $costo_salida_total += $costo_lote;
                            
                            $detalle_salidas[] = [
                                'cantidad' => $usar,
                                'costo_unitario' => $precio,
                                'costo_total' => $costo_lote
                            ];
                            
                            $cantidad -= $usar;
                            $restante -= $usar;
                        }
                    }
                }
                
                // NOTA CRÉDITO COMPRA: NO AFECTA COSTO DE VENTAS (solo inventario)
                $costo_ventas_movimiento = 0;
                
            } else {
                // SALIDA NORMAL: PEPS - SUMA al costo de ventas
                uksort($lotes, function($a, $b) {
                    return normalizarPrecio($a) <=> normalizarPrecio($b);
                });
                
                foreach($lotes as $precio => &$cantidad) {
                    if($restante <= 0) break;
                    
                    if($cantidad > 0) {
                        $usar = min($cantidad, $restante);
                        $costo_lote = $usar * normalizarPrecio($precio);
                        $costo_salida_total += $costo_lote;
                        
                        $detalle_salidas[] = [
                            'cantidad' => $usar,
                            'costo_unitario' => $precio,
                            'costo_total' => $costo_lote
                        ];
                        
                        $cantidad -= $usar;
                        $restante -= $usar;
                    }
                }
                
                $costo_ventas_movimiento = $costo_salida_total;
                $costo_ventas_total += $costo_ventas_movimiento; // Sumar al total del período
            }
            
            // Limpiar lotes vacíos
            $lotes = array_filter($lotes, function($cantidad) {
                return $cantidad > 0;
            });
            
            $error = ($restante > 0);
            if(!$error) {
                // CORRECCIÓN: Recalcular exactamente el costo total
                $costo_salida_total_recalc = 0;
                foreach($detalle_salidas as $detalle) {
                    $costo_salida_total_recalc += $detalle['cantidad'] * normalizarPrecio($detalle['costo_unitario']);
                }
                
                $saldo_total -= $costo_salida_total_recalc; // Usar valor recalculado
                
                // Actualizar costo_ventas_movimiento con valor recalculado solo para salidas normales
                if (!$es_nota_credito_compra) {
                    $costo_ventas_movimiento = $costo_salida_total_recalc;
                    // Ajustar costo de ventas total con el valor recalculado
                    $costo_ventas_total += ($costo_salida_total_recalc - $costo_salida_total);
                    $historial_salidas[] = $detalle_salidas;
                }
            } else {
                // Si hay error, no hay costo de ventas
                $costo_ventas_movimiento = 0;
            }
            
            // Calcular costo promedio
            $costo_promedio = 0;
            if($cantidad_salida > 0 && !$error) {
                $costo_promedio = $costo_salida_total / $cantidad_salida;
            }
            
            // Generar texto para saldo (unidades y costos)
            $saldo_unidades_texto = "";
            $saldo_costos_texto = "";
            
            foreach($lotes as $p => $c) {
                if($c > 0) {
                    if ($saldo_unidades_texto) $saldo_unidades_texto .= "\n";
                    if ($saldo_costos_texto) $saldo_costos_texto .= "\n";
                    $saldo_unidades_texto .= formatearCantidad($c) . " und";
                    $saldo_costos_texto .= "S/ " . number_format(normalizarPrecio($p), 2);
                }
            }
            
            if(empty($lotes)) {
                $saldo_unidades_texto = "0 und";
                $saldo_costos_texto = "S/ 0.00";
            }
            
            $tipo = 'SALIDA';
            if ($es_nota_credito_compra) {
                $tipo = 'NOTA CRÉDITO COMPRA';
            }
            
            $kardex_completo[] = [
                'fecha' => $mov['fecha'],
                'tipo' => $tipo,
                'entradas_cantidad' => 0,
                'entradas_costo_unitario' => 0,
                'entradas_costo_total' => 0,
                'salidas_cantidad' => $cantidad_salida,
                'salidas_costo_unitario' => $error ? 0 : $costo_promedio,
                'salidas_costo_total' => $error ? 0 : $costo_salida_total,
                'saldo_costo_total' => $saldo_total,
                'saldo_unidades_texto' => $saldo_unidades_texto,
                'saldo_costos_texto' => $saldo_costos_texto,
                'error_stock' => $error,
                'es_saldo_inicial' => false
            ];
            
            $ultimo_saldo = $saldo_total; // Actualizar último saldo
        }
    }
    
    // Agregar información del costo de ventas total al array de resultados
    $kardex_completo['costo_ventas_total'] = $costo_ventas_total;
    
    return $kardex_completo;
}

// Obtener parámetros
$cod_producto = isset($_GET['cod_producto']) ? pg_escape_string($conexion, $_GET['cod_producto']) : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;

if(empty($cod_producto)) {
    header("HTTP/1.1 400 Bad Request");
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Código de producto no especificado']);
    exit;
}

// Procesar el kardex completo con filtros
$kardex_completo = procesarKardexProductoCompleto($conexion, $cod_producto, $fecha_inicio, $fecha_fin);

// Separar el costo de ventas total del array principal
$costo_ventas_total = $kardex_completo['costo_ventas_total'];
unset($kardex_completo['costo_ventas_total']);

// Devolver los datos en formato JSON con costo de ventas incluido
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'kardex' => $kardex_completo,
    'costo_ventas_total' => $costo_ventas_total
], JSON_UNESCAPED_UNICODE);
?>