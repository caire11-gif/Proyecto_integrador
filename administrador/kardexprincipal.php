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
        .bg-success-light {
            background-color: #d4edda !important;
        }
        .bg-danger-light {
            background-color: #f8d7da !important;
        }
        .bg-primary-light {
            background-color: #cce7ff !important;
        }
        .table th {
            vertical-align: middle;
        }
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

    // CORRECCIÓN: Obtener productos con código y nombre
    $result1 = pg_query($conexion, "SELECT cod_producto, nombre FROM producto");
    if(!$result1){
        echo "Error al seleccionar los productos.";
    }

    // CORRECCIÓN: Obtener tipos de movimiento con código y nombre
    $result2 = pg_query($conexion, "SELECT cod_tipomovimiento, nombre FROM tipomovimiento");
    if(!$result2){
        echo "Error al seleccionar el tipo de movimiento.";
    }
    
    // OBTENER TODOS LOS MOVIMIENTOS EN ORDEN ASCENDENTE PARA CALCULAR SALDOS
    // CORRECCIÓN: Usar códigos reales que existan en la base de datos
    $result_todos_movimientos = pg_query($conexion, "SELECT 
                                ri.fecha_inventario AS fecha,
                                ri.cod_inventario,
                                p.nombre AS producto_nombre,
                                p.cod_producto AS producto_codigo,
                                p.precio_caja AS precio_costo_producto,
                                p.unidades_por_caja,
                                tm.nombre AS tipomovimiento_nombre,
                                tm.cod_tipomovimiento AS tipomovimiento_codigo,
                                u.usuario AS usuario_nombre,
                                ri.cantidad AS cantidad,
                                ri.precio_unitario AS precio_unitario_calculado,
                                ri.total AS total
                            FROM registroinventario ri
                            JOIN producto p ON ri.cod_producto = p.cod_producto
                            JOIN tipomovimiento tm ON ri.cod_tipomovimiento = tm.cod_tipomovimiento
                            JOIN usuario u ON ri.cod_usuario = u.cod_usuario
                            ORDER BY p.cod_producto, ri.fecha_inventario ASC, ri.cod_inventario ASC");

    // CALCULAR SALDOS HISTÓRICOS PARA CADA MOVIMIENTO
    $movimientos_con_saldo_historico = [];
    $saldos_acumulados = [];

    if($result_todos_movimientos && pg_num_rows($result_todos_movimientos) > 0) {
        while($row = pg_fetch_assoc($result_todos_movimientos)) {
            $cod_producto = $row['producto_codigo'];
            
            // INICIALIZAR SALDO SI ES LA PRIMERA VEZ QUE VEMOS ESTE PRODUCTO
            if(!isset($saldos_acumulados[$cod_producto])) {
                $saldos_acumulados[$cod_producto] = [
                    'unidades' => 0,
                    'costo_total' => 0
                ];
            }
            
            // CALCULAR COSTO TOTAL CORRECTO
            $costo_total_movimiento = $row['total'];
            
            // DETERMINAR SI ES ENTRADA O SALIDA BASADO EN EL NOMBRE DEL MOVIMIENTO
            $es_entrada = stripos($row['tipomovimiento_nombre'], 'entrada') !== false;
            $es_salida = stripos($row['tipomovimiento_nombre'], 'salida') !== false;
            
            // CALCULAR NUEVO SALDO SEGÚN EL TIPO DE MOVIMIENTO
            if($es_entrada) {
                $saldos_acumulados[$cod_producto]['unidades'] += $row['cantidad'];
                $saldos_acumulados[$cod_producto]['costo_total'] += $costo_total_movimiento;
            } else if($es_salida) {
                $saldos_acumulados[$cod_producto]['unidades'] -= $row['cantidad'];
                $saldos_acumulados[$cod_producto]['costo_total'] -= $costo_total_movimiento;
            }
            
            // GUARDAR MOVIMIENTO CON SALDO HISTÓRICO
            $movimientos_con_saldo_historico[] = [
                'fecha' => $row['fecha'],
                'producto_nombre' => $row['producto_nombre'],
                'producto_codigo' => $row['producto_codigo'],
                'tipomovimiento_nombre' => $row['tipomovimiento_nombre'],
                'tipomovimiento_codigo' => $row['tipomovimiento_codigo'],
                'usuario_nombre' => $row['usuario_nombre'],
                'cantidad' => $row['cantidad'],
                'precio_unitario' => $row['precio_unitario_calculado'],
                'total' => $costo_total_movimiento,
                'saldo_unidades' => $saldos_acumulados[$cod_producto]['unidades'],
                'saldo_costo_total' => $saldos_acumulados[$cod_producto]['costo_total'],
                'es_entrada' => $es_entrada,
                'es_salida' => $es_salida
            ];
        }
    }

    // ORDENAR MOVIMIENTOS POR FECHA DESCENDENTE PARA MOSTRAR
    usort($movimientos_con_saldo_historico, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });

    // CALCULAR ESTADÍSTICAS CORRECTAMENTE
    $total_entradas = 0;
    $total_salidas = 0;
    $stock_valorizado = 0;
    
    // Calcular stock valorizado actual
    foreach($saldos_acumulados as $saldo) {
        $stock_valorizado += $saldo['costo_total'];
    }
    
    // Calcular totales de entradas y salidas
    foreach($movimientos_con_saldo_historico as $mov) {
        if($mov['es_entrada']) {
            $total_entradas += $mov['total'];
        } else if($mov['es_salida']) {
            $total_salidas += $mov['total'];
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
                <h1 class="h3 mb-0"><i class="fas fa-chart-line me-2"></i>Kardex Principal</h1>
                <div>
                    <button class="btn btn-mad me-2">
                        <i class="fas fa-download me-2"></i>Exportar
                    </button>
                    <button class="btn btn-outline-secondary" id="btnResetFilters">
                        <i class="fas fa-redo me-2"></i>Limpiar Filtros
                    </button>
                </div>
            </div>

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

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-0 bg-primary text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1"><?php echo count($movimientos_con_saldo_historico); ?></h4>
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

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary"><i class="fas fa-list-alt me-2"></i>Registro de Movimientos</h5>
                        <span class="badge bg-primary" id="movementCount"><?php echo count($movimientos_con_saldo_historico); ?> movimientos</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered text-center align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th rowspan="2" class="bg-light">Fecha</th>
                                    <th rowspan="2" class="bg-light">Usuario</th>
                                    <th rowspan="2" class="bg-light">Producto</th>
                                    <th colspan="3" class="bg-success text-white">Entradas</th>
                                    <th colspan="3" class="bg-danger text-white">Salidas</th>
                                    <th colspan="2" class="bg-primary text-white">Saldo Final</th>
                                </tr>
                                <tr>
                                    <th class="bg-success-light">Unidades</th>
                                    <th class="bg-success-light">Costo Unit.</th>
                                    <th class="bg-success-light">Costo Total</th>

                                    <th class="bg-danger-light">Unidades</th>
                                    <th class="bg-danger-light">Costo Unit.</th>
                                    <th class="bg-danger-light">Costo Total</th>

                                    <th class="bg-primary-light">Unidades</th>
                                    <th class="bg-primary-light">Costo Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(!empty($movimientos_con_saldo_historico)) {
                                    foreach($movimientos_con_saldo_historico as $mov) {
                                        if($mov['es_entrada']){
                                            echo "
                                            <tr data-producto='{$mov['producto_codigo']}' data-movimiento='{$mov['tipomovimiento_codigo']}' data-fecha='{$mov['fecha']}'>
                                                <td>{$mov['fecha']}</td>
                                                <td>{$mov['usuario_nombre']}</td>
                                                <td>
                                                    <div class='fw-bold text-primary'>{$mov['producto_nombre']}</div>
                                                    <small class='text-muted'>{$mov['producto_codigo']}</small>
                                                </td>
                                                <td class='text-success fw-bold'>{$mov['cantidad']}</td>
                                                <td>S/ " . number_format($mov['precio_unitario'], 2) . "</td>
                                                <td class='fw-bold'>S/ " . number_format($mov['total'], 2) . "</td>
                                                <td>-</td>
                                                <td>-</td>
                                                <td>-</td>
                                                <td class='fw-bold text-primary'>{$mov['saldo_unidades']}</td>
                                                <td class='fw-bold text-primary'>S/ " . number_format($mov['saldo_costo_total'], 2) . "</td>
                                            </tr>
                                            ";
                                            
                                        } else if($mov['es_salida']){
                                            echo "
                                            <tr data-producto='{$mov['producto_codigo']}' data-movimiento='{$mov['tipomovimiento_codigo']}' data-fecha='{$mov['fecha']}'>
                                                <td>{$mov['fecha']}</td>
                                                <td>{$mov['usuario_nombre']}</td>
                                                <td>
                                                    <div class='fw-bold text-primary'>{$mov['producto_nombre']}</div>
                                                    <small class='text-muted'>{$mov['producto_codigo']}</small>
                                                </td>
                                                <td>-</td>
                                                <td>-</td>
                                                <td>-</td>
                                                <td class='text-danger fw-bold'>{$mov['cantidad']}</td>
                                                <td>S/ " . number_format($mov['precio_unitario'], 2) . "</td>
                                                <td class='fw-bold'>S/ " . number_format($mov['total'], 2) . "</td>
                                                <td class='fw-bold text-primary'>{$mov['saldo_unidades']}</td>
                                                <td class='fw-bold text-primary'>S/ " . number_format($mov['saldo_costo_total'], 2) . "</td>
                                            </tr>
                                            ";
                                        }               
                                    }
                                } else {
                                    echo "<tr><td colspan='11' class='text-center py-4'>No hay movimientos registrados</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
            
                    <nav>
                        <ul class="pagination justify-content-center mt-4">
                            <li class="page-item disabled"><a class="page-link" href="#">Anterior</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item"><a class="page-link" href="#">Siguiente</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
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
                            
    // Cierra el menú si haces clic fuera
    document.addEventListener("click", (e) => {
        if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
            dropdownList.style.display = "none";
            arrow.style.transform = "rotate(0deg)";
        }
    });

    // Filtros mejorados
    document.addEventListener('DOMContentLoaded', function() {
        const productFilter = document.getElementById('productFilter');
        const movementType = document.getElementById('movementType');
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const btnResetFilters = document.getElementById('btnResetFilters');
        const tableRows = document.querySelectorAll('tbody tr');
        const movementCount = document.getElementById('movementCount');

        function aplicarFiltros() {
            const productoVal = productFilter.value;
            const movimientoVal = movementType.value;
            const fechaDesde = dateFrom.value;
            const fechaHasta = dateTo.value;

            let visibleCount = 0;

            tableRows.forEach(row => {
                if (row.cells.length < 10) return;

                const productoData = row.getAttribute('data-producto');
                const movimientoData = row.getAttribute('data-movimiento');
                const fechaData = row.getAttribute('data-fecha');

                let mostrar = true;

                // Filtro por producto
                if (productoVal && productoData !== productoVal) {
                    mostrar = false;
                }

                // Filtro por tipo de movimiento
                if (movimientoVal && movimientoData !== movimientoVal) {
                    mostrar = false;
                }

                // Filtro por fecha
                if (fechaDesde && fechaData < fechaDesde) {
                    mostrar = false;
                }

                if (fechaHasta && fechaData > fechaHasta) {
                    mostrar = false;
                }

                row.style.display = mostrar ? '' : 'none';
                
                if (mostrar) {
                    visibleCount++;
                }
            });

            // Actualizar contador
            movementCount.textContent = visibleCount + ' movimientos';
        }

        function resetearFiltros() {
            productFilter.selectedIndex = 0;
            movementType.selectedIndex = 0;
            dateFrom.value = '';
            dateTo.value = '';
            aplicarFiltros();
        }

        productFilter.addEventListener('change', aplicarFiltros);
        movementType.addEventListener('change', aplicarFiltros);
        dateFrom.addEventListener('change', aplicarFiltros);
        dateTo.addEventListener('change', aplicarFiltros);
        btnResetFilters.addEventListener('click', resetearFiltros);

        // Aplicar filtros inicialmente
        aplicarFiltros();
    });
    </script>
</body>
</html>