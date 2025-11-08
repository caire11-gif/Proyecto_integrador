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
    <style>
        .bg-success-light { background-color: #d4edda !important; }
        .bg-danger-light { background-color: #f8d7da !important; }
        .bg-primary-light { background-color: #cce7ff !important; }
        .table th { vertical-align: middle; }
        .kardex-table { font-size: 0.8rem; }
        .kardex-table th, .kardex-table td { padding: 0.3rem; text-align: center; }
        .entrada-row { background-color: #f8fff8; }
        .salida-row { background-color: #fff8f8; }
        .saldo-unitario { font-weight: bold; color: #007bff; }
        .error-stock { background-color: #ffe6e6; color: #d63031; }
        .lote-separado { border-bottom: 1px dashed #ccc; padding: 2px 0; display: flex; justify-content: space-between; }
        .lote-total { border-top: 1px solid #333; font-weight: bold; padding: 2px 0; display: flex; justify-content: space-between; }
        .lote-cantidad { text-align: left; }
        .lote-costo { text-align: right; }
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

    // Obtener tipos de movimiento
    $result2 = pg_query($conexion, "SELECT cod_tipomovimiento, nombre FROM tipomovimiento");
    if(!$result2) echo "Error al seleccionar el tipo de movimiento.";

    // FUNCIÓN MEJORADA - AGRUPAR LOTES CON EL MISMO COSTO
    function procesarProductoKardex($conexion, $cod_producto) {
        // Obtener movimientos del producto ordenados por fecha e ID
        $query = "SELECT 
                    ri.fecha_inventario AS fecha,
                    tm.nombre AS tipomovimiento_nombre,
                    tm.cod_tipomovimiento,
                    ri.cantidad AS cantidad,
                    ri.precio_unitario AS precio_unitario,
                    ri.total AS total_movimiento,
                    u.usuario AS usuario_nombre,
                    ri.cod_inventario
                FROM registroinventario ri
                JOIN tipomovimiento tm ON ri.cod_tipomovimiento = tm.cod_tipomovimiento
                JOIN usuario u ON ri.cod_usuario = u.cod_usuario
                WHERE ri.cod_producto = '$cod_producto'
                ORDER BY ri.fecha_inventario ASC, ri.cod_inventario ASC";
        
        $result = pg_query($conexion, $query);
        $movimientos = [];
        
        if($result && pg_num_rows($result) > 0) {
            while($row = pg_fetch_assoc($result)) {
                $movimientos[] = $row;
            }
        }
        
        // INICIALIZAR KARDEX
        $kardex = [];
        $lotes = []; // Array de lotes separados
        $lote_id_counter = 1;
        $saldo_cantidad = 0;
        $saldo_costo_total = 0;
        
        // PROCESAR MOVIMIENTOS EN ORDEN CRONOLÓGICO
        foreach($movimientos as $mov) {
            $es_entrada = stripos($mov['tipomovimiento_nombre'], 'entrada') !== false;
            $es_salida = stripos($mov['tipomovimiento_nombre'], 'salida') !== false;
            
            if($es_entrada) {
                // VERIFICAR SI YA EXISTE UN LOTE CON EL MISMO PRECIO
                $lote_existente_index = null;
                foreach($lotes as $index => $lote) {
                    if($lote['costo_unitario'] == $mov['precio_unitario']) {
                        $lote_existente_index = $index;
                        break;
                    }
                }
                
                if($lote_existente_index !== null) {
                    // AUMENTAR LOTE EXISTENTE CON EL MISMO PRECIO
                    $lotes[$lote_existente_index]['cantidad'] += $mov['cantidad'];
                    $detalle_lote = "Mismo lote: +{$mov['cantidad']} und a S/ " . number_format($mov['precio_unitario'], 2);
                } else {
                    // CREAR NUEVO LOTE CON NUEVO PRECIO
                    $nuevo_lote = [
                        'id' => $lote_id_counter++,
                        'cantidad' => $mov['cantidad'], 
                        'costo_unitario' => $mov['precio_unitario'],
                        'fecha_entrada' => $mov['fecha'],
                        'cod_inventario' => $mov['cod_inventario']
                    ];
                    
                    $lotes[] = $nuevo_lote;
                    $detalle_lote = "Nuevo lote: {$mov['cantidad']} und a S/ " . number_format($mov['precio_unitario'], 2);
                }
                
                // Calcular nuevo saldo (ACUMULADO)
                $saldo_cantidad += $mov['cantidad'];
                $saldo_costo_total += $mov['total_movimiento'];
                
                // Preparar la visualización de lotes para el saldo - AGRUPANDO POR PRECIO
                $saldo_unidades_html = "";
                $saldo_costos_html = "";
                
                // Agrupar lotes por costo unitario
                $lotes_agrupados = [];
                foreach($lotes as $lote) {
                    if($lote['cantidad'] > 0) {
                        $costo_key = (string)$lote['costo_unitario'];
                        if(!isset($lotes_agrupados[$costo_key])) {
                            $lotes_agrupados[$costo_key] = [
                                'cantidad_total' => 0,
                                'costo_unitario' => $lote['costo_unitario']
                            ];
                        }
                        $lotes_agrupados[$costo_key]['cantidad_total'] += $lote['cantidad'];
                    }
                }
                
                // Mostrar lotes agrupados
                foreach($lotes_agrupados as $lote_agrupado) {
                    $saldo_unidades_html .= "<div class='lote-separado'><span class='lote-cantidad'>{$lote_agrupado['cantidad_total']} und</span></div>";
                    $saldo_costos_html .= "<div class='lote-separado'><span class='lote-costo'>S/ " . number_format($lote_agrupado['costo_unitario'], 2) . "</span></div>";
                }
                
                $kardex[] = [
                    'fecha' => $mov['fecha'],
                    'tipo' => 'ENTRADA',
                    'usuario' => $mov['usuario_nombre'],
                    'entradas_cantidad' => $mov['cantidad'],
                    'entradas_costo_unitario' => $mov['precio_unitario'],
                    'entradas_costo_total' => $mov['total_movimiento'],
                    'salidas_cantidad' => 0,
                    'salidas_costo_unitario' => 0,
                    'salidas_costo_total' => 0,
                    'saldo_cantidad' => $saldo_cantidad,
                    'saldo_costo_unitario' => $mov['precio_unitario'], // Último costo
                    'saldo_costo_total' => $saldo_costo_total,
                    'saldo_unidades_html' => $saldo_unidades_html,
                    'saldo_costos_html' => $saldo_costos_html,
                    'detalle_lotes' => $detalle_lote,
                    'lotes_actuales' => count($lotes)
                ];
                
            } else if($es_salida) {
                // APLICAR MÉTODO PEPS - consumir de los lotes más antiguos primero
                $cantidad_restante = $mov['cantidad'];
                $costo_total_salida = 0;
                $detalle_salida = [];
                
                // Ordenar lotes por fecha de entrada (más antiguos primero)
                usort($lotes, function($a, $b) {
                    $dateCompare = strtotime($a['fecha_entrada']) - strtotime($b['fecha_entrada']);
                    if ($dateCompare == 0) {
                        return $a['id'] - $b['id'];
                    }
                    return $dateCompare;
                });
                
                // Consumir de los lotes más antiguos
                foreach($lotes as $index => &$lote) {
                    if($cantidad_restante <= 0) break;
                    
                    if($lote['cantidad'] > 0) {
                        $cantidad_usar = min($lote['cantidad'], $cantidad_restante);
                        $costo_lote = $cantidad_usar * $lote['costo_unitario'];
                        
                        $costo_total_salida += $costo_lote;
                        $lote['cantidad'] -= $cantidad_usar;
                        $cantidad_restante -= $cantidad_usar;
                        
                        $detalle_salida[] = "Lote #{$lote['id']}: {$cantidad_usar} und a S/ " . number_format($lote['costo_unitario'], 2);
                    }
                }
                
                // Eliminar lotes vacíos
                $lotes = array_filter($lotes, function($lote) {
                    return $lote['cantidad'] > 0;
                });
                $lotes = array_values($lotes);
                
                // Verificar si hay suficiente stock
                $error_stock = false;
                if($cantidad_restante > 0) {
                    $error_stock = true;
                    $detalle_salida = ["ERROR: Stock insuficiente - Faltan {$cantidad_restante} unidades"];
                    $costo_total_salida = 0;
                } else {
                    // Calcular nuevo saldo después de la salida
                    $saldo_cantidad -= $mov['cantidad'];
                    $saldo_costo_total -= $costo_total_salida;
                }
                
                // Preparar la visualización de lotes para el saldo después de la salida - AGRUPANDO POR PRECIO
                $saldo_unidades_html = "";
                $saldo_costos_html = "";
                $ultimo_costo = 0;
                
                // Agrupar lotes por costo unitario
                $lotes_agrupados = [];
                foreach($lotes as $lote) {
                    if($lote['cantidad'] > 0) {
                        $costo_key = (string)$lote['costo_unitario'];
                        if(!isset($lotes_agrupados[$costo_key])) {
                            $lotes_agrupados[$costo_key] = [
                                'cantidad_total' => 0,
                                'costo_unitario' => $lote['costo_unitario']
                            ];
                        }
                        $lotes_agrupados[$costo_key]['cantidad_total'] += $lote['cantidad'];
                        $ultimo_costo = $lote['costo_unitario']; // Tomar el último costo
                    }
                }
                
                // Mostrar lotes agrupados
                foreach($lotes_agrupados as $lote_agrupado) {
                    $saldo_unidades_html .= "<div class='lote-separado'><span class='lote-cantidad'>{$lote_agrupado['cantidad_total']} und</span></div>";
                    $saldo_costos_html .= "<div class='lote-separado'><span class='lote-costo'>S/ " . number_format($lote_agrupado['costo_unitario'], 2) . "</span></div>";
                }
                
                if(empty($lotes_agrupados)) {
                    $saldo_unidades_html = "<div>0 und</div>";
                    $saldo_costos_html = "<div>S/ 0.00</div>";
                }
                
                $salidas_costo_unitario = $mov['cantidad'] > 0 ? $costo_total_salida / $mov['cantidad'] : 0;
                
                $kardex[] = [
                    'fecha' => $mov['fecha'],
                    'tipo' => 'SALIDA',
                    'usuario' => $mov['usuario_nombre'],
                    'entradas_cantidad' => 0,
                    'entradas_costo_unitario' => 0,
                    'entradas_costo_total' => 0,
                    'salidas_cantidad' => $mov['cantidad'],
                    'salidas_costo_unitario' => $error_stock ? 0 : $salidas_costo_unitario,
                    'salidas_costo_total' => $error_stock ? 0 : $costo_total_salida,
                    'saldo_cantidad' => $error_stock ? $kardex[count($kardex)-1]['saldo_cantidad'] : $saldo_cantidad,
                    'saldo_costo_unitario' => $error_stock ? $kardex[count($kardex)-1]['saldo_costo_unitario'] : $ultimo_costo,
                    'saldo_costo_total' => $error_stock ? $kardex[count($kardex)-1]['saldo_costo_total'] : $saldo_costo_total,
                    'saldo_unidades_html' => $error_stock ? $kardex[count($kardex)-1]['saldo_unidades_html'] : $saldo_unidades_html,
                    'saldo_costos_html' => $error_stock ? $kardex[count($kardex)-1]['saldo_costos_html'] : $saldo_costos_html,
                    'detalle_lotes' => implode(' + ', $detalle_salida),
                    'error_stock' => $error_stock,
                    'lotes_actuales' => count($lotes)
                ];
            }
        }
        
        return $kardex;
    }

    // Obtener datos del kardex para todos los productos
    $productos_kardex = [];
    
    $result_productos = pg_query($conexion, "SELECT cod_producto, nombre FROM producto");
    if($result_productos && pg_num_rows($result_productos) > 0) {
        while($producto = pg_fetch_assoc($result_productos)) {
            $kardex_data = procesarProductoKardex($conexion, $producto['cod_producto']);
            if(!empty($kardex_data)) {
                $productos_kardex[$producto['cod_producto']] = [
                    'nombre' => $producto['nombre'],
                    'kardex' => $kardex_data
                ];
            }
        }
    }

    // Calcular estadísticas generales
    $total_movimientos = 0;
    $total_entradas = 0;
    $total_salidas = 0;
    $stock_valorizado = 0;
    
    foreach($productos_kardex as $producto) {
        $total_movimientos += count($producto['kardex']);
        
        if(!empty($producto['kardex'])) {
            $ultimo_saldo = end($producto['kardex']);
            $stock_valorizado += $ultimo_saldo['saldo_costo_total'];
            
            foreach($producto['kardex'] as $mov) {
                if($mov['tipo'] == 'ENTRADA') {
                    $total_entradas += $mov['entradas_costo_total'];
                } else if($mov['tipo'] == 'SALIDA' && !$mov['error_stock']) {
                    $total_salidas += $mov['salidas_costo_total'];
                }
            }
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
                    <a href="dashboard.php" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="kardexprincipal.php" class="nav-link active"><ul><i class="fas fa-boxes"></i>Kardex Principal</ul></a>
                    <a href="proveedores.php" class="nav-link"><ul><i class="fas fa-truck"></i>Proveedores</ul></a>
                    <a href="controlpersonal.php" class="nav-link"><ul><i class="fas fa-truck-loading"></i>Control de Personal</ul></a>
                    <a href="registroventas.php" class="nav-link"><ul><i class="fas fa-arrow-right"></i>Registro de Ventas</ul></a>
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
                    <button class="btn btn-mad me-2">
                        <i class="fas fa-download me-2"></i>Exportar
                    </button>
                    <button class="btn btn-outline-secondary" id="btnResetFilters">
                        <i class="fas fa-redo me-2"></i>Limpiar Filtros
                    </button>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Producto</label>
                            <select class="form-select" id="productFilter">
                                <option value="">Todos los productos</option>
                                <?php
                                pg_result_seek($result1, 0);
                                while($row1 = pg_fetch_assoc($result1)){
                                    echo "<option value='{$row1['cod_producto']}'>{$row1['nombre']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Desde</label>
                            <input type="date" class="form-control" id="dateFrom">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Hasta</label>
                            <input type="date" class="form-control" id="dateTo">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo Movimiento</label>
                            <select class="form-select" id="movementType">
                                <option value="">Todos</option>
                                <?php
                                pg_result_seek($result2, 0);
                                while($row2 = pg_fetch_assoc($result2)){
                                    echo "<option value='{$row2['cod_tipomovimiento']}'>{$row2['nombre']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ESTADÍSTICAS -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-0 bg-primary text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1"><?php echo $total_movimientos; ?></h4>
                            <small>Total Movimientos</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-success text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1">S/ <?php echo number_format($total_entradas, 2); ?></h4>
                            <small>Valor Entradas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-danger text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1">S/ <?php echo number_format($total_salidas, 2); ?></h4>
                            <small>Valor Salidas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-info text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1">S/ <?php echo number_format($stock_valorizado, 2); ?></h4>
                            <small>Stock Valorizado</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KARDEX POR PRODUCTO -->
            <?php foreach($productos_kardex as $cod_producto => $producto): ?>
                <?php if(!empty($producto['kardex'])): ?>
                    <div class="card border-0 shadow-sm mb-4 product-kardex" data-producto="<?php echo $cod_producto; ?>">
                        <div class="card-header bg-light border-0">
                            <h5 class="mb-0 text-primary">
                                <i class="fas fa-cube me-2"></i><?php echo $producto['nombre']; ?>
                                <small class="text-muted">(<?php echo $cod_producto; ?>)</small>
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- TABLA KARDEX - MOVIMIENTOS EN ORDEN DE REGISTRO -->
                            <div class="table-responsive kardex-table">
                                <table class="table table-bordered table-sm">
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
                                            <tr class="<?php echo $mov['tipo'] == 'ENTRADA' ? 'entrada-row' : ($mov['error_stock'] ? 'error-stock' : 'salida-row'); ?>">
                                                <td><strong><?php echo $mov['fecha']; ?></strong></td>
                                                <td><small><?php echo $mov['usuario']; ?></small></td>
                                                
                                                <!-- ENTRADAS -->
                                                <td>
                                                    <?php if($mov['entradas_cantidad'] > 0): ?>
                                                        <strong class="text-success"><?php echo $mov['entradas_cantidad']; ?></strong>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($mov['entradas_cantidad'] > 0): ?>
                                                        S/ <?php echo number_format($mov['entradas_costo_unitario'], 2); ?>
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
                                                        <strong class="text-danger"><?php echo $mov['salidas_cantidad']; ?></strong>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($mov['salidas_cantidad'] > 0 && !$mov['error_stock']): ?>
                                                        S/ <?php echo number_format($mov['salidas_costo_unitario'], 2); ?>
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
                                                
                                                <!-- SALDO FINAL - MOSTRANDO LOTES AGRUPADOS POR PRECIO -->
                                                <td>
                                                    <?php if(isset($mov['saldo_unidades_html'])): ?>
                                                        <?php echo $mov['saldo_unidades_html']; ?>
                                                    <?php else: ?>
                                                        <strong class="text-primary"><?php echo $mov['saldo_cantidad']; ?></strong>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if(isset($mov['saldo_costos_html'])): ?>
                                                        <?php echo $mov['saldo_costos_html']; ?>
                                                    <?php else: ?>
                                                        <strong class="saldo-unitario">S/ <?php echo number_format($mov['saldo_costo_unitario'], 2); ?></strong>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong class="text-primary">S/ <?php echo number_format($mov['saldo_costo_total'], 2); ?></strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    // ... (código JavaScript para filtros) ...
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

    // Filtros para kardex
    document.addEventListener('DOMContentLoaded', function() {
        const productFilter = document.getElementById('productFilter');
        const kardexCards = document.querySelectorAll('.product-kardex');

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
        }

        productFilter.addEventListener('change', aplicarFiltros);
    });
    </script>
</body>
</html>