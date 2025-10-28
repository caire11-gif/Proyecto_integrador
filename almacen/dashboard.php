<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Sistema de Encargado</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/almacen-dashboard/datos.css">
    <link rel="stylesheet" href="css/almacen-interfaz/interfaz.css">
</head>
<body>
    <?php
        $conexion=pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
        if(!$conexion){
            echo "Un error de conexión ocurrió.";
            exit;
        }

        $result1=pg_query($conexion,"SELECT COUNT(cod_producto) AS cantidad_producto FROM producto");
        if(!$result1){
            echo "Error al contar los productos.";
        }

        $result2=pg_query($conexion,"SELECT COUNT(cod_categoria) AS cantidad_categoria FROM categoria");
        if(!$result2){
            echo "Erro al contar las categorías.";
        }

        $result3=pg_query($conexion,"SELECT p.nombre AS producto_nombre,p.stock AS stock,c.nombre AS categoria_nombre FROM producto p
                                     JOIN categoria c ON p.cod_categoria=c.cod_categoria
                                     WHERE stock<=5 
                                     ORDER BY cod_producto DESC");
        if(!$result3){
            echo "Error en seleccionar los productos.";
            exit;
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
                    <a href="reportes.php" class="nav-link"><ul><i class="fas fa-chart-bar"></i>Reportes</ul></a>
                    <a href="#" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
                </div>
            </div>
        </main>

        <div class="secundario">
            <div class="header">
                <div class="caja-busqueda">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Buscar productos, ventas..." id="globalSearch">
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

            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card primary h-100">
                            <i class="fas fa-boxes text-primary"></i>
                            <?php
                            while($row1=pg_fetch_assoc($result1)){
                                echo "
                                <div class='number'>$row1[cantidad_producto]</div>
                                ";
                            }
                            ?>
                            <div class="label">Productos en Almacén</div>
                            <?php
                            while($row2=pg_fetch_assoc($result2)){
                                echo "
                                    <div class='text-muted'>$row2[cantidad_categoria]
                                ";

                                if($row2['cantidad_categoria']=1){
                                    echo "categoría</div>";
                                } else {
                                    echo "categorías</div>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card success h-100">
                            <i class="fas fa-truck-loading text-success"></i>
                            <div class="number">23</div>
                            <div class="label">Entradas Este Mes</div>
                            <small class="text-muted">+5% vs mes anterior</small>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card warning h-100">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            <div class="number">7</div>
                            <div class="label">Alertas Activas</div>
                            <small class="text-muted">3 requieren atención urgente</small>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card danger h-100">
                            <i class="fas fa-arrow-right text-danger"></i>
                            <div class="number">15</div>
                            <div class="label">Traslados Pendientes</div>
                            <small class="text-muted">5 para hoy</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="acciones-grid">
                                    <a href="entradaproveedor.php">
                                        <div class="accion-card">
                                            <div class="accion-icono"><i class="fas fa-truck-loading fa-2x mb-2 d-block"></i></div>
                                            <div class="accion-titulo"><span class="fw-bold">Nueva Entrada</span></div>
                                            <div class="accion-descripcion"><small class="d-block mt-1">Registrar compra</small></div>
                                        </div>
                                    </a>

                                    <a href="trasladotienda.php">
                                        <div class=accion-card>
                                            <div class="accion-icono"><i class="fas fa-arrow-right fa-2x mb-2 d-block"></i></div>
                                            <div class="accion-titulo"><span class="fw-bold">Traslado a tienda</span></div>
                                            <div class="accion-descripcion"><small class="db-block mt-1">Envío de productos</small></div>
                                        </div>
                                    </a>

                                    <a href="gestionproductos.php">
                                        <div class="accion-card">
                                            <div class="accion-icono"><i class="fas fa-plus-circle fa-2x mb-2 d-block"></i></div>
                                            <div class="accion-titulo"><span class="fw-bold">Nuevo producto</span></div>
                                            <div class="accion-descripcion"><small class="d-block mt-1">Agregar productos al sistema</small></div>
                                        </div>
                                    </a>

                                    <a href="notificaciones.php">
                                        <div class="accion-card">
                                            <div class="accion-icono"><i class="fas fa-bell fa-2x mb-2 d-block"></i></div>
                                            <div class="accion-titulo"><span class="fw-bold">Ver notificaciones</span></div>
                                            <div class="accion-descripcion"><small class="d-block mt-1">Revisar notificaciones</small></div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Alertas de Stock Urgentes</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1 text-danger">Coca Cola 500ml</h6>
                                            <p class="mb-1">Stock en almacén: <strong>2 cajas</strong> (mínimo: 5)</p>
                                            <small class="text-muted">Última entrada: 15/12/2024</small>
                                        </div>
                                        <span class="badge bg-danger">URGENTE</span>
                                    </div>
                                </div>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1 text-warning">Galletas Oreo</h6>
                                            <p class="mb-1">Stock en tienda: <strong>8 unidades</strong> (mínimo: 15)</p>
                                            <small class="text-muted">Último traslado: 18/12/2024</small>
                                        </div>
                                        <span class="badge bg-warning">BAJO</span>
                                    </div>
                                </div>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1 text-warning">Aceite Primor 1L</h6>
                                            <p class="mb-1">Stock en almacén: <strong>4 cajas</strong> (mínimo: 6)</p>
                                            <small class="text-muted">Última entrada: 12/12/2024</small>
                                        </div>
                                        <span class="badge bg-warning">BAJO</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Movimientos Recientes</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <small class="text-muted">Hoy, 10:30 AM</small>
                                    <p class="mb-0"><i class="fas fa-truck-loading text-success me-2"></i>Entrada de 10 cajas de Leche Gloria</p>
                                </div>
                                <div class="timeline-item warning">
                                    <small class="text-muted">Hoy, 09:15 AM</small>
                                    <p class="mb-0"><i class="fas fa-arrow-right text-warning me-2"></i>Traslado de 5 cajas de Aceite a tienda</p>
                                </div>
                                <div class="timeline-item">
                                    <small class="text-muted">Ayer, 15:45 PM</small>
                                    <p class="mb-0"><i class="fas fa-truck-loading text-success me-2"></i>Entrada de 8 cajas de Arroz Costeño</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Productos que Requieren Atención</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th>Categoría</th>
                                                <th>Stock</th>
                                                <th>Estado</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            while($row3=pg_fetch_assoc($result3)){
                                                echo "
                                                <tr>
                                                    <td><strong>$row3[producto_nombre]</strong></td>
                                                    <td>$row3[categoria_nombre]</td>
                                                    <td>$row3[stock]</td>";

                                                    if($row3['stock']<=1){
                                                        echo "<td><span class='badge bg-danger'>Urgente</span></td>";
                                                    } else {
                                                        echo "<td><span class='badge bg-warning text-dark'>Crítico</span></td>";
                                                    }

                                                    if($row3['stock']<=1){
                                                        echo "<td><button class='btn btn-sm btn-outline-primary'>Solicitar</button></td>";
                                                    } else {
                                                        echo "<td><button class='btn btn-sm btn-outline-warning'>Trasladar</button></td>";
                                                    }
                                                echo "</tr>";
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
</body>
</html>