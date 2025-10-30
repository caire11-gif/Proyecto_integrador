<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Sistema de Encargado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/almacen-trasladotienda/registrotraslado.css">
    <link rel="stylesheet" href="css/almacen-estilo.css">
</head>
<body>
    <?php
    $conexion=pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
    if(!$conexion){
        echo "Un error de conexión ocurrió.";
    }

    $result1=pg_query($conexion,"SELECT nombre FROM producto");
    if(!$result1){
        echo "Error al seleccionar los productos.";
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
                    <a href="trasladotienda.php" class="nav-link"><ul><i class="fas fa-arrow-right"></i>Traslados a Tienda</ul></a>
                    <a href="notificaciones.php" class="nav-link"><ul><i class="fas fa-bell"></i>Notificaciones</ul></a>
                    <a href="reportes.php" class="nav-link"><ul><i class="fas fa-chart-bar"></i>Reportes</ul></a>
                    <a href="../login.php" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1"><i class="fas fa-arrow-right me-2"></i>Traslados a Tienda</h4>
                        <p class="text-muted mb-0">Gestiona el envío de productos del almacén a la tienda</p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-5 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Registrar Traslado</h5>
                            </div>
                            <div class="card-body">
                                <form id="formTraslado">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Producto a Trasladar</label>
                                            <select class="form-select" required>
                                                <option value="">Seleccionar producto...</option>
                                                <?php
                                                while($row1=pg_fetch_assoc($result1)){
                                                    echo "<option>$row1[nombre]</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Cantidad de Cajas</label>
                                            <input type="number" class="form-control" value="5" min="1" max="10" required>
                                            <small class="text-muted">Máximo disponible: 10 cajas</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Unidades Equivalentes</label>
                                            <input type="number" class="form-control" value="120" readonly>
                                            <small class="text-muted">Calculado automáticamente</small>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Fecha de Traslado</label>
                                            <input type="date" class="form-control" value="2024-12-19" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Observaciones</label>
                                            <textarea class="form-control" rows="2" placeholder="Motivo del traslado..."></textarea>
                                        </div>
                                        <div class="col-12 text-end">
                                            <button type="submit" class="btn btn-mad">Confirmar Traslado</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Traslados Recientes</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Producto</th>
                                                <th>Cajas</th>
                                                <th>Unidades</th>
                                                <th>Destino</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>18/12/2024</td>
                                                <td>Coca Cola 500ml</td>
                                                <td>5 cajas</td>
                                                <td>120 unidades</td>
                                                <td>Tienda Principal</td>
                                                <td><span class="badge bg-success">Completado</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>17/12/2024</td>
                                                <td>Galletas Oreo</td>
                                                <td>3 cajas</td>
                                                <td>36 unidades</td>
                                                <td>Tienda Principal</td>
                                                <td><span class="badge bg-success">Completado</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>16/12/2024</td>
                                                <td>Aceite Primor 1L</td>
                                                <td>2 cajas</td>
                                                <td>12 unidades</td>
                                                <td>Tienda Principal</td>
                                                <td><span class="badge bg-warning">Pendiente</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

            
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Resumen de Stock</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4 mb-3">
                                        <div class="border rounded p-3">
                                            <i class="fas fa-warehouse fa-2x text-primary mb-2"></i>
                                            <h4>148</h4>
                                            <small>Productos en Almacén</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="border rounded p-3">
                                            <i class="fas fa-store fa-2x text-success mb-2"></i>
                                            <h4>89</h4>
                                            <small>Productos en Tienda</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="border rounded p-3">
                                            <i class="fas fa-exchange-alt fa-2x text-warning mb-2"></i>
                                            <h4>15</h4>
                                            <small>Traslados Este Mes</small>
                                        </div>
                                    </div>
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