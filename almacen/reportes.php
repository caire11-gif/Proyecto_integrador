<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Reportes de Entradas y Traslados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/almacen-estilo.css">
    <link rel="stylesheet" href="css/almacen-reportes/reportes.css">
</head>
<body>
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
                    <a href="#" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
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
            
            <div class="contenido-principal">
                <div class="container-fluid reportes-page">
                    <!-- Encabezado -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-1"><i class="fas fa-chart-bar me-2"></i>Reportes de Entradas y Traslados</h4>
                            <p class="text-muted mb-0">Análisis completo de Entradas y Salidas</p>
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

                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Fecha Inicio</label>
                                    <input type="date" class="form-control" id="fechaInicio" value="2024-12-01">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Fecha Fin</label>
                                    <input type="date" class="form-control" id="fechaFin" value="2024-12-19">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Producto</label>
                                    <select class="form-select" id="filtroProducto">
                                        <option value="">Todos los productos</option>
                                        <option value="coca500">Coca Cola 500ml</option>
                                        <option value="oreo">Galletas Oreo</option>
                                        <option value="aceite">Aceite Primor 1L</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tipo de Movimiento</label>
                                    <select class="form-select" id="filtroMovimiento">
                                        <option value="">Todos los movimientos</option>
                                        <option value="entrada">Entradas</option>
                                        <option value="salida">Salidas</option>
                                        <option value="traslado">Traslados</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button id="btnFiltrar" class="btn btn-mad w-100">
                                        <i class="fas fa-filter me-2"></i>Filtrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestañas -->
                    <ul class="nav nav-tabs mb-4" id="reportesTabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#tabReportes">
                                <i class="fas fa-list-alt me-2"></i>Reportes Generales
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tabProductosVendidos">
                                <i class="fas fa-trophy me-2"></i>Productos Más Vendidos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tabEstadisticas">
                                <i class="fas fa-chart-line me-2"></i>Estadísticas
                            </a>
                        </li>
                    </ul>

                    <!-- Contenido de pestañas -->
                    <div class="tab-content">
                        <!-- Pestaña Reportes Generales -->
                        <div class="tab-pane fade show active" id="tabReportes">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Reportes de Entradas y Traslados - Diciembre 2024</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Producto</th>
                                                    <th>Movimiento</th>
                                                    <th>Entrada</th>
                                                    <th>Salida</th>
                                                    <th>Stock Actual</th>
                                                    <th>Usuario</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>19/12/2024</td>
                                                    <td><strong>Coca Cola 500ml</strong></td>
                                                    <td><span class="badge bg-success">Entrada Proveedor</span></td>
                                                    <td>10 cajas</td>
                                                    <td>-</td>
                                                    <td>12 cajas</td>
                                                    <td>María Alvarez</td>
                                                </tr>
                                                <tr>
                                                    <td>18/12/2024</td>
                                                    <td><strong>Galletas Oreo</strong></td>
                                                    <td><span class="badge bg-primary">Traslado Tienda</span></td>
                                                    <td>-</td>
                                                    <td>3 cajas</td>
                                                    <td>8 cajas</td>
                                                    <td>María Alvarez</td>
                                                </tr>
                                                <tr>
                                                    <td>18/12/2024</td>
                                                    <td><strong>Coca Cola 500ml</strong></td>
                                                    <td><span class="badge bg-info">Venta</span></td>
                                                    <td>-</td>
                                                    <td>24 unidades</td>
                                                    <td>48 unidades</td>
                                                    <td>Carlos Ruiz</td>
                                                </tr>
                                                <tr>
                                                    <td>17/12/2024</td>
                                                    <td><strong>Aceite Primor 1L</strong></td>
                                                    <td><span class="badge bg-warning">Ajuste Inventario</span></td>
                                                    <td>2 unidades</td>
                                                    <td>-</td>
                                                    <td>15 unidades</td>
                                                    <td>María Alvarez</td>
                                                </tr>
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
                                    <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Productos Más Vendidos - Diciembre 2024</h5>
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
                                                    <th>Tendencia</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>1</td>
                                                    <td><strong>Coca Cola 500ml</strong></td>
                                                    <td>Bebidas</td>
                                                    <td>240 unidades</td>
                                                    <td>S/ 912.00</td>
                                                    <td><i class="fas fa-arrow-up text-success"></i> +15%</td>
                                                </tr>
                                                <tr>
                                                    <td>2</td>
                                                    <td><strong>Galletas Oreo</strong></td>
                                                    <td>Galletas</td>
                                                    <td>180 unidades</td>
                                                    <td>S/ 450.00</td>
                                                    <td><i class="fas fa-arrow-up text-success"></i> +8%</td>
                                                </tr>
                                                <tr>
                                                    <td>3</td>
                                                    <td><strong>Aceite Primor 1L</strong></td>
                                                    <td>Abarrotes</td>
                                                    <td>45 unidades</td>
                                                    <td>S/ 540.00</td>
                                                    <td><i class="fas fa-arrow-down text-danger"></i> -5%</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pestaña Estadísticas -->
                        <div class="tab-pane fade" id="tabEstadisticas">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Movimientos Mensuales</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="estadistica-chart">
                                                <div class="chart-placeholder">
                                                    <div class="chart-bars">
                                                        <div class="chart-bar entrada" style="height: 80%">
                                                            <span class="chart-label">Entradas</span>
                                                            <span class="chart-value">80%</span>
                                                        </div>
                                                        <div class="chart-bar salida" style="height: 60%">
                                                            <span class="chart-label">Salidas</span>
                                                            <span class="chart-value">60%</span>
                                                        </div>
                                                        <div class="chart-bar traslado" style="height: 40%">
                                                            <span class="chart-label">Traslados</span>
                                                            <span class="chart-value">40%</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-percentage me-2"></i>Resumen del Mes</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="resumen-estadisticas">
                                                <div class="estadistica-item">
                                                    <div class="estadistica-icon bg-success">
                                                        <i class="fas fa-arrow-down"></i>
                                                    </div>
                                                    <div class="estadistica-info">
                                                        <h4>156</h4>
                                                        <small>Entradas</small>
                                                    </div>
                                                </div>
                                                <div class="estadistica-item">
                                                    <div class="estadistica-icon bg-danger">
                                                        <i class="fas fa-arrow-up"></i>
                                                    </div>
                                                    <div class="estadistica-info">
                                                        <h4>89</h4>
                                                        <small>Salidas</small>
                                                    </div>
                                                </div>
                                                <div class="estadistica-item">
                                                    <div class="estadistica-icon bg-primary">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </div>
                                                    <div class="estadistica-info">
                                                        <h4>34</h4>
                                                        <small>Traslados</small>
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function initPage() {
            console.log('Reportes cargada');

            // Botón filtrar
            document.getElementById('btnFiltrar').addEventListener('click', function() {
                const fechaInicio = document.getElementById('fechaInicio').value;
                const fechaFin = document.getElementById('fechaFin').value;
                const producto = document.getElementById('filtroProducto').value;
                const movimiento = document.getElementById('filtroMovimiento').value;
                
                showNotification(`Filtro aplicado: ${fechaInicio} a ${fechaFin}`, 'success');
            });

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
                    showNotification(`Buscando: ${searchTerm}`, 'info');
                }
            });
        }

        function showNotification(message, type) {
            // Esta función debería implementarse según tu sistema de notificaciones
            const alertClass = type === 'success' ? 'alert-success' : 
                              type === 'info' ? 'alert-info' : 'alert-warning';
            
            // Crear notificación temporal
            const notification = document.createElement('div');
            notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
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
                // Redirigir o realizar otras acciones
            }
        }

        // Inicializar página cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', initPage);
    </script>
</body>
</html>