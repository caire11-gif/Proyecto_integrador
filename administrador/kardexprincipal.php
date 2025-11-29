<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Sistema de Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/administrador-kardexprincipal/kardex.css">
    <link rel="stylesheet" href="css/administrador-estilo.css">
    <link rel="stylesheet" href="css/administrador-boton/boton.css">
    <!-- Librerías para exportación -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <style>
        .bg-success-light { background-color: #d4edda !important; }
        .bg-danger-light { background-color: #f8d7da !important; }
        .bg-primary-light { background-color: #cce7ff !important; }
        .table th { vertical-align: middle; }
        .kardex-table { font-size: 0.8rem; }
        .kardex-table th, .kardex-table td { padding: 0.3rem; text-align: center; }
        .entrada-row { background-color: #f8fff8; }
        .salida-row { background-color: #fff8f8; }
        .nota-credito-venta-row { background-color: #f0fff0; border-left: 3px solid #28a745; }
        .nota-credito-compra-row { background-color: #fff0f0; border-left: 3px solid #dc3545; }
        .saldo-unitario { font-weight: bold; color: #007bff; }
        .error-stock { background-color: #ffe6e6; color: #d63031; }
        .lote-separado { padding: 2px 0; }
        .debug-info { background-color: #fff3cd; border-left: 3px solid #ffc107; }
        .lote-item { border-bottom: 1px dashed #dee2e6; padding: 2px 0; }
        .lote-item:last-child { border-bottom: none; }
        .pagination-container { display: flex; justify-content: center; margin-top: 20px; }
        .pagination-btn { margin: 0 5px; }
        .kardex-pagination { display: flex; justify-content: center; align-items: center; margin: 10px 0; }
        .kardex-pagination button { margin: 0 2px; padding: 2px 8px; font-size: 0.8rem; }
        .product-kardex { display: none; }
        .product-kardex.active { display: block; }
        .movimientos-info { font-size: 0.8rem; color: #6c757d; }
        .loading { opacity: 0.7; pointer-events: none; }
        .estadisticas-producto { display: none; }
        .estadisticas-global { display: block; }
        .export-buttons { margin-bottom: 15px; }
        .export-buttons .btn { margin-right: 5px; }
    </style>
</head>
<body>
    <?php
    $conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
    if(!$conexion){
        echo "Un error de conexión ocurrió.";
    }

    session_start();
    $usuarioadmin = $_SESSION['nombreusuarioadmin'];
    $apellidoadmin = $_SESSION['apellidousuarioadmin'];

    $inicialNombre = substr($usuarioadmin, 0, 1);
    $inicialApellido = substr($apellidoadmin, 0, 1);

    if (!isset($_SESSION['nombreusuarioadmin'])) {
        header("Location: ../login.php");
        exit;
    }

    // Obtener productos
    $result1 = pg_query($conexion, "SELECT cod_producto, nombre FROM producto");
    if(!$result1) echo "Error al seleccionar los productos.";

    // FUNCIÓN PARA NORMALIZAR PRECIOS Y EVITAR LOTES DUPLICADOS
    function normalizarPrecio($precio) {
        return round(floatval($precio), 2);
    }

    // ESTRUCTURA PARA ALMACENAR DETALLES DE SALIDAS POR LOTE
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

    // ESTRUCTURA PARA ALMACENAR DETALLES DE ENTRADAS POR LOTE
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

    // FUNCIÓN MEJORADA - PROCESAR KARDEX CON PAGINACIÓN INTERCONECTADA
    function procesarProductoKardexPaginado($conexion, $cod_producto, $pagina_movimientos = 1, $movimientos_por_pagina = 10) {
        // PRIMERO: Obtener TODOS los movimientos del producto para calcular el estado acumulado
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
        
        $total_movimientos = count($todos_movimientos);
        
        // Si no hay movimientos, retornar vacío
        if(empty($todos_movimientos)) {
            return [
                'movimientos' => [],
                'total_movimientos' => 0,
                'total_paginas' => 0,
                'pagina_actual' => $pagina_movimientos
            ];
        }
        
        // Calcular offset para la paginación
        $offset = ($pagina_movimientos - 1) * $movimientos_por_pagina;
        
        // Obtener solo los movimientos de la página actual
        $movimientos_pagina = array_slice($todos_movimientos, $offset, $movimientos_por_pagina);
        
        // Si no hay movimientos en esta página, retornar vacío
        if(empty($movimientos_pagina)) {
            return [
                'movimientos' => [],
                'total_movimientos' => $total_movimientos,
                'total_paginas' => ceil($total_movimientos / $movimientos_por_pagina),
                'pagina_actual' => $pagina_movimientos
            ];
        }
        
        // CALCULAR EL ESTADO INICIAL (lotes y saldo) procesando todos los movimientos anteriores
        $lotes_iniciales = [];
        $saldo_total_inicial = 0;
        $historial_salidas_inicial = [];
        
        // Procesar todos los movimientos hasta el offset actual
        $movimientos_anteriores = array_slice($todos_movimientos, 0, $offset);
        
        foreach($movimientos_anteriores as $mov_anterior) {
            // DETERMINAR TIPO DE MOVIMIENTO
            $es_nota_credito = !empty($mov_anterior['cod_notacredito']);
            $es_nota_credito_venta = $es_nota_credito && !empty($mov_anterior['cod_detalleventa']);
            $es_nota_credito_compra = $es_nota_credito && !empty($mov_anterior['cod_detallecompra']);
            
            $es_entrada = stripos($mov_anterior['tipomovimiento_nombre'], 'entrada') !== false || 
                         $es_nota_credito_venta;
            $es_salida = stripos($mov_anterior['tipomovimiento_nombre'], 'salida') !== false || 
                        $es_nota_credito_compra;
            
            if($es_entrada) {
                // MANEJO DE ENTRADAS
                if ($es_nota_credito_venta) {
                    // NOTA DE CRÉDITO DE VENTA (devolución): Recuperar lotes originales
                    $cantidad_devolver = $mov_anterior['cantidad'];
                    $restante_devolver = $cantidad_devolver;
                    
                    // Buscar en el historial de salidas almacenado
                    foreach(array_reverse($historial_salidas_inicial) as $fecha_salida => $detalle_salida) {
                        if ($restante_devolver <= 0) break;
                        
                        foreach(array_reverse($detalle_salida) as $lote_consumido) {
                            if ($restante_devolver <= 0) break;
                            
                            $devolver_lote = min($lote_consumido->cantidad, $restante_devolver);
                            
                            if ($devolver_lote > 0) {
                                $precio_lote = $lote_consumido->costo_unitario;
                                
                                // Buscar si ya existe un lote con el mismo precio
                                $lote_existente = null;
                                foreach($lotes_iniciales as $precio_lote_existente => $cantidad_lote_existente) {
                                    if (normalizarPrecio($precio_lote_existente) == normalizarPrecio($precio_lote)) {
                                        $lote_existente = $precio_lote_existente;
                                        break;
                                    }
                                }
                                
                                if ($lote_existente !== null) {
                                    // Agregar al lote existente
                                    $lotes_iniciales[$lote_existente] += $devolver_lote;
                                } else {
                                    // Crear nuevo lote
                                    $lotes_iniciales[$precio_lote] = $devolver_lote;
                                }
                                
                                $restante_devolver -= $devolver_lote;
                            }
                        }
                    }
                    
                    // Recalcular el total
                    $total_movimiento_anterior = 0;
                    foreach($lotes_iniciales as $precio => $cantidad) {
                        $total_movimiento_anterior += $cantidad * $precio;
                    }
                    $saldo_total_inicial = $total_movimiento_anterior;
                    
                } else {
                    // ENTRADA NORMAL: Agregar al lote del mismo precio
                    $precio = normalizarPrecio($mov_anterior['precio_unitario']);
                    
                    // Buscar si ya existe un lote con el mismo precio
                    $lote_existente = null;
                    foreach($lotes_iniciales as $precio_lote => $cantidad_lote) {
                        if (normalizarPrecio($precio_lote) == $precio) {
                            $lote_existente = $precio_lote;
                            break;
                        }
                    }
                    
                    if ($lote_existente !== null) {
                        // Usar el lote existente con el mismo precio
                        $lotes_iniciales[$lote_existente] += $mov_anterior['cantidad'];
                    } else {
                        // Crear nuevo lote
                        $lotes_iniciales[$precio] = $mov_anterior['cantidad'];
                    }
                    
                    $saldo_total_inicial += $mov_anterior['total_movimiento'];
                }
                
            } else if($es_salida) {
                // SALIDA: PEPS simple con detalle por lote
                $cantidad_salida = $mov_anterior['cantidad'];
                $costo_salida_total = 0;
                $restante = $cantidad_salida;
                $detalle_salidas_anterior = [];
                
                if ($es_nota_credito_compra) {
                    // NOTA DE CRÉDITO DE COMPRA: Descontar del lote con el mismo precio de compra
                    $precio_compra = normalizarPrecio($mov_anterior['precio_unitario']);
                    
                    // Buscar lotes que tengan el mismo precio
                    $lotes_mismo_precio = [];
                    foreach($lotes_iniciales as $precio_lote => $cantidad_lote) {
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
                            $detalle_salidas_anterior[] = new DetalleSalida($usar, $precio);
                            
                            $lotes_iniciales[$precio] -= $usar;
                            $restante -= $usar;
                        }
                    }
                } else {
                    // SALIDA NORMAL: PEPS (más barato primero)
                    uksort($lotes_iniciales, function($a, $b) {
                        return normalizarPrecio($a) <=> normalizarPrecio($b);
                    });
                    
                    foreach($lotes_iniciales as $precio => &$cantidad) {
                        if($restante <= 0) break;
                        
                        if($cantidad > 0) {
                            $usar = min($cantidad, $restante);
                            $costo_lote = $usar * $precio;
                            $costo_salida_total += $costo_lote;
                            
                            // Agregar al detalle de salidas
                            $detalle_salidas_anterior[] = new DetalleSalida($usar, $precio);
                            
                            $cantidad -= $usar;
                            $restante -= $usar;
                        }
                    }
                }
                
                // Limpiar lotes vacíos
                $lotes_iniciales = array_filter($lotes_iniciales, function($cantidad) {
                    return $cantidad > 0;
                });
                
                // Actualizar saldo
                if($restante <= 0) {
                    $saldo_total_inicial -= $costo_salida_total;
                    
                    // Guardar el detalle de esta salida en el historial para posibles devoluciones (solo salidas normales)
                    if (!$es_nota_credito_compra) {
                        $historial_salidas_inicial[$mov_anterior['fecha_completa']] = $detalle_salidas_anterior;
                    }
                }
            }
        }
        
        // AHORA PROCESAR LOS MOVIMIENTOS DE LA PÁGINA ACTUAL CON EL ESTADO INICIAL CORRECTO
        $kardex = [];
        $lotes = $lotes_iniciales;
        $saldo_total = $saldo_total_inicial;
        $historial_salidas = $historial_salidas_inicial;
        
        foreach($movimientos_pagina as $mov) {
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
                
                // Generar HTML para mostrar lotes actuales
                $html_unidades = "";
                $html_costos = "";
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
                        $html_unidades .= "<div class='lote-separado'>" . intval($c) . " und</div>";
                        $html_costos .= "<div class='lote-separado'>S/ " . number_format($p, 2) . "</div>";
                    }
                }
                
                // Generar HTML para detalle de entradas
                $html_entradas_cantidad = "";
                $html_entradas_costo_unitario = "";
                
                if (!empty($detalle_entradas)) {
                    foreach($detalle_entradas as $detalle) {
                        $html_entradas_cantidad .= "<div class='lote-item'>" . intval($detalle->cantidad) . "</div>";
                        $html_entradas_costo_unitario .= "<div class='lote-item'>S/ " . number_format($detalle->costo_unitario, 2) . "</div>";
                    }
                } else {
                    $html_entradas_cantidad = intval($mov['cantidad']);
                    $html_entradas_costo_unitario = "S/ " . number_format($precio_unitario, 2);
                }
                
                // Determinar tipo para display
                $tipo = 'ENTRADA';
                $clase_fila = 'entrada-row';
                if ($es_nota_credito_venta) {
                    $tipo = 'NOTA CRÉDITO VENTA';
                    $clase_fila = 'nota-credito-venta-row';
                }
                
                $kardex[] = [
                    'fecha' => $mov['fecha'],
                    'tipo' => $tipo,
                    'usuario' => $mov['usuario_nombre'],
                    'entradas_cantidad' => $mov['cantidad'],
                    'entradas_costo_unitario' => $precio_unitario,
                    'entradas_costo_total' => $total_movimiento,
                    'entradas_detalle' => $detalle_entradas,
                    'salidas_cantidad' => 0,
                    'salidas_detalle' => [],
                    'salidas_costo_total' => 0,
                    'saldo_costo_total' => $saldo_total,
                    'saldo_unidades_html' => $html_unidades,
                    'saldo_costos_html' => $html_costos,
                    'html_entradas_cantidad' => $html_entradas_cantidad,
                    'html_entradas_costo_unitario' => $html_entradas_costo_unitario,
                    'clase_fila' => $clase_fila
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
                
                // Mostrar lotes que quedan después de la salida (agrupados por precio)
                $html_unidades = "";
                $html_costos = "";
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
                        $html_unidades .= "<div class='lote-separado'>" . intval($c) . " und</div>";
                        $html_costos .= "<div class='lote-separado'>S/ " . number_format($p, 2) . "</div>";
                    }
                }
                
                if(empty($lotes_agrupados)) {
                    $html_unidades = "<div>0 und</div>";
                    $html_costos = "<div>S/ 0.00</div>";
                }
                
                // Generar HTML para mostrar detalle de salidas
                $html_salidas_cantidad = "";
                $html_salidas_costo_unitario = "";
                
                if (!$error && !empty($detalle_salidas)) {
                    foreach($detalle_salidas as $detalle) {
                        $html_salidas_cantidad .= "<div class='lote-item'>" . intval($detalle->cantidad) . "</div>";
                        $html_salidas_costo_unitario .= "<div class='lote-item'>S/ " . number_format($detalle->costo_unitario, 2) . "</div>";
                    }
                } else if ($error) {
                    $html_salidas_cantidad = "<strong class='text-danger'>" . intval($cantidad_salida) . "*</strong>";
                    $html_salidas_costo_unitario = "<strong class='text-danger'>ERROR</strong>";
                } else {
                    $html_salidas_cantidad = intval($cantidad_salida);
                    $html_salidas_costo_unitario = "S/ " . number_format($costo_promedio, 2);
                }
                
                // Determinar tipo para display
                $tipo = 'SALIDA';
                $clase_fila = $error ? 'error-stock' : 'salida-row';
                if ($es_nota_credito_compra) {
                    $tipo = 'NOTA CRÉDITO COMPRA';
                    $clase_fila = 'nota-credito-compra-row';
                }
                
                $kardex[] = [
                    'fecha' => $mov['fecha'],
                    'tipo' => $tipo,
                    'usuario' => $mov['usuario_nombre'],
                    'entradas_cantidad' => 0,
                    'entradas_costo_unitario' => 0,
                    'entradas_costo_total' => 0,
                    'entradas_detalle' => [],
                    'salidas_cantidad' => $cantidad_salida,
                    'salidas_costo_unitario' => $error ? 0 : $costo_promedio,
                    'salidas_detalle' => $detalle_salidas,
                    'salidas_costo_total' => $error ? 0 : $costo_salida_total,
                    'saldo_costo_total' => $saldo_total,
                    'saldo_unidades_html' => $html_unidades,
                    'saldo_costos_html' => $html_costos,
                    'html_salidas_cantidad' => $html_salidas_cantidad,
                    'html_salidas_costo_unitario' => $html_salidas_costo_unitario,
                    'error_stock' => $error,
                    'clase_fila' => $clase_fila
                ];
            }
        }
        
        $total_paginas_movimientos = ceil($total_movimientos / $movimientos_por_pagina);
        
        return [
            'movimientos' => $kardex,
            'total_movimientos' => $total_movimientos,
            'total_paginas' => $total_paginas_movimientos,
            'pagina_actual' => $pagina_movimientos
        ];
    }

    // FUNCIÓN PARA OBTENER ESTADÍSTICAS POR PRODUCTO
    function obtenerEstadisticasProducto($conexion, $cod_producto) {
        $query = "SELECT 
            COUNT(*) as total_movimientos,
            COALESCE(SUM(CASE WHEN tm.nombre ILIKE '%entrada%' OR (ri.cod_notacredito IS NOT NULL AND nc.cod_detalleventa IS NOT NULL) THEN ri.total ELSE 0 END), 0) as total_entradas,
            COALESCE(SUM(CASE WHEN (tm.nombre ILIKE '%salida%' OR (ri.cod_notacredito IS NOT NULL AND nc.cod_detallecompra IS NOT NULL)) AND ri.cantidad > 0 THEN ri.total ELSE 0 END), 0) as total_salidas
        FROM registroinventario ri
        JOIN tipomovimiento tm ON ri.cod_tipomovimiento = tm.cod_tipomovimiento
        LEFT JOIN notacredito nc ON ri.cod_notacredito = nc.cod_notacredito
        WHERE ri.cod_producto = '$cod_producto'";
        
        $result = pg_query($conexion, $query);
        if($result && pg_num_rows($result) > 0) {
            return pg_fetch_assoc($result);
        }
        
        return [
            'total_movimientos' => 0,
            'total_entradas' => 0,
            'total_salidas' => 0
        ];
    }

    // Obtener datos del kardex PAGINADOS
    $productos_kardex = [];
    
    // Configuración de paginación
    $productos_por_pagina = 5;
    $movimientos_por_pagina = 10;
    
    // Paginación de productos
    $total_productos_result = pg_query($conexion, "SELECT COUNT(*) as total FROM producto");
    $total_productos = 0;
    if($total_productos_result && pg_num_rows($total_productos_result) > 0) {
        $row_total = pg_fetch_assoc($total_productos_result);
        $total_productos = $row_total['total'];
    }
    
    $total_paginas_productos = ceil($total_productos / $productos_por_pagina);
    $pagina_actual_productos = isset($_GET['pagina_productos']) ? max(1, min($total_paginas_productos, intval($_GET['pagina_productos']))) : 1;
    $offset_productos = ($pagina_actual_productos - 1) * $productos_por_pagina;
    
    // Obtener productos de la página actual
    $result_productos = pg_query($conexion, "SELECT cod_producto, nombre FROM producto LIMIT $productos_por_pagina OFFSET $offset_productos");
    if($result_productos && pg_num_rows($result_productos) > 0) {
        while($producto = pg_fetch_assoc($result_productos)) {
            $pagina_movimientos = isset($_GET['movimientos_' . $producto['cod_producto']]) ? intval($_GET['movimientos_' . $producto['cod_producto']]) : 1;
            $kardex_data = procesarProductoKardexPaginado($conexion, $producto['cod_producto'], $pagina_movimientos, $movimientos_por_pagina);
            if(!empty($kardex_data['movimientos'])) {
                $productos_kardex[$producto['cod_producto']] = [
                    'nombre' => $producto['nombre'],
                    'kardex' => $kardex_data['movimientos'],
                    'total_movimientos' => $kardex_data['total_movimientos'],
                    'total_paginas_movimientos' => $kardex_data['total_paginas'],
                    'pagina_actual_movimientos' => $kardex_data['pagina_actual']
                ];
            }
        }
    }

    // Calcular estadísticas GLOBALES
    $total_movimientos_global = 0;
    $total_entradas_global = 0;
    $total_salidas_global = 0;
    $stock_valorizado_global = 0;
    
    $query_estadisticas = "SELECT 
        COUNT(*) as total_movimientos,
        COALESCE(SUM(CASE WHEN tm.nombre ILIKE '%entrada%' OR (ri.cod_notacredito IS NOT NULL AND nc.cod_detalleventa IS NOT NULL) THEN ri.total ELSE 0 END), 0) as total_entradas,
        COALESCE(SUM(CASE WHEN (tm.nombre ILIKE '%salida%' OR (ri.cod_notacredito IS NOT NULL AND nc.cod_detallecompra IS NOT NULL)) AND ri.cantidad > 0 THEN ri.total ELSE 0 END), 0) as total_salidas
    FROM registroinventario ri
    JOIN tipomovimiento tm ON ri.cod_tipomovimiento = tm.cod_tipomovimiento
    LEFT JOIN notacredito nc ON ri.cod_notacredito = nc.cod_notacredito";
    
    $result_estadisticas = pg_query($conexion, $query_estadisticas);
    if($result_estadisticas && pg_num_rows($result_estadisticas) > 0) {
        $row_estadisticas = pg_fetch_assoc($result_estadisticas);
        $total_movimientos_global = $row_estadisticas['total_movimientos'];
        $total_entradas_global = $row_estadisticas['total_entradas'];
        $total_salidas_global = $row_estadisticas['total_salidas'];
        $stock_valorizado_global = $total_entradas_global - $total_salidas_global;
    }

    // Obtener estadísticas para cada producto (para usar en el filtro)
    $estadisticas_por_producto = [];
    $result_todos_productos = pg_query($conexion, "SELECT cod_producto, nombre FROM producto");
    if($result_todos_productos && pg_num_rows($result_todos_productos) > 0) {
        while($producto = pg_fetch_assoc($result_todos_productos)) {
            $estadisticas_por_producto[$producto['cod_producto']] = obtenerEstadisticasProducto($conexion, $producto['cod_producto']);
        }
    }
    ?>

    <div class="grid">
        <main class="principal">
            <button class="boton-menu" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>

            <div class="barra-lateral" id="barra-lateral">
                <div class="logo">
                    <h4><i class="fas fa-store"></i> MAD MARKET</h4>
                    <small id="userRole">Administrador</small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.html" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="kardexprincipal.html" class="nav-link active"><ul><i class="fas fa-boxes"></i>Kardex Principal</ul></a>
                    <a href="proveedores.php" class="nav-link"><ul><i class="fas fa-truck"></i>Proveedores</ul></a>
                    <a href="controlpersonal.html" class="nav-link"><ul><i class="fas fa-truck-loading"></i>Control de Personal</ul></a>
                    <a href="registroventas.html" class="nav-link"><ul><i class="fas fa-arrow-right"></i>Registro de Ventas</ul></a>
                </div>
            </div>
        </main>

        <div class="secundario">
            <div class="header">
                <div class="usuario-info">
                    <div class="usuario-avatar" id="usuarioAvatar"><?php echo htmlspecialchars($inicialNombre.$inicialApellido)?></div>
                    <div>
                        <div class="fw-bold fs-5" id="userName"><?php echo htmlspecialchars($usuarioadmin." ".$apellidoadmin) ?></div>
                        <small class="text-muted" id="userPosition">Administrador</small>
                    </div>
                    <div class="dropdown-container">
                        <div class="dropdown">
                            <button class="dropdown-btn" id="dropdownBtn">
                                <span class="arrow" id="arrow">▲</span>
                            </button>
                            <ul class="dropdown-list" id="dropdownList">
                                <a href="../login.php" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <br>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0"><i class="fas fa-chart-line me-2"></i>Kardex Principal <small class="text-muted">(Método PEPS)</small></h1>
                <div>
                    <button class="btn btn-outline-secondary" id="btnResetFilters">
                        <i class="fas fa-redo me-2"></i>Limpiar Filtros
                    </button>
                </div>
            </div>

            <!-- FILTROS SIMPLIFICADOS - SOLO PRODUCTO -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Producto</label>
                            <select class="form-select" id="productFilter">
                                <option value="">Todos los productos</option>
                                <?php
                                $result_todos_productos = pg_query($conexion, "SELECT cod_producto, nombre FROM producto");
                                if($result_todos_productos && pg_num_rows($result_todos_productos) > 0) {
                                    while($row = pg_fetch_assoc($result_todos_productos)){
                                        echo "<option value='{$row['cod_producto']}'>{$row['nombre']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ESTADÍSTICAS GLOBALES -->
            <div class="row mb-4 estadisticas-global" id="estadisticasGlobal">
                <div class="col-md-3">
                    <div class="card border-0 bg-primary text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1"><?php echo $total_movimientos_global; ?></h4>
                            <small>Total Movimientos</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-success text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1">S/ <?php echo number_format($total_entradas_global, 2); ?></h4>
                            <small>Valor Entradas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-danger text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1">S/ <?php echo number_format($total_salidas_global, 2); ?></h4>
                            <small>Valor Salidas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-info text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1">S/ <?php echo number_format($stock_valorizado_global, 2); ?></h4>
                            <small>Stock Valorizado</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ESTADÍSTICAS POR PRODUCTO (se muestran cuando se filtra por producto) -->
            <div class="row mb-4 estadisticas-producto" id="estadisticasProducto">
                <div class="col-md-3">
                    <div class="card border-0 bg-primary text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1" id="totalMovimientosProducto">0</h4>
                            <small>Total Movimientos</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-success text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1" id="valorEntradasProducto">S/ 0.00</h4>
                            <small>Valor Entradas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-danger text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1" id="valorSalidasProducto">S/ 0.00</h4>
                            <small>Valor Salidas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-info text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1" id="stockValorizadoProducto">S/ 0.00</h4>
                            <small>Stock Valorizado</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KARDEX POR PRODUCTO CON DOBLE PAGINACIÓN -->
            <?php if(empty($productos_kardex)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No se encontraron movimientos en el kardex.
                </div>
            <?php else: ?>
                <?php foreach($productos_kardex as $cod_producto => $producto): ?>
                    <?php if(!empty($producto['kardex'])): ?>
                        <div class="card border-0 shadow-sm mb-4 product-kardex active" data-producto="<?php echo $cod_producto; ?>">
                            <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 text-primary">
                                    <i class="fas fa-cube me-2"></i><?php echo $producto['nombre']; ?>
                                    <small class="text-muted">(<?php echo $cod_producto; ?>)</small>
                                </h5>
                                <div class="movimientos-info">
                                    <?php 
                                    $inicio_movimientos = (($producto['pagina_actual_movimientos'] - 1) * $movimientos_por_pagina) + 1;
                                    $fin_movimientos = min($inicio_movimientos + $movimientos_por_pagina - 1, $producto['total_movimientos']);
                                    ?>
                                    Mostrando <?php echo $inicio_movimientos; ?>-<?php echo $fin_movimientos; ?> de <?php echo $producto['total_movimientos']; ?> movimientos
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- BOTONES DE EXPORTACIÓN PARA CADA PRODUCTO -->
                                <div class="export-buttons d-flex justify-content-end mb-3">
                                    <button class="btn btn-success btn-sm" onclick="exportarExcel('<?php echo $cod_producto; ?>', '<?php echo $producto['nombre']; ?>', this)">
                                        <i class="fas fa-file-excel me-1"></i>Exportar Excel
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="exportarPDF('<?php echo $cod_producto; ?>', '<?php echo $producto['nombre']; ?>', this)">
                                        <i class="fas fa-file-pdf me-1"></i>Exportar PDF
                                    </button>
                                </div>
                                
                                <div class="table-responsive kardex-table">
                                    <table class="table table-bordered table-sm" id="table-<?php echo $cod_producto; ?>">
                                        <thead class="table-light">
                                            <tr>
                                                <th rowspan="2">FECHA</th>
                                                <th rowspan="2">USUARIO</th>
                                                <th colspan="3" class="text-center">ENTRADAS</th>
                                                <th colspan="3" class="text-center">SALIDAS</th>
                                                <th colspan="3" class="text-center">SALDO FINAL</th>
                                            </tr>
                                            <tr>
                                                <th>CANTIDAD</th>
                                                <th>COSTO UNIT.</th>
                                                <th>COSTO TOTAL</th>
                                                <th>CANTIDAD</th>
                                                <th>COSTO UNIT.</th>
                                                <th>COSTO TOTAL</th>
                                                <th>UNIDADES</th>
                                                <th>COSTO UNIT.</th>
                                                <th>COSTO TOTAL</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($producto['kardex'] as $mov): ?>
                                                <tr class="<?php echo $mov['clase_fila']; ?>">
                                                    <td><strong><?php echo $mov['fecha']; ?></strong></td>
                                                    <td><small><?php echo $mov['usuario']; ?></small></td>
                                                    
                                                    <!-- ENTRADAS -->
                                                    <td>
                                                        <?php if($mov['entradas_cantidad'] > 0): ?>
                                                            <?php if(isset($mov['html_entradas_cantidad']) && is_string($mov['html_entradas_cantidad'])): ?>
                                                                <?php echo $mov['html_entradas_cantidad']; ?>
                                                            <?php else: ?>
                                                                <strong class="text-success"><?php echo intval($mov['entradas_cantidad']); ?></strong>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($mov['entradas_cantidad'] > 0): ?>
                                                            <?php if(isset($mov['html_entradas_costo_unitario']) && is_string($mov['html_entradas_costo_unitario'])): ?>
                                                                <?php echo $mov['html_entradas_costo_unitario']; ?>
                                                            <?php else: ?>
                                                                S/ <?php echo number_format($mov['entradas_costo_unitario'], 2); ?>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($mov['entradas_cantidad'] > 0): ?>
                                                            <strong>S/ <?php echo number_format($mov['entradas_costo_total'], 2); ?></strong>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <!-- SALIDAS -->
                                                    <td>
                                                        <?php if($mov['salidas_cantidad'] > 0): ?>
                                                            <?php if(isset($mov['html_salidas_cantidad']) && is_string($mov['html_salidas_cantidad'])): ?>
                                                                <?php echo $mov['html_salidas_cantidad']; ?>
                                                            <?php else: ?>
                                                                <strong class="text-danger"><?php echo intval($mov['salidas_cantidad']); ?></strong>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($mov['salidas_cantidad'] > 0 && !$mov['error_stock']): ?>
                                                            <?php if(isset($mov['html_salidas_costo_unitario']) && is_string($mov['html_salidas_costo_unitario'])): ?>
                                                                <?php echo $mov['html_salidas_costo_unitario']; ?>
                                                            <?php else: ?>
                                                                S/ <?php echo number_format($mov['salidas_costo_unitario'], 2); ?>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($mov['salidas_cantidad'] > 0 && !$mov['error_stock']): ?>
                                                            <strong>S/ <?php echo number_format($mov['salidas_costo_total'], 2); ?></strong>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <!-- SALDO FINAL -->
                                                    <td>
                                                        <?php echo $mov['saldo_unidades_html']; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $mov['saldo_costos_html']; ?>
                                                    </td>
                                                    <td>
                                                        <strong class="text-primary">S/ <?php echo number_format($mov['saldo_costo_total'], 2); ?></strong>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- PAGINACIÓN DE MOVIMIENTOS POR PRODUCTO -->
                                <?php if($producto['total_paginas_movimientos'] > 1): ?>
                                    <div class="kardex-pagination">
                                        <?php if($producto['pagina_actual_movimientos'] > 1): ?>
                                            <a href="?pagina_productos=<?php echo $pagina_actual_productos; ?>&movimientos_<?php echo $cod_producto; ?>=<?php echo $producto['pagina_actual_movimientos'] - 1; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-chevron-left"></i> Anterior
                                            </a>
                                        <?php endif; ?>
                                        
                                        <span class="mx-2">
                                            Página <?php echo $producto['pagina_actual_movimientos']; ?> de <?php echo $producto['total_paginas_movimientos']; ?>
                                        </span>
                                        
                                        <?php if($producto['pagina_actual_movimientos'] < $producto['total_paginas_movimientos']): ?>
                                            <a href="?pagina_productos=<?php echo $pagina_actual_productos; ?>&movimientos_<?php echo $cod_producto; ?>=<?php echo $producto['pagina_actual_movimientos'] + 1; ?>" class="btn btn-sm btn-outline-primary">
                                                Siguiente <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- PAGINACIÓN DE PRODUCTOS -->
            <?php if($total_paginas_productos > 1): ?>
                <div class="pagination-container">
                    <nav>
                        <ul class="pagination">
                            <?php if($pagina_actual_productos > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina_productos=<?php echo $pagina_actual_productos - 1; ?>">
                                        <i class="fas fa-chevron-left"></i> Anterior
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for($i = 1; $i <= $total_paginas_productos; $i++): ?>
                                <li class="page-item <?php echo $i == $pagina_actual_productos ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina_productos=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if($pagina_actual_productos < $total_paginas_productos): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina_productos=<?php echo $pagina_actual_productos + 1; ?>">
                                        Siguiente <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
const dropdownBtn = document.getElementById("dropdownBtn");
const dropdownList = document.getElementById("dropdownList");
const arrow = document.getElementById("arrow");

dropdownBtn.addEventListener("click", () => {
    const isVisible = dropdownList.style.display === "block";
    dropdownList.style.display = isVisible ? "none" : "block";
    arrow.style.transform = isVisible ? "rotate(0deg)" : "rotate(180deg)";
});
                            
document.addEventListener("click", (e) => {
    if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
        dropdownList.style.display = "none";
        arrow.style.transform = "rotate(0deg)";
    }
});

// Datos de estadísticas por producto (se pasan desde PHP)
const estadisticasProductos = <?php echo json_encode($estadisticas_por_producto); ?>;

// Función auxiliar para extraer texto de celdas con formato
function extraerTextoFormateado(cell) {
    const divs = cell.querySelectorAll('.lote-separado, .lote-item');
    if (divs.length > 0) {
        return Array.from(divs).map(div => div.textContent.trim()).join('\n');
    }
    return cell.textContent.trim();
}

// Filtros para kardex
document.addEventListener('DOMContentLoaded', function() {
    const productFilter = document.getElementById('productFilter');
    const kardexCards = document.querySelectorAll('.product-kardex');
    const estadisticasGlobal = document.getElementById('estadisticasGlobal');
    const estadisticasProducto = document.getElementById('estadisticasProducto');

    function actualizarEstadisticasProducto(codProducto) {
        if (codProducto && estadisticasProductos[codProducto]) {
            const stats = estadisticasProductos[codProducto];
            
            document.getElementById('totalMovimientosProducto').textContent = stats.total_movimientos;
            document.getElementById('valorEntradasProducto').textContent = 'S/ ' + parseFloat(stats.total_entradas).toFixed(2);
            document.getElementById('valorSalidasProducto').textContent = 'S/ ' + parseFloat(stats.total_salidas).toFixed(2);
            
            const stockValorizado = parseFloat(stats.total_entradas) - parseFloat(stats.total_salidas);
            document.getElementById('stockValorizadoProducto').textContent = 'S/ ' + stockValorizado.toFixed(2);
            
            // Mostrar estadísticas del producto y ocultar las globales
            estadisticasGlobal.style.display = 'none';
            estadisticasProducto.style.display = 'flex';
        } else {
            // Mostrar estadísticas globales y ocultar las del producto
            estadisticasGlobal.style.display = 'flex';
            estadisticasProducto.style.display = 'none';
        }
    }

    function aplicarFiltros() {
        const productoVal = productFilter.value;

        kardexCards.forEach(card => {
            const productoData = card.getAttribute('data-producto');
            
            if (productoVal && productoData !== productoVal) {
                card.style.display = 'none';
            } else {
                card.style.display = 'block';
            }
        });

        // Actualizar estadísticas según el filtro
        actualizarEstadisticasProducto(productoVal);
    }

    productFilter.addEventListener('change', aplicarFiltros);
    
    // Limpiar filtros
    document.getElementById('btnResetFilters').addEventListener('click', function() {
        productFilter.value = '';
        kardexCards.forEach(card => {
            card.style.display = 'block';
        });
        // Mostrar estadísticas globales al limpiar filtros
        estadisticasGlobal.style.display = 'flex';
        estadisticasProducto.style.display = 'none';
    });

    // Inicializar mostrando estadísticas globales
    estadisticasGlobal.style.display = 'flex';
    estadisticasProducto.style.display = 'none';
});

// FUNCIONES DE EXPORTACIÓN MEJORADAS - AHORA OBTIENEN TODOS LOS DATOS
function exportarExcel(codProducto, nombreProducto, btnElement) {
    // Mostrar mensaje de carga
    const originalText = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
    btnElement.disabled = true;
    
    // Hacer una petición AJAX para obtener todos los datos del kardex
    fetch(`obtener_kardex_completo.php?cod_producto=${codProducto}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(kardexCompleto => {
            // Crear el workbook de Excel
            const workbook = XLSX.utils.book_new();
            
            // Crear datos manualmente para formatear correctamente
            const data = [];
            
            // Encabezados
            const headers = [
                'FECHA', 'USUARIO', 
                'ENTRADAS CANTIDAD', 'ENTRADAS COSTO UNIT.', 'ENTRADAS COSTO TOTAL',
                'SALIDAS CANTIDAD', 'SALIDAS COSTO UNIT.', 'SALIDAS COSTO TOTAL',
                'SALDO UNIDADES', 'SALDO COSTO UNIT.', 'SALDO COSTO TOTAL'
            ];
            data.push(headers);
            
            // Agregar filas de datos
            kardexCompleto.forEach(mov => {
                const rowData = [
                    mov.fecha,
                    mov.usuario,
                    mov.entradas_cantidad > 0 ? mov.entradas_cantidad : '',
                    mov.entradas_cantidad > 0 ? `S/ ${parseFloat(mov.entradas_costo_unitario).toFixed(2)}` : '',
                    mov.entradas_cantidad > 0 ? `S/ ${parseFloat(mov.entradas_costo_total).toFixed(2)}` : '',
                    mov.salidas_cantidad > 0 ? mov.salidas_cantidad : '',
                    mov.salidas_cantidad > 0 && !mov.error_stock ? `S/ ${parseFloat(mov.salidas_costo_unitario).toFixed(2)}` : '',
                    mov.salidas_cantidad > 0 && !mov.error_stock ? `S/ ${parseFloat(mov.salidas_costo_total).toFixed(2)}` : '',
                    // Para saldo, extraemos el texto de las unidades y costos
                    mov.saldo_unidades_texto || '',
                    mov.saldo_costos_texto || '',
                    `S/ ${parseFloat(mov.saldo_costo_total).toFixed(2)}`
                ];
                data.push(rowData);
            });
            
            // Crear worksheet con datos formateados
            const worksheet = XLSX.utils.aoa_to_sheet(data);
            
            // Ajustar el ancho de las columnas
            worksheet['!cols'] = [
                { wch: 12 }, // FECHA
                { wch: 15 }, // USUARIO
                { wch: 15 }, // ENTRADAS CANTIDAD
                { wch: 15 }, // ENTRADAS COSTO UNIT.
                { wch: 15 }, // ENTRADAS COSTO TOTAL
                { wch: 15 }, // SALIDAS CANTIDAD
                { wch: 15 }, // SALIDAS COSTO UNIT.
                { wch: 15 }, // SALIDAS COSTO TOTAL
                { wch: 20 }, // SALDO UNIDADES
                { wch: 20 }, // SALDO COSTO UNIT.
                { wch: 15 }  // SALDO COSTO TOTAL
            ];
            
            // Agregar worksheet al workbook
            XLSX.utils.book_append_sheet(workbook, worksheet, 'Kardex');
            
            // Descargar archivo
            XLSX.writeFile(workbook, `Kardex_${nombreProducto}_${codProducto}_COMPLETO.xlsx`);
            
            // Restaurar botón
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        })
        .catch(error => {
            console.error('Error al exportar Excel:', error);
            alert('Error al exportar el archivo Excel. Por favor, intente nuevamente.');
            
            // Restaurar botón en caso de error
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        });
}

function exportarPDF(codProducto, nombreProducto, btnElement) {
    // Mostrar mensaje de carga
    const originalText = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
    btnElement.disabled = true;
    
    // Hacer una petición AJAX para obtener todos los datos del kardex
    fetch(`obtener_kardex_completo.php?cod_producto=${codProducto}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(kardexCompleto => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4');
            
            // Título del documento
            doc.setFontSize(16);
            doc.text(`Kardex Completo - ${nombreProducto} (${codProducto})`, 14, 15);
            doc.setFontSize(10);
            doc.text(`Fecha de exportación: ${new Date().toLocaleDateString()}`, 14, 22);
            doc.text(`Total de movimientos: ${kardexCompleto.length}`, 14, 28);
            
            // Preparar datos para la tabla PDF
            const headers = [
                ['FECHA', 'USUARIO', 'ENTRADAS', 'ENTRADAS', 'ENTRADAS', 'SALIDAS', 'SALIDAS', 'SALIDAS', 'SALDO', 'SALDO', 'SALDO'],
                ['', '', '', 'CANTIDAD', 'COSTO UNIT.', 'COSTO TOTAL', 'CANTIDAD', 'COSTO UNIT.', 'COSTO TOTAL', 'UNIDADES', 'COSTO UNIT.', 'COSTO TOTAL']
            ];
            
            const body = kardexCompleto.map(mov => [
                mov.fecha,
                mov.usuario,
                mov.entradas_cantidad > 0 ? mov.entradas_cantidad.toString() : '',
                mov.entradas_cantidad > 0 ? `S/ ${parseFloat(mov.entradas_costo_unitario).toFixed(2)}` : '',
                mov.entradas_cantidad > 0 ? `S/ ${parseFloat(mov.entradas_costo_total).toFixed(2)}` : '',
                mov.salidas_cantidad > 0 ? mov.salidas_cantidad.toString() : '',
                mov.salidas_cantidad > 0 && !mov.error_stock ? `S/ ${parseFloat(mov.salidas_costo_unitario).toFixed(2)}` : '',
                mov.salidas_cantidad > 0 && !mov.error_stock ? `S/ ${parseFloat(mov.salidas_costo_total).toFixed(2)}` : '',
                mov.saldo_unidades_texto || '',
                mov.saldo_costos_texto || '',
                `S/ ${parseFloat(mov.saldo_costo_total).toFixed(2)}`
            ]);
            
            // Crear tabla PDF
            doc.autoTable({
                head: headers,
                body: body,
                startY: 35,
                styles: { 
                    fontSize: 6, 
                    cellPadding: 1,
                    lineColor: [0, 0, 0],
                    lineWidth: 0.1
                },
                headStyles: { 
                    fillColor: [52, 58, 64],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    fontSize: 6
                },
                alternateRowStyles: { 
                    fillColor: [240, 240, 240]
                },
                margin: { top: 35 },
                tableWidth: 'wrap'
            });
            
            // Descargar PDF
            doc.save(`Kardex_${nombreProducto}_${codProducto}_COMPLETO.pdf`);
            
            // Restaurar botón
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        })
        .catch(error => {
            console.error('Error al exportar PDF:', error);
            alert('Error al exportar el archivo PDF. Por favor, intente nuevamente.');
            
            // Restaurar botón en caso de error
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        });
}
</script>
</body>
</html>