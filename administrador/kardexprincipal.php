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
        .lote-separado { padding: 2px 0; }
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

    // FUNCIÓN CORREGIDA - ORDEN POR cod_inventario
    function procesarProductoKardex($conexion, $cod_producto) {
        // Obtener movimientos del producto con ORDEN POR cod_inventario
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
                ORDER BY ri.cod_inventario ASC";
        
        $result = pg_query($conexion, $query);
        $movimientos = [];
        
        if($result && pg_num_rows($result) > 0) {
            while($row = pg_fetch_assoc($result)) {
                $movimientos[] = $row;
            }
        }
        
        // INICIALIZAR
        $kardex = [];
        $lotes = []; // [precio => cantidad]
        $saldo_total = 0;
        
        foreach($movimientos as $mov) {
            $es_entrada = stripos($mov['tipomovimiento_nombre'], 'entrada') !== false;
            $es_salida = stripos($mov['tipomovimiento_nombre'], 'salida') !== false;
            
            if($es_entrada) {
                // ENTRADA: Agregar al lote del mismo precio
                $precio = $mov['precio_unitario'];
                if(!isset($lotes[$precio])) {
                    $lotes[$precio] = 0;
                }
                $lotes[$precio] += $mov['cantidad'];
                $saldo_total += $mov['total_movimiento'];
                
                // Mostrar lotes actuales
                $html_unidades = "";
                $html_costos = "";
                foreach($lotes as $p => $c) {
                    if($c > 0) {
                        $html_unidades .= "<div class='lote-separado'>" . intval($c) . " und</div>";
                        $html_costos .= "<div class='lote-separado'>S/ " . number_format($p, 2) . "</div>";
                    }
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
                    'saldo_costo_total' => $saldo_total,
                    'saldo_unidades_html' => $html_unidades,
                    'saldo_costos_html' => $html_costos
                ];
                
            } else if($es_salida) {
                // SALIDA: PEPS simple
                $cantidad_salida = $mov['cantidad'];
                $costo_salida_total = 0;
                $restante = $cantidad_salida;
                
                // Ordenar por precio (más barato primero)
                ksort($lotes);
                
                // Consumir de lotes más baratos
                foreach($lotes as $precio => &$cantidad) {
                    if($restante <= 0) break;
                    
                    if($cantidad > 0) {
                        $usar = min($cantidad, $restante);
                        $costo_salida_total += $usar * $precio;
                        $cantidad -= $usar;
                        $restante -= $usar;
                    }
                }
                
                // Verificar si alcanza
                $error = ($restante > 0);
                if(!$error) {
                    $saldo_total -= $costo_salida_total;
                }
                
                // Calcular costo promedio de salida
                $costo_promedio = 0;
                if($cantidad_salida > 0 && !$error) {
                    $costo_promedio = $costo_salida_total / $cantidad_salida;
                }
                
                // Mostrar lotes que quedan después de la salida
                $html_unidades = "";
                $html_costos = "";
                foreach($lotes as $p => $c) {
                    if($c > 0) {
                        $html_unidades .= "<div class='lote-separado'>" . intval($c) . " und</div>";
                        $html_costos .= "<div class='lote-separado'>S/ " . number_format($p, 2) . "</div>";
                    }
                }
                
                if(empty($lotes)) {
                    $html_unidades = "<div>0 und</div>";
                    $html_costos = "<div>S/ 0.00</div>";
                }
                
                // CORRECCIÓN: Manejar caso cuando es el primer movimiento
                $movimiento_anterior = null;
                if(count($kardex) > 0) {
                    $movimiento_anterior = $kardex[count($kardex)-1];
                }
                
                $kardex[] = [
                    'fecha' => $mov['fecha'],
                    'tipo' => 'SALIDA',
                    'usuario' => $mov['usuario_nombre'],
                    'entradas_cantidad' => 0,
                    'entradas_costo_unitario' => 0,
                    'entradas_costo_total' => 0,
                    'salidas_cantidad' => $cantidad_salida,
                    'salidas_costo_unitario' => $error ? 0 : $costo_promedio,
                    'salidas_costo_total' => $error ? 0 : $costo_salida_total,
                    'saldo_costo_total' => $error ? ($movimiento_anterior ? $movimiento_anterior['saldo_costo_total'] : 0) : $saldo_total,
                    'saldo_unidades_html' => $error ? ($movimiento_anterior ? $movimiento_anterior['saldo_unidades_html'] : "<div>0 und</div>") : $html_unidades,
                    'saldo_costos_html' => $error ? ($movimiento_anterior ? $movimiento_anterior['saldo_costos_html'] : "<div>S/ 0.00</div>") : $html_costos,
                    'error_stock' => $error
                ];
            }
        }
        
        return $kardex;
    }

    // Obtener datos del kardex
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

    // Calcular estadísticas
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
                                                        <strong class="text-success"><?php echo intval($mov['entradas_cantidad']); ?></strong>
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
                                                        <strong class="text-danger"><?php echo intval($mov['salidas_cantidad']); ?></strong>
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
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
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