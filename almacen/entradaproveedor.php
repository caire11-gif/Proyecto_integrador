<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Sistema de Encargado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/almacen-estilo.css">
</head>
<body>
    <?php
        $conexion=pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
        if(!$conexion){
            echo "Un error de conexión ocurrió.";
            exit;
        }

        $result1=pg_query($conexion, "SELECT nombre FROM proveedor");
        if(!$result1){
            echo "Un error de conexión ocurrió.";
            exit;
        }

        $result2=pg_query($conexion, "SELECT nombre FROM tipodocumento");
        if(!$result2){
            echo "Un error de conexión ocurrió.";
            exit;
        }

        $result3=pg_query($conexion, "SELECT nombre from producto");
        if(!$result3){
            echo "Un error de conexión ocurrió.";
            exit;
        }

        $result4=pg_query($conexion, "SELECT hi.cod_historialproducto AS cod_historial,hi.fecha AS fecha,p.nombre AS producto_nombre,
                          pro.nombre AS proveedor_nombre,p.stock AS stock FROM producto p
                          JOIN historialproducto hi ON p.cod_producto=hi.cod_producto
                          JOIN proveedor pro ON p.cod_proveedor=pro.cod_proveedor")
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
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1"><i class="fas fa-truck-loading me-2"></i>Entradas de Proveedor</h4>
                        <p class="text-muted mb-0">Registra nuevas entradas de productos al almacén</p>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <span class="badge bg-success">Hoy: 20/10/2025</span>
                        </div>
                        <button class="btn btn-outline-primary">
                            <i class="fas fa-file-export me-1"></i>Exportar
                        </button>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-4" id="entradasTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#nuevaEntrada" style="color:black">
                            <i class="fas fa-plus-circle me-1"></i>Nueva Entrada
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#historialEntradas" style="color:black">
                            <i class="fas fa-history me-1"></i>Historial
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="nuevaEntrada">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Registrar Nueva Entrada</h5>
                            </div>
                            <div class="card-body">
                                <form id="formEntrada">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Proveedor: <span class="text-danger">*</span></label>
                                            <select class="form-select" id="proveedorSelect" required>
                                                <option value="">Seleccione proveedor</option>
                                                <?php
                                                while($row1=pg_fetch_assoc($result1)){
                                                    echo "
                                                    <option>$row1[nombre]</option>
                                                    ";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Fecha de Entrada: <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="fechaEntrada" value="2025-10-20" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">N° Factura/Comprobante: (Opcional)</label>
                                            <input type="text" class="form-control" id="numeroFactura" placeholder="Ej: F001-1245">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tipo de Documento:</label>
                                            <select class="form-select" id="tipoComprobante">
                                                <option value="factura">Seleccione documento</option>
                                                <?php
                                                while($row2=pg_fetch_assoc($result2)){
                                                    echo "
                                                    <option>$row2[nombre]</option>
                                                    ";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Productos de la Entrada</h6>
                                            <span class="badge bg-primary" id="contadorProductos">1 producto(s)</span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="35%">Producto</th>
                                                        <th width="15%">Cantidad (Cajas)</th>
                                                        <th width="15%">Precio Unitario (S/)</th>
                                                        <th width="15%">Total (S/)</th>
                                                        <th width="10%">Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="detallesEntrada">
                                                    <tr class="product-row">
                                                        <td>        
                                                            <select class="form-select product-select">
                                                                <option value="">Seleccione producto</option>
                                                                <?php
                                                                while($row3=pg_fetch_assoc($result3)){
                                                                    echo "
                                                                    <option>$row3[nombre]</option>
                                                                    ";
                                                                }
                                                                ?>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control cantidad-input" value="10" min="1">
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control precio-input" value="2.50" step="0.01" min="0">
                                                        </td>
                                                        <td class="total-producto">S/ 25.00</td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-danger action-btn" onclick="eliminarFila(this)">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </td>       
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="5" class="text-end">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="agregarFila()">
                                                                <i class="fas fa-plus me-1"></i>Agregar Producto
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr class="table-active total-row">
                                                        <td colspan="3" class="text-end"><strong>Total General:</strong></td>
                                                        <td colspan="2"><strong id="totalGeneral">S/ 25.00</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="mt-4 text-end">
                                        <button type="reset" class="btn btn-secondary me-2" onclick="resetForm()">Cancelar</button>
                                        <button type="submit" class="btn btn-mad">
                                            <i class="fas fa-save me-1"></i>Registrar Entrada
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
    
                    <div class="tab-pane fade" id="historialEntradas">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Entradas</h5>
                                    <div class="d-flex">
                                        <div class="search-box me-2">
                                            <i class="fas fa-search"></i>
                                            <input type="text" class="form-control" placeholder="Buscar..." id="buscarHistorial">
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-filter me-1"></i>Filtrar
                                            </button>
                                            <ul class="dropdown-menu filter-dropdown" aria-labelledby="filterDropdown">
                                                <li><a class="dropdown-item" href="#" onclick="filtrarHistorial('todos')">Todos</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="filtrarHistorial('hoy')">Hoy</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="filtrarHistorial('semana')">Esta semana</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="filtrarHistorial('mes')">Este mes</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="#" onclick="filtrarHistorial('coca')">Coca Cola</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="filtrarHistorial('nestle')">Nestlé</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="filtrarHistorial('unilever')">Unilever</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="tablaHistorial">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Fecha</th>
                                                <th>Proveedor</th>
                                                <th>Productos</th>
                                                <th>Cantidad</th>
                                                <th>Registrado por</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            while($row4=pg_fetch_assoc($result4)){
                                                echo "
                                                <tr>
                                                    <td>$row4[cod_historial]</td>
                                                    <td>$row4[fecha]</td>
                                                    <td>$row4[producto_nombre]</td>
                                                    <td>$row4[proveedor_nombre]</td>
                                                    <td>$row4[stock]</td>
                                                    <td>
                                                        <button class='btn btn-sm btn-outline-primary action-btn' title='Ver detalles'>
                                                            <i class='fas fa-eye'></i>
                                                        </button>
                                                        <button class='btn btn-sm btn-outline-success action-btn' title='Descargar PDF'>
                                                            <i class='fas fa-file-pdf'></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                ";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        Mostrando <strong>4</strong> de <strong>12</strong> entradas
                                    </div>
                                    <nav>
                                        <ul class="pagination mb-0">
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
                </div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        </div>
    </div>
</body>
</html>