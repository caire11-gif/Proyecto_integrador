<?php
session_start();
if (!isset($_SESSION['nombreusuarioadmin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
if(!$conexion){
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
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
function procesarKardexProductoSimple($conexion, $cod_producto, $fecha_inicio = null, $fecha_fin = null, $pagina = 1, $por_pagina = 10) {
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
    
    $total_movimientos = count($todos_movimientos);
    
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
            $html_unidades = "";
            $html_costos = "";
            
            foreach($lotes as $p => $c) {
                if($c > 0) {
                    $html_unidades .= "<div class='lote-separado'>" . formatearCantidad($c) . " und</div>";
                    $html_costos .= "<div class='lote-separado'>S/ " . number_format(normalizarPrecio($p), 2) . "</div>";
                }
            }
            
            $saldo_inicial_row = [
                'fecha' => $fecha_inicio_formatted,
                'tipo' => 'SALDO INICIAL',
                'html_entradas_cantidad' => '-',
                'html_entradas_costo_unitario' => '-',
                'html_entradas_costo_total' => '-',
                'html_salidas_cantidad' => '-',
                'html_salidas_costo_unitario' => '-',
                'html_salidas_costo_total' => '-',
                'entradas_cantidad' => 0,
                'entradas_costo_unitario' => 0,
                'entradas_costo_total' => 0,
                'salidas_cantidad' => 0,
                'salidas_costo_unitario' => 0,
                'salidas_costo_total' => 0,
                'saldo_costo_total' => $saldo_total,
                'saldo_unidades_html' => $html_unidades ?: "0 und",
                'saldo_costos_html' => $html_costos ?: "S/ 0.00",
                'clase_fila' => 'saldo-inicial-row',
                'es_saldo_inicial' => true,
                'error_stock' => false
            ];
        }
    }
    
    // ====== PROCESAR MOVIMIENTOS DEL PERÍODO FILTRADO ======
    $kardex_completo = [];
    $ultimo_saldo = $saldo_total; // Inicializar con saldo inicial
    $costo_ventas_total = 0; // Costo de ventas de TODO el período
    $costo_ventas_pagina = 0; // Costo de ventas solo de esta página
    
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
        
        // Variable para costo de ventas de este movimiento (solo para esta página)
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
                $costo_ventas_pagina += $costo_ventas_movimiento; // Restar de esta página
                
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
            
            // Generar HTML para display
            $html_entradas_cantidad = formatearCantidad($mov['cantidad']) . " und";
            $html_entradas_costo_unitario = "S/ " . number_format($precio_unitario, 2);
            $html_entradas_costo_total = "S/ " . number_format($total_movimiento, 2);
            
            // Generar HTML para lotes actuales
            $html_unidades = "";
            $html_costos = "";
            
            foreach($lotes as $p => $c) {
                if($c > 0) {
                    $html_unidades .= "<div class='lote-separado'>" . formatearCantidad($c) . " und</div>";
                    $html_costos .= "<div class='lote-separado'>S/ " . number_format(normalizarPrecio($p), 2) . "</div>";
                }
            }
            
            $tipo = 'ENTRADA';
            $clase_fila = 'entrada-row';
            if ($es_nota_credito_venta) {
                $tipo = 'NOTA CRÉDITO VENTA';
                $clase_fila = 'nota-credito-venta-row';
            }
            
            $kardex_completo[] = [
                'fecha' => $mov['fecha'],
                'tipo' => $tipo,
                'html_entradas_cantidad' => $html_entradas_cantidad,
                'html_entradas_costo_unitario' => $html_entradas_costo_unitario,
                'html_entradas_costo_total' => $html_entradas_costo_total,
                'html_salidas_cantidad' => '-',
                'html_salidas_costo_unitario' => '-',
                'html_salidas_costo_total' => '-',
                'entradas_cantidad' => $mov['cantidad'],
                'entradas_costo_unitario' => $precio_unitario,
                'entradas_costo_total' => $total_movimiento,
                'salidas_cantidad' => 0,
                'salidas_costo_unitario' => 0,
                'salidas_costo_total' => 0,
                'saldo_costo_total' => $saldo_total,
                'saldo_unidades_html' => $html_unidades,
                'saldo_costos_html' => $html_costos,
                'clase_fila' => $clase_fila,
                'es_saldo_inicial' => false,
                'error_stock' => false,
                'costo_ventas_movimiento' => $costo_ventas_movimiento
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
                $costo_ventas_pagina += $costo_ventas_movimiento; // Sumar a esta página
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
                    // Ajustar costo de ventas total y de página con el valor recalculado
                    $costo_ventas_total += ($costo_salida_total_recalc - $costo_salida_total);
                    $costo_ventas_pagina += ($costo_salida_total_recalc - $costo_salida_total);
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
            
            // Generar HTML para display
            $html_salidas_cantidad = formatearCantidad($cantidad_salida) . " und";
            $html_salidas_costo_unitario = !$error ? "S/ " . number_format($costo_promedio, 2) : '-';
            $html_salidas_costo_total = !$error ? "S/ " . number_format($costo_salida_total, 2) : '-';
            
            // Generar HTML para lotes actuales
            $html_unidades = "";
            $html_costos = "";
            
            foreach($lotes as $p => $c) {
                if($c > 0) {
                    $html_unidades .= "<div class='lote-separado'>" . formatearCantidad($c) . " und</div>";
                    $html_costos .= "<div class='lote-separado'>S/ " . number_format(normalizarPrecio($p), 2) . "</div>";
                }
            }
            
            if(empty($lotes)) {
                $html_unidades = "<div>" . formatearCantidad(0) . " und</div>";
                $html_costos = "<div>S/ 0.00</div>";
            }
            
            $tipo = 'SALIDA';
            $clase_fila = $error ? 'error-stock' : 'salida-row';
            if ($es_nota_credito_compra) {
                $tipo = 'NOTA CRÉDITO COMPRA';
                $clase_fila = 'nota-credito-compra-row';
            }
            
            $kardex_completo[] = [
                'fecha' => $mov['fecha'],
                'tipo' => $tipo,
                'html_entradas_cantidad' => '-',
                'html_entradas_costo_unitario' => '-',
                'html_entradas_costo_total' => '-',
                'html_salidas_cantidad' => $html_salidas_cantidad,
                'html_salidas_costo_unitario' => $html_salidas_costo_unitario,
                'html_salidas_costo_total' => $html_salidas_costo_total,
                'entradas_cantidad' => 0,
                'entradas_costo_unitario' => 0,
                'entradas_costo_total' => 0,
                'salidas_cantidad' => $cantidad_salida,
                'salidas_costo_unitario' => $error ? 0 : $costo_promedio,
                'salidas_costo_total' => $error ? 0 : $costo_salida_total,
                'saldo_costo_total' => $saldo_total,
                'saldo_unidades_html' => $html_unidades,
                'saldo_costos_html' => $html_costos,
                'error_stock' => $error,
                'clase_fila' => $clase_fila,
                'es_saldo_inicial' => false,
                'costo_ventas_movimiento' => $costo_ventas_movimiento
            ];
            
            $ultimo_saldo = $saldo_total; // Actualizar último saldo
        }
    }
    
    // ====== APLICAR PAGINACIÓN ======
    $total_movimientos_con_saldo = count($kardex_completo);
    $total_paginas = ceil($total_movimientos_con_saldo / $por_pagina);
    $offset = ($pagina - 1) * $por_pagina;
    $movimientos_pagina = array_slice($kardex_completo, $offset, $por_pagina);
    
    return [
        'movimientos' => $movimientos_pagina,
        'total_movimientos' => $total_movimientos_con_saldo,
        'total_movimientos_reales' => $total_movimientos, // Solo movimientos reales
        'total_paginas' => $total_paginas,
        'pagina_actual' => $pagina,
        'ultimo_saldo' => $ultimo_saldo,
        'costo_ventas_pagina' => $costo_ventas_pagina, // Costo de ventas de esta página
        'costo_ventas_total' => $costo_ventas_total // Costo de ventas de TODO el período
    ];
}

// Obtener parámetros
$producto_filtro = $_POST['producto_filtro'] ?? '';
$fecha_inicio = $_POST['fecha_inicio'] ?? '';
$fecha_fin = $_POST['fecha_fin'] ?? '';
$pagina_productos = intval($_POST['pagina_productos'] ?? 1);
$productos_por_pagina = intval($_POST['productos_por_pagina'] ?? 5);
$movimientos_por_pagina = intval($_POST['movimientos_por_pagina'] ?? 10);

// Obtener páginas de movimientos
$paginas_movimientos = [];
foreach ($_POST as $key => $value) {
    if (strpos($key, 'movimientos_') === 0) {
        $cod_producto = substr($key, 12);
        $paginas_movimientos[$cod_producto] = intval($value);
    }
}

// Obtener productos filtrados
$where_conditions = [];
if ($fecha_inicio && $fecha_fin) {
    $fecha_inicio_formatted = date('Y-m-d', strtotime($fecha_inicio));
    $fecha_fin_formatted = date('Y-m-d', strtotime($fecha_fin));
    
    $where_conditions[] = "EXISTS (
        SELECT 1 FROM registroinventario ri 
        WHERE ri.cod_producto = p.cod_producto 
        AND ri.fecha_inventario >= '$fecha_inicio_formatted 00:00:00'
        AND ri.fecha_inventario <= '$fecha_fin_formatted 23:59:59'
    )";
}
if ($producto_filtro) {
    $where_conditions[] = "p.cod_producto = '$producto_filtro'";
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Obtener total de productos
$total_productos_result = pg_query($conexion, "SELECT COUNT(*) as total FROM producto p $where_clause");
$total_productos = 0;
if($total_productos_result && pg_num_rows($total_productos_result) > 0) {
    $row_total = pg_fetch_assoc($total_productos_result);
    $total_productos = $row_total['total'];
}

$total_paginas_productos = ceil($total_productos / $productos_por_pagina);
$offset_productos = ($pagina_productos - 1) * $productos_por_pagina;

// Obtener productos
$result_productos = pg_query($conexion, 
    "SELECT cod_producto, nombre FROM producto p 
     $where_clause 
     ORDER BY p.cod_producto
     LIMIT $productos_por_pagina OFFSET $offset_productos");

$kardex_data = [];

// Para calcular estadísticas globales
$total_movimientos_global = 0;
$total_entradas_global = 0;
$total_salidas_global = 0;
$stock_valorizado_global = 0;
$costo_ventas_global = 0;

// Primero obtener TODOS los productos que coinciden con el filtro (sin paginación)
$result_todos_productos = pg_query($conexion, 
    "SELECT cod_producto, nombre FROM producto p 
     $where_clause 
     ORDER BY p.cod_producto");

if($result_todos_productos && pg_num_rows($result_todos_productos) > 0) {
    // Procesar TODOS los productos para calcular estadísticas globales
    while($producto = pg_fetch_assoc($result_todos_productos)) {
        // Para estadísticas globales, obtenemos el kardex completo (sin paginación)
        $kardex_result_global = procesarKardexProductoSimple($conexion, $producto['cod_producto'], $fecha_inicio, $fecha_fin, 1, 1000000);
        
        // Sumar estadísticas de este producto
        $total_movimientos_global += $kardex_result_global['total_movimientos_reales'];
        $costo_ventas_global += $kardex_result_global['costo_ventas_total'];
        
        foreach($kardex_result_global['movimientos'] as $mov) {
            // Excluir saldo inicial del cálculo
            if (!$mov['es_saldo_inicial']) {
                if($mov['entradas_cantidad'] > 0) {
                    $total_entradas_global += $mov['entradas_costo_total'];
                }
                if($mov['salidas_cantidad'] > 0 && !$mov['error_stock']) {
                    $total_salidas_global += $mov['salidas_costo_total'];
                }
            }
        }
        
        // Stock valorizado por producto: usar el ÚLTIMO saldo del período
        $stock_valorizado_global += $kardex_result_global['ultimo_saldo'];
        
        // Si este producto está en la página actual, también lo agregamos al kardex_data
        if($result_productos && pg_num_rows($result_productos) > 0) {
            // Reiniciar el puntero del resultado de productos de la página actual
            pg_result_seek($result_productos, 0);
            
            $encontrado = false;
            while($producto_pagina = pg_fetch_assoc($result_productos)) {
                if($producto_pagina['cod_producto'] == $producto['cod_producto']) {
                    $encontrado = true;
                    break;
                }
            }
            
            if($encontrado) {
                // Si está en la página actual, usar su página de movimientos específica
                $pagina_movimientos = $paginas_movimientos[$producto['cod_producto']] ?? 1;
                $kardex_result_pagina = procesarKardexProductoSimple($conexion, $producto['cod_producto'], $fecha_inicio, $fecha_fin, $pagina_movimientos, $movimientos_por_pagina);
                
                $inicio_movimientos = (($pagina_movimientos - 1) * $movimientos_por_pagina) + 1;
                $fin_movimientos = min($inicio_movimientos + $movimientos_por_pagina - 1, $kardex_result_pagina['total_movimientos']);
                
                // Calcular estadísticas por producto (para mostrar en la tabla)
                $total_entradas_producto = 0;
                $total_salidas_producto = 0;
                $costo_ventas_producto_pagina = 0; // Costo de ventas solo de esta página
                
                foreach($kardex_result_pagina['movimientos'] as $mov) {
                    // Excluir saldo inicial del cálculo
                    if (!$mov['es_saldo_inicial']) {
                        if($mov['entradas_cantidad'] > 0) {
                            $total_entradas_producto += $mov['entradas_costo_total'];
                        }
                        if($mov['salidas_cantidad'] > 0 && !$mov['error_stock']) {
                            $total_salidas_producto += $mov['salidas_costo_total'];
                        }
                        // Sumar costo de ventas de la página
                        if(isset($mov['costo_ventas_movimiento'])) {
                            $costo_ventas_producto_pagina += $mov['costo_ventas_movimiento'];
                        }
                    }
                }
                
                $kardex_data[] = [
                    'cod_producto' => $producto['cod_producto'],
                    'nombre' => $producto['nombre'],
                    'kardex' => $kardex_result_pagina['movimientos'],
                    'total_movimientos' => $kardex_result_pagina['total_movimientos_reales'], // Solo movimientos reales
                    'total_movimientos_con_saldo' => $kardex_result_pagina['total_movimientos'], // Con saldo inicial
                    'total_paginas_movimientos' => $kardex_result_pagina['total_paginas'],
                    'pagina_actual_movimientos' => $kardex_result_pagina['pagina_actual'],
                    'inicio_movimientos' => $inicio_movimientos,
                    'fin_movimientos' => $fin_movimientos,
                    'total_entradas' => $total_entradas_producto,
                    'total_salidas' => $total_salidas_producto,
                    'costo_ventas_pagina' => $costo_ventas_producto_pagina, // Costo de ventas de esta página
                    'costo_ventas_total' => $kardex_result_global['costo_ventas_total'] // Costo de ventas total del producto (TODO el período)
                ];
            }
        }
    }
}

// Configurar estadísticas globales
$estadisticas_totales = [
    'total_movimientos' => $total_movimientos_global,
    'total_entradas' => $total_entradas_global,
    'total_salidas' => $total_salidas_global,
    'stock_valorizado' => $stock_valorizado_global,
    'costo_ventas_total' => $costo_ventas_global
];

// Preparar respuesta
$response = [
    'success' => true,
    'kardex' => $kardex_data,
    'estadisticas' => $estadisticas_totales,
    'paginacion' => [
        'pagina_actual' => $pagina_productos,
        'total_paginas' => $total_paginas_productos,
        'total_productos' => $total_productos
    ],
    'paginas_movimientos' => $paginas_movimientos
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>