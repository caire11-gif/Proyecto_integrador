<?php
session_start();
if (!isset($_SESSION['nombreusuarioadmin'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

$conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
if(!$conexion){
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// FUNCIÓN PARA NORMALIZAR PRECIOS
function normalizarPrecio($precio) {
    return round(floatval($precio), 2);
}

// ESTRUCTURAS PARA DETALLES
class DetalleSalida {
    public $cantidad;
    public $costo_unitario;
    public $costo_total;
    
    public function __construct($cantidad, $costo_unitario) {
        $this->cantidad = $cantidad;
        $this->costo_unitario = $costo_unitario;
        $this->costo_total = $cantidad * $costo_unitario;
    }
}

class DetalleEntrada {
    public $cantidad;
    public $costo_unitario;
    public $costo_total;
    
    public function __construct($cantidad, $costo_unitario) {
        $this->cantidad = $cantidad;
        $this->costo_unitario = $costo_unitario;
        $this->costo_total = $cantidad * $costo_unitario;
    }
}

// FUNCIÓN PARA PROCESAR KARDEX COMPLETO
function procesarProductoKardexCompleto($conexion, $cod_producto) {
    // Obtener TODOS los movimientos del producto
    $query_todos = "SELECT 
                TO_CHAR(ri.fecha_inventario, 'YYYY-MM-DD HH24:MI:SS') as fecha_completa,
                TO_CHAR(ri.fecha_inventario, 'YYYY-MM-DD') AS fecha,
                tm.nombre AS tipomovimiento_nombre,
                tm.cod_tipomovimiento,
                ri.cantidad AS cantidad,
                ri.precio_unitario AS precio_unitario,
                ri.total AS total_movimiento,
                u.usuario AS usuario_nombre,
                ri.cod_inventario,
                ri.cod_notacredito,
                nc.cod_detalleventa,
                nc.cod_detallecompra
            FROM registroinventario ri
            JOIN tipomovimiento tm ON ri.cod_tipomovimiento = tm.cod_tipomovimiento
            JOIN usuario u ON ri.cod_usuario = u.cod_usuario
            LEFT JOIN notacredito nc ON ri.cod_notacredito = nc.cod_notacredito
            WHERE ri.cod_producto = '$cod_producto'
            ORDER BY ri.fecha_inventario ASC, ri.cod_inventario ASC";
    
    $result_todos = pg_query($conexion, $query_todos);
    $todos_movimientos = [];
    
    if($result_todos && pg_num_rows($result_todos) > 0) {
        while($row = pg_fetch_assoc($result_todos)) {
            $todos_movimientos[] = $row;
        }
    }
    
    // Si no hay movimientos, retornar vacío
    if(empty($todos_movimientos)) {
        return [];
    }
    
    // PROCESAR TODOS LOS MOVIMIENTOS
    $kardex = [];
    $lotes = [];
    $saldo_total = 0;
    $historial_salidas = [];
    
    foreach($todos_movimientos as $mov) {
        // DETERMINAR TIPO DE MOVIMIENTO
        $es_nota_credito = !empty($mov['cod_notacredito']);
        $es_nota_credito_venta = $es_nota_credito && !empty($mov['cod_detalleventa']);
        $es_nota_credito_compra = $es_nota_credito && !empty($mov['cod_detallecompra']);
        
        $es_entrada = stripos($mov['tipomovimiento_nombre'], 'entrada') !== false || 
                     $es_nota_credito_venta;
        $es_salida = stripos($mov['tipomovimiento_nombre'], 'salida') !== false || 
                    $es_nota_credito_compra;
        
        // Variables para el movimiento actual
        $precio_unitario = $mov['precio_unitario'];
        $total_movimiento = $mov['total_movimiento'];
        $detalle_entradas = [];
        $detalle_salidas = [];
        
        if($es_entrada) {
            // MANEJO DE ENTRADAS
            if ($es_nota_credito_venta) {
                // NOTA DE CRÉDITO DE VENTA (devolución): Recuperar lotes originales
                
                // Buscar en el historial de salidas almacenado
                $cantidad_devolver = $mov['cantidad'];
                $restante_devolver = $cantidad_devolver;
                
                foreach(array_reverse($historial_salidas) as $fecha_salida => $detalle_salida) {
                    if ($restante_devolver <= 0) break;
                    
                    foreach(array_reverse($detalle_salida) as $lote_consumido) {
                        if ($restante_devolver <= 0) break;
                        
                        $devolver_lote = min($lote_consumido->cantidad, $restante_devolver);
                        
                        if ($devolver_lote > 0) {
                            $precio_lote = $lote_consumido->costo_unitario;
                            
                            // Buscar si ya existe un lote con el mismo precio
                            $lote_existente = null;
                            foreach($lotes as $precio_lote_existente => $cantidad_lote_existente) {
                                if (normalizarPrecio($precio_lote_existente) == normalizarPrecio($precio_lote)) {
                                    $lote_existente = $precio_lote_existente;
                                    break;
                                }
                            }
                            
                            if ($lote_existente !== null) {
                                // Agregar al lote existente
                                $lotes[$lote_existente] += $devolver_lote;
                            } else {
                                // Crear nuevo lote
                                $lotes[$precio_lote] = $devolver_lote;
                            }
                            
                            // Agregar al detalle de entradas
                            $detalle_entradas[] = new DetalleEntrada($devolver_lote, $precio_lote);
                            
                            $restante_devolver -= $devolver_lote;
                        }
                    }
                }
                
                // Recalcular el total de movimiento basado en los lotes reales
                $total_movimiento = 0;
                foreach($detalle_entradas as $detalle) {
                    $total_movimiento += $detalle->costo_total;
                }
                
            } else {
                // ENTRADA NORMAL: Agregar al lote del mismo precio
                $precio = normalizarPrecio($precio_unitario);
                
                // Buscar si ya existe un lote con el mismo precio
                $lote_existente = null;
                foreach($lotes as $precio_lote => $cantidad_lote) {
                    if (normalizarPrecio($precio_lote) == $precio) {
                        $lote_existente = $precio_lote;
                        break;
                    }
                }
                
                if ($lote_existente !== null) {
                    // Usar el lote existente con el mismo precio
                    $lotes[$lote_existente] += $mov['cantidad'];
                } else {
                    // Crear nuevo lote
                    $lotes[$precio] = $mov['cantidad'];
                }
                
                $detalle_entradas[] = new DetalleEntrada($mov['cantidad'], $precio);
            }
            
            $saldo_total += $total_movimiento;
            
            // Generar texto para saldo (unidades y costos)
            $saldo_unidades_texto = "";
            $saldo_costos_texto = "";
            $lotes_agrupados = [];
            
            // Agrupar lotes por precio normalizado
            foreach($lotes as $p => $c) {
                $precio_normalizado = normalizarPrecio($p);
                if (!isset($lotes_agrupados[$precio_normalizado])) {
                    $lotes_agrupados[$precio_normalizado] = 0;
                }
                $lotes_agrupados[$precio_normalizado] += $c;
            }
            
            // Ordenar lotes por precio (más barato primero)
            ksort($lotes_agrupados);
            
            foreach($lotes_agrupados as $p => $c) {
                if($c > 0) {
                    if ($saldo_unidades_texto) $saldo_unidades_texto .= "\n";
                    if ($saldo_costos_texto) $saldo_costos_texto .= "\n";
                    $saldo_unidades_texto .= intval($c) . " und";
                    $saldo_costos_texto .= "S/ " . number_format($p, 2);
                }
            }
            
            // Determinar tipo para display
            $tipo = 'ENTRADA';
            if ($es_nota_credito_venta) {
                $tipo = 'NOTA CRÉDITO VENTA';
            }
            
            $kardex[] = [
                'fecha' => $mov['fecha'],
                'tipo' => $tipo,
                'usuario' => $mov['usuario_nombre'],
                'entradas_cantidad' => $mov['cantidad'],
                'entradas_costo_unitario' => $precio_unitario,
                'entradas_costo_total' => $total_movimiento,
                'salidas_cantidad' => 0,
                'salidas_costo_unitario' => 0,
                'salidas_costo_total' => 0,
                'saldo_costo_total' => $saldo_total,
                'saldo_unidades_texto' => $saldo_unidades_texto,
                'saldo_costos_texto' => $saldo_costos_texto,
                'error_stock' => false
            ];
            
        } else if($es_salida) {
            // SALIDA: PEPS simple con detalle por lote
            $cantidad_salida = $mov['cantidad'];
            $costo_salida_total = 0;
            $restante = $cantidad_salida;
            $detalle_salidas = [];
            
            if ($es_nota_credito_compra) {
                // NOTA DE CRÉDITO DE COMPRA: Descontar del lote con el mismo precio de compra
                $precio_compra = normalizarPrecio($precio_unitario);
                
                // Buscar lotes que tengan el mismo precio
                $lotes_mismo_precio = [];
                foreach($lotes as $precio_lote => $cantidad_lote) {
                    if (normalizarPrecio($precio_lote) == $precio_compra && $cantidad_lote > 0) {
                        $lotes_mismo_precio[$precio_lote] = $cantidad_lote;
                    }
                }
                
                if (!empty($lotes_mismo_precio)) {
                    // Consumir de los lotes con el mismo precio
                    foreach($lotes_mismo_precio as $precio => &$cantidad) {
                        if($restante <= 0) break;
                        
                        $usar = min($cantidad, $restante);
                        $costo_lote = $usar * $precio;
                        $costo_salida_total += $costo_lote;
                        
                        // Agregar al detalle de salidas
                        $detalle_salidas[] = new DetalleSalida($usar, $precio);
                        
                        $lotes[$precio] -= $usar;
                        $restante -= $usar;
                    }
                } else {
                    // Si no hay lotes con el mismo precio, usar PEPS normal
                    uksort($lotes, function($a, $b) {
                        return normalizarPrecio($a) <=> normalizarPrecio($b);
                    });
                    
                    foreach($lotes as $precio => &$cantidad) {
                        if($restante <= 0) break;
                        
                        if($cantidad > 0) {
                            $usar = min($cantidad, $restante);
                            $costo_lote = $usar * $precio;
                            $costo_salida_total += $costo_lote;
                            
                            // Agregar al detalle de salidas
                            $detalle_salidas[] = new DetalleSalida($usar, $precio);
                            
                            $cantidad -= $usar;
                            $restante -= $usar;
                        }
                    }
                }
            } else {
                // SALIDA NORMAL: PEPS (más barato primero)
                uksort($lotes, function($a, $b) {
                    return normalizarPrecio($a) <=> normalizarPrecio($b);
                });
                
                foreach($lotes as $precio => &$cantidad) {
                    if($restante <= 0) break;
                    
                    if($cantidad > 0) {
                        $usar = min($cantidad, $restante);
                        $costo_lote = $usar * $precio;
                        $costo_salida_total += $costo_lote;
                        
                        // Agregar al detalle de salidas
                        $detalle_salidas[] = new DetalleSalida($usar, $precio);
                        
                        $cantidad -= $usar;
                        $restante -= $usar;
                    }
                }
            }
            
            // Limpiar lotes vacíos
            $lotes = array_filter($lotes, function($cantidad) {
                return $cantidad > 0;
            });
            
            // Verificar si alcanza
            $error = ($restante > 0);
            if(!$error) {
                $saldo_total -= $costo_salida_total;
                
                // Guardar el detalle de esta salida en el historial para posibles devoluciones (solo salidas normales)
                if (!$es_nota_credito_compra) {
                    $historial_salidas[$mov['fecha_completa']] = $detalle_salidas;
                }
            }
            
            // Calcular costo promedio de salida (solo para referencia)
            $costo_promedio = 0;
            if($cantidad_salida > 0 && !$error) {
                $costo_promedio = $costo_salida_total / $cantidad_salida;
            }
            
            // Generar texto para saldo (unidades y costos)
            $saldo_unidades_texto = "";
            $saldo_costos_texto = "";
            $lotes_agrupados = [];
            
            // Agrupar lotes por precio normalizado
            foreach($lotes as $p => $c) {
                $precio_normalizado = normalizarPrecio($p);
                if (!isset($lotes_agrupados[$precio_normalizado])) {
                    $lotes_agrupados[$precio_normalizado] = 0;
                }
                $lotes_agrupados[$precio_normalizado] += $c;
            }
            
            // Ordenar lotes por precio
            ksort($lotes_agrupados);
            
            foreach($lotes_agrupados as $p => $c) {
                if($c > 0) {
                    if ($saldo_unidades_texto) $saldo_unidades_texto .= "\n";
                    if ($saldo_costos_texto) $saldo_costos_texto .= "\n";
                    $saldo_unidades_texto .= intval($c) . " und";
                    $saldo_costos_texto .= "S/ " . number_format($p, 2);
                }
            }
            
            if(empty($lotes_agrupados)) {
                $saldo_unidades_texto = "0 und";
                $saldo_costos_texto = "S/ 0.00";
            }
            
            // Determinar tipo para display
            $tipo = 'SALIDA';
            if ($es_nota_credito_compra) {
                $tipo = 'NOTA CRÉDITO COMPRA';
            }
            
            $kardex[] = [
                'fecha' => $mov['fecha'],
                'tipo' => $tipo,
                'usuario' => $mov['usuario_nombre'],
                'entradas_cantidad' => 0,
                'entradas_costo_unitario' => 0,
                'entradas_costo_total' => 0,
                'salidas_cantidad' => $cantidad_salida,
                'salidas_costo_unitario' => $error ? 0 : $costo_promedio,
                'salidas_costo_total' => $error ? 0 : $costo_salida_total,
                'saldo_costo_total' => $saldo_total,
                'saldo_unidades_texto' => $saldo_unidades_texto,
                'saldo_costos_texto' => $saldo_costos_texto,
                'error_stock' => $error
            ];
        }
    }
    
    return $kardex;
}

// Obtener el código del producto desde la URL
$cod_producto = isset($_GET['cod_producto']) ? pg_escape_string($conexion, $_GET['cod_producto']) : '';

if(empty($cod_producto)) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['error' => 'Código de producto no especificado']);
    exit;
}

// Procesar el kardex completo
$kardex_completo = procesarProductoKardexCompleto($conexion, $cod_producto);

// Devolver los datos en formato JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($kardex_completo, JSON_UNESCAPED_UNICODE);
?>