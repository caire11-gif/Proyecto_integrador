<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Sistema de Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/administrador-dashboard/datos.css">
    <link rel="stylesheet" href="css/administrador-estilo.css">
</head>
<body>
    <?php
    $conexion=pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
    if(!$conexion){
        echo "Un error de conexión ocurrió";
    }

    $result1=pg_query($conexion,"SELECT SUM(total) AS total FROM detalleventa
                      WHERE DATE_TRUNC('month', fecha_venta) = DATE_TRUNC('month', CURRENT_DATE)");
    if(!$result1){
        echo "Error al sumar todas las ventas.";
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

        
                <div class="turno-info">
                    <div class="fw-bold">María Alvarez</div>
                    <small>Turno: 08:00 - 16:00</small><br>
                    <small id="tiempoActivoSidebar">0h 0m activo</small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.php" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="kardexprincipal.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Kardex Principal</ul></a>
                    <a href="proveedores.php" class="nav-link"><ul><i class="fas fa-truck"></i>Proveedores</ul></a>
                    <a href="controlpersonal.php" class="nav-link"><ul><i class="fas fa-truck-loading"></i>Control de Personal</ul></a>
                    <a href="registroventas.php" class="nav-link"><ul><i class="fas fa-arrow-right"></i>Registro de Ventas</ul></a>
                    <a href="configuracion.php" class="nav-link"><ul><i class="fas fa-bell"></i>Configuración</ul></a>
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
                    <div class="usuario-avatar" id="usuarioAvatar">AP</div>
                    <div>
                        <div class="fw-bold fs-5" id="userName">Admin Principal</div>
                        <small class="text-muted" id="userPosition">Administrador - Turno Activo</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger ms-3" onclick="cerrarTurno()">
                        <i class="fas fa-sign-out-alt me-1"></i>Cerrar Turno
                    </button>
                </div>
            </div>
            <br>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Administrativo</h1>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-mad w-100" id="btnNuevaAlerta">
                        <i class="fas fa-plus me-2"></i>Nueva notificacion
                    </button>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Ventas del Mes</div>
                                    <?php
                                    while($row1=pg_fetch_assoc($result1)){
                                        echo "
                                        <div class='h4 mb-0 text-primary'>$row1[total]</div>
                                        ";
                                    }
                                    ?>
                                    
                                </div>
                                <div class="bg-primary text-white rounded p-3">
                                    <i class="fas fa-shopping-cart fa-2x"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <span class="text-success small">
                                    <i class="fas fa-arrow-up"></i> 12.5% vs mes anterior
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
        
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Margen Ganancia</div>
                                    <div class="h4 mb-0 text-success">28.3%</div>
                                </div>
                                <div class="bg-success text-white rounded p-3">
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <span class="text-success small">
                                    <i class="fas fa-arrow-up"></i> 2.1% mejor
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
        
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Stock Valorizado</div>
                                    <div class="h4 mb-0 text-info">S/ 124,500</div>
                                </div>
                                <div class="bg-info text-white rounded p-3">
                                    <i class="fas fa-boxes fa-2x"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <span class="text-muted small">215 productos</span>
                            </div>
                        </div>
                    </div>
                </div>
        
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Ticket Promedio</div>
                                    <div class="h4 mb-0 text-warning">S/ 42.50</div>
                                </div>
                                <div class="bg-warning text-white rounded p-3">
                                    <i class="fas fa-receipt fa-2x"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <span class="text-success small">
                                    <i class="fas fa-arrow-up"></i> S/ 3.20 más
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>