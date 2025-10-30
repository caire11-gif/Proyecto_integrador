<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Reportes de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/almacen-estilo.css">
    <link rel="stylesheet" href="css/almacen-reportes/reportes.css">
</head>
<body>
    <?php
    // Conexión a la base de datos
    $conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
    if(!$conexion){
        echo "Error de conexión.";
        exit;
    }

    // Obtener parámetros de filtros
    $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
    $fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
    $producto_filtro = isset($_GET['producto']) ? $_GET['producto'] : '';
    $movimiento_filtro = isset($_GET['movimiento']) ? $_GET['movimiento'] : '';

    // Obtener datos para filtros
    $query_productos = "SELECT cod_producto, nombre FROM producto ORDER BY nombre";
    $result_productos = pg_query($conexion, $query_productos);

    // Construir consulta para movimientos con filtros
    $where_conditions = array();
    $query_params = array();

    // Filtro por fecha
    if(!empty($fecha_inicio) && !empty($fecha_fin)){
        $where_conditions[] = "m.fecha_movimiento BETWEEN $1 AND $2";
        $query_params[] = $fecha_inicio;
        $query_params[] = $fecha_fin;
    }

    // Filtro por producto
    if(!empty($producto_filtro)){
        $where_conditions[] = "m.cod_producto = $" . (count($query_params) + 1);
        $query_params[] = $producto_filtro;
    }

    // Filtro por tipo de movimiento
    if(!empty($movimiento_filtro)){
        if($movimiento_filtro == 'entrada') {
            $where_conditions[] = "m.cod_tipomovimiento = 'mov001'";
        } elseif($movimiento_filtro == 'salida') {
            $where_conditions[] = "m.cod_tipomovimiento = 'mov002'";
        } elseif($movimiento_filtro == 'ajuste') {
            $where_conditions[] = "m.cod_tipomovimiento = 'mov003'";
        }
    }

    // Consulta base para movimientos
    $query_movimientos = "
        SELECT 
            m.cod_movimiento,
            m.fecha_movimiento,
            p.nombre as producto_nombre,
            tm.nombre as tipo_movimiento,
            m.cod_tipomovimiento,
            m.observacion,
            u.usuario,
            p.stock
        FROM movimiento m
        JOIN producto p ON m.cod_producto = p.cod_producto
        JOIN tipomovimiento tm ON m.cod_tipomovimiento = tm.cod_tipomovimiento
        JOIN usuario u ON m.cod_usuario = u.cod_usuario
    ";

    // Agregar condiciones WHERE si existen
    if(!empty($where_conditions)){
        $query_movimientos .= " WHERE " . implode(" AND ", $where_conditions);
    }

    $query_movimientos .= " ORDER BY m.fecha_movimiento DESC LIMIT 100";

    // Ejecutar consulta con parámetros si existen
    if(!empty($query_params)){
        $result_movimientos = pg_query_params($conexion, $query_movimientos, $query_params);
    } else {
        $result_movimientos = pg_query($conexion, $query_movimientos);
    }

    // Consulta para productos más vendidos con filtro de fecha
    $query_ventas = "
        SELECT 
            p.nombre as producto_nombre,
            c.nombre as categoria_nombre,
            SUM(dv.cantidad_unidades) as unidades_vendidas,
            SUM(dv.total) as ingresos_totales,
            p.stock
        FROM detalleventa dv
        JOIN producto p ON dv.cod_producto = p.cod_producto
        JOIN categoria c ON p.cod_categoria = c.cod_categoria
        JOIN venta v ON dv.cod_venta = v.cod_venta
        WHERE v.fecha_venta BETWEEN $1 AND $2
        GROUP BY p.cod_producto, p.nombre, c.nombre, p.stock
        ORDER BY unidades_vendidas DESC
        LIMIT 10
    ";
    
    $result_ventas = pg_query_params($conexion, $query_ventas, array($fecha_inicio, $fecha_fin));

    function showNotification($message, $type) {
        $alert_class = $type == 'success' ? 'alert-success' : 'alert-danger';
        echo "<div class='alert {$alert_class} alert-dismissible fade show position-fixed' style='top: 20px; right: 20px; z-index: 1050; min-width: 300px;'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
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
                    <small id="userRole">Encargado</small>
                </div>

                <div class="turno-info">
                    <div class="fw-bold">María Alvarez</div>
                    <small>Turno: 08:00 - 16:00</small><br>
                    <small id="tiempoActivoSidebar">0h 0m activo</small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.php" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="gestionproductos.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Gestión de Productos</ul></a>
                    <a href="almacenproveedores.php" class="nav-link"><ul><i class="fas fa-truck"></i>Proveedores</ul></a>
                    <a href="entradaproveedor.php" class="nav-link"><ul><i class="fas fa-truck-loading"></i>Entradas Proveedor</ul></a>
                    <a href="notificaciones.php" class="nav-link"><ul><i class="fas fa-bell"></i>Notificaciones</ul></a>
                    <a href="reportes.php" class="nav-link active"><ul><i class="fas fa-chart-bar"></i>Reportes</ul></a>
                    <a href="../login.php" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
                </div>
            </div>
        </main>

        <div class="secundario">
            <div class="header">
                <div class="caja-busqueda">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Buscar reportes, productos..." id="globalSearch">
                </div>
                
                <div class="usuario-info">
                    <div class="usuario-avatar" id="usuarioAvatar">MA</div>
                    <div>
                        <div class="fw-bold fs-5" id="userName">María Alvarez</div>
                        <small class="text-muted" id="userPosition">Encargado - Turno Activo</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger ms-3" onclick="cerrarTurno()">
                        <i class="fas fa-sign-out-alt me-1"></i>Cerrar Turno
                    </button>
                </div>
            </div>
            <br>
            <div class="contenido-principal">
                <div class="container-fluid reportes-page">
                    <!-- Encabezado -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-1"><i class="fas fa-chart-bar me-2"></i>Reportes de Inventario</h4>
                            <p class="text-muted mb-0">Análisis completo de movimientos y ventas</p>
                        </div>
                        <div>
                            <button id="btnExportar" class="btn btn-outline-primary me-2">
                                <i class="fas fa-file-export me-2"></i>Exportar
                            </button>
                            <button id="btnImprimir" class="btn btn-mad">
                                <i class="fas fa-print me-2"></i>Imprimir
                            </button>
                        </div>
                    </div>

                    <!-- Filtros Automáticos -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" id="filtroForm">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Fecha Inicio</label>
                                        <input type="date" class="form-control" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" onchange="this.form.submit()">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Fecha Fin</label>
                                        <input type="date" class="form-control" name="fecha_fin" value="<?php echo $fecha_fin; ?>" onchange="this.form.submit()">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Producto</label>
                                        <select class="form-select" name="producto" onchange="this.form.submit()">
                                            <option value="">Todos los productos</option>
                                            <?php
                                            if($result_productos){
                                                while($producto = pg_fetch_assoc($result_productos)){
                                                    $selected = ($producto_filtro == $producto['cod_producto']) ? 'selected' : '';
                                                    echo "<option value='{$producto['cod_producto']}' $selected>{$producto['nombre']}</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tipo de Movimiento</label>
                                        <select class="form-select" name="movimiento" onchange="this.form.submit()">
                                            <option value="">Todos los movimientos</option>
                                            <option value="entrada" <?php echo ($movimiento_filtro == 'entrada') ? 'selected' : ''; ?>>Entradas</option>
                                            <option value="salida" <?php echo ($movimiento_filtro == 'salida') ? 'selected' : ''; ?>>Salidas</option>
                                            <option value="ajuste" <?php echo ($movimiento_filtro == 'ajuste') ? 'selected' : ''; ?>>Ajustes</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12 d-flex justify-content-end">
                                        <button type="button" class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                                            <i class="fas fa-times me-2"></i>Limpiar Filtros
                                        </button>
                                    </div>
                                </div>

                                <!-- Mostrar filtros activos -->
                                <?php if(!empty($fecha_inicio) || !empty($fecha_fin) || !empty($producto_filtro) || !empty($movimiento_filtro)): ?>
                                <div class="mt-3">
                                    <small class="text-muted">Filtros activos:</small>
                                    <div class="d-flex flex-wrap gap-2 mt-1">
                                        <?php if(!empty($fecha_inicio) && !empty($fecha_fin)): ?>
                                            <span class="badge bg-primary">
                                                Fechas: <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> - <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
                                                <a href="?" class="text-white ms-1" onclick="removerFiltro('fecha_inicio'); removerFiltro('fecha_fin'); return false;">×</a>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($producto_filtro)): 
                                            $producto_nombre = '';
                                            $query_prod = "SELECT nombre FROM producto WHERE cod_producto = $1";
                                            $result_prod = pg_query_params($conexion, $query_prod, array($producto_filtro));
                                            if($result_prod && pg_num_rows($result_prod) > 0){
                                                $producto_nombre = pg_fetch_result($result_prod, 0, 0);
                                            }
                                        ?>
                                            <span class="badge bg-success">
                                                Producto: <?php echo $producto_nombre ?: $producto_filtro; ?>
                                                <a href="?" class="text-white ms-1" onclick="removerFiltro('producto'); return false;">×</a>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($movimiento_filtro)): ?>
                                            <span class="badge bg-info">
                                                Movimiento: <?php 
                                                    $movimientos = ['entrada' => 'Entradas', 'salida' => 'Salidas', 'ajuste' => 'Ajustes'];
                                                    echo $movimientos[$movimiento_filtro] ?? $movimiento_filtro;
                                                ?>
                                                <a href="?" class="text-white ms-1" onclick="removerFiltro('movimiento'); return false;">×</a>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <!-- Pestañas -->
                    <ul class="nav nav-tabs mb-4" id="reportesTabs">
                        <li class="nav-item">
                            <a class="nav-link active text-dark" data-bs-toggle="tab" href="#tabReportes">
                                <i class="fas fa-list-alt me-2"></i>Movimientos
                                <?php if($result_movimientos): ?>
                                <span class="badge bg-primary ms-1"><?php echo pg_num_rows($result_movimientos); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark" data-bs-toggle="tab" href="#tabProductosVendidos">
                                <i class="fas fa-trophy me-2"></i>Productos Más Vendidos
                                <?php if($result_ventas): ?>
                                <span class="badge bg-success ms-1"><?php echo pg_num_rows($result_ventas); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>

                    <!-- Contenido de pestañas -->
                    <div class="tab-content">
                        <!-- Pestaña Movimientos -->
                        <div class="tab-pane fade show active" id="tabReportes">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-list-alt me-2"></i>Movimientos 
                                        <?php if(!empty($fecha_inicio) && !empty($fecha_fin)): ?>
                                        - <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> al <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
                                        <?php endif; ?>
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Producto</th>
                                                    <th>Movimiento</th>
                                                    <th>Detalle</th>
                                                    <th>Stock Actual</th>
                                                    <th>Usuario</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if($result_movimientos && pg_num_rows($result_movimientos) > 0){
                                                    while($movimiento = pg_fetch_assoc($result_movimientos)){
                                                        // Determinar color del badge según el tipo de movimiento
                                                        $badge_class = '';
                                                        if($movimiento['cod_tipomovimiento'] == 'mov001') {
                                                            $badge_class = 'bg-success';
                                                        } elseif($movimiento['cod_tipomovimiento'] == 'mov002') {
                                                            $badge_class = 'bg-danger';
                                                        } else {
                                                            $badge_class = 'bg-warning';
                                                        }
                                                        
                                                        echo "
                                                        <tr>
                                                            <td>" . date('d/m/Y', strtotime($movimiento['fecha_movimiento'])) . "</td>
                                                            <td><strong>{$movimiento['producto_nombre']}</strong></td>
                                                            <td><span class='badge {$badge_class}'>{$movimiento['tipo_movimiento']}</span></td>
                                                            <td>{$movimiento['observacion']}</td>
                                                            <td>
                                                                <span class='badge " . ($movimiento['stock'] < 20 ? 'bg-warning' : 'bg-success') . "'>
                                                                    {$movimiento['stock']} und.
                                                                </span>
                                                            </td>
                                                            <td>{$movimiento['usuario']}</td>
                                                        </tr>
                                                        ";
                                                    }
                                                } else {
                                                    echo "
                                                    <tr>
                                                        <td colspan='6' class='text-center py-4'>
                                                            <i class='fas fa-info-circle fa-2x text-muted mb-2'></i>
                                                            <p class='mb-0 text-muted'>No hay movimientos registrados con los filtros aplicados</p>
                                                            <small class='text-muted'>Intenta cambiar los criterios de búsqueda</small>
                                                        </td>
                                                    </tr>
                                                    ";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pestaña Productos Más Vendidos -->
                        <div class="tab-pane fade" id="tabProductosVendidos">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-trophy me-2"></i>Productos Más Vendidos
                                        <?php if(!empty($fecha_inicio) && !empty($fecha_fin)): ?>
                                        - <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> al <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
                                        <?php endif; ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Producto</th>
                                                    <th>Categoría</th>
                                                    <th>Unidades Vendidas</th>
                                                    <th>Ingresos Totales</th>
                                                    <th>Stock Actual</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if($result_ventas && pg_num_rows($result_ventas) > 0){
                                                    $contador = 1;
                                                    while($venta = pg_fetch_assoc($result_ventas)){
                                                        echo "
                                                        <tr>
                                                            <td>{$contador}</td>
                                                            <td><strong>{$venta['producto_nombre']}</strong></td>
                                                            <td>{$venta['categoria_nombre']}</td>
                                                            <td>{$venta['unidades_vendidas']} unidades</td>
                                                            <td>S/ " . number_format($venta['ingresos_totales'], 2) . "</td>
                                                            <td>
                                                                <span class='badge " . ($venta['stock'] < 20 ? 'bg-warning' : 'bg-success') . "'>
                                                                    {$venta['stock']} und.
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        ";
                                                        $contador++;
                                                    }
                                                } else {
                                                    echo "
                                                    <tr>
                                                        <td colspan='6' class='text-center py-4'>
                                                            <i class='fas fa-chart-line fa-2x text-muted mb-2'></i>
                                                            <p class='mb-0 text-muted'>No hay datos de ventas en el período seleccionado</p>
                                                            <small class='text-muted'>Intenta cambiar el rango de fechas</small>
                                                        </td>
                                                    </tr>
                                                    ";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function initPage() {
            console.log('Reportes cargada');

            // Botón exportar
            document.getElementById('btnExportar').addEventListener('click', function() {
                showNotification('Exportando reporte a Excel...', 'info');
                // Simular descarga
                setTimeout(() => {
                    showNotification('Reporte exportado correctamente', 'success');
                }, 2000);
            });

            // Botón imprimir
            document.getElementById('btnImprimir').addEventListener('click', function() {
                showNotification('Preparando para impresión...', 'info');
                window.print();
            });

            // Búsqueda global
            document.getElementById('globalSearch').addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                if (searchTerm.length > 2) {
                    filtrarTablas(searchTerm);
                } else if (searchTerm.length === 0) {
                    mostrarTodasFilas();
                }
            });
        }

        function limpiarFiltros() {
            window.location.href = 'reportes.php';
        }

        function removerFiltro(filtro) {
            const url = new URL(window.location.href);
            url.searchParams.delete(filtro);
            window.location.href = url.toString();
        }

        function filtrarTablas(termino) {
            const tablas = document.querySelectorAll('table tbody');
            tablas.forEach(tabla => {
                const filas = tabla.querySelectorAll('tr');
                let encontradas = 0;
                
                filas.forEach(fila => {
                    const texto = fila.textContent.toLowerCase();
                    if (texto.includes(termino)) {
                        fila.style.display = '';
                        encontradas++;
                    } else {
                        fila.style.display = 'none';
                    }
                });
            });
        }

        function mostrarTodasFilas() {
            const filas = document.querySelectorAll('table tbody tr');
            filas.forEach(fila => {
                fila.style.display = '';
            });
        }

        function showNotification(message, type) {
            // Crear notificación temporal
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remover después de 3 segundos
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }

        function cerrarTurno() {
            if(confirm('¿Está seguro de que desea cerrar el turno?')) {
                showNotification('Turno cerrado correctamente', 'success');
            }
        }

        // Inicializar página cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', initPage);
    </script>
</body>
</html>

<?php
// Cerrar conexión
if($conexion){
    pg_close($conexion);
}
?>