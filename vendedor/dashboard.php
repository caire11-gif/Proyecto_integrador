<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Sistema de Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/vendedor-estilo.css">
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
                    <small id="userRole">Vendedor</small>
                </div>

        
                <div class="turno-info">
                    <div class="fw-bold">Carlos Rodríguez</div>
                    <small>Turno: 08:00 - 16:00</small><br>
                    <small id="tiempoActivoSidebar">0h 0m activo</small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.php" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="nuevaventa.php" class="nav-link"><ul><i class="fas fa-cash-register"></i>Nueva Venta</ul></a>
                    <a href="registrodevolucion.php" class="nav-link"><ul><i class="fas fa-undo-alt"></i>Registrar Devolución</ul></a>
                    <a href="#" class="nav-link"><ul><i class="fas fa-receipt"></i>Boletas/Facturas</ul></a>
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
                    <div class="usuario-avatar" id="usuarioAvatar">CR</div>
                    <div>
                        <div class="fw-bold fs-5" id="userName">Carlos Rodríguez</div>
                        <small class="text-muted" id="userPosition">Vendedor - Turno Activo</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger ms-3" onclick="cerrarTurno()">
                        <i class="fas fa-sign-out-alt me-1"></i>Cerrar Turno
                    </button>
                </div>
            </div>

            <div class="contenedor-dashboard">
                <section class="turno-actual">
                    <div class="turno-card">
                        <h3><i class="fas fa-chart-line"></i> Resumen Turno Actual</h3>
                        <div class="turno-stats">
                            <div class="stat">
                                <span class="stat-value" id="ventasHoy">0</span>
                                <span class="stat-label">Ventas Hoy</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value" id="totalVendido">$0.00</span>
                                <span class="stat-label">Total Vendido</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value" id="tiempoActivo">0h 0m</span>
                                <span class="stat-label">Tiempo Activo</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="acciones-principales">
                    <h3><i class="fas fa-bolt"></i> Acciones Rápidas</h3>
                    <div class="acciones-grid">
                        <a href="nuevaventa.html">
                            <div class="accion-card">
                                <div class="accion-icono"><i class="fas fa-cash-register"></i></div>
                                <div class="accion-titulo">Nueva Venta</div>
                                <div class="accion-descripcion">Registrar venta al público</div>
                            </div>
                        </a>

                        <a href="#">
                            <div class="accion-card">
                                <div class="accion-icono"><i class="fas fa-boxes"></i></div>
                                <div class="accion-titulo">Consultar Stock</div>
                                <div class="accion-descripcion">Ver disponibilidad productos</div>
                            </div>
                        </a>
                        
                        <a href="#">
                            <div class="accion-card" onclick="parent.document.getElementById('contentFrame').src='boletas-facturas.html'">
                                <div class="accion-icono"><i class="fas fa-receipt"></i></div>
                                <div class="accion-titulo">Boletas-Facturas</div>
                                <div class="accion-descripcion">Crear boletas-facturas</div>
                            </div>
                        </a>

                        <a href="#">
                            <div class="accion-card" onclick="parent.document.getElementById('contentFrame').src='registrar-devolucion.html'">
                                <div class="accion-icono"><i class="fas fa-undo-alt"></i></div>
                                <div class="accion-titulo">Registrar Devolución</div>
                                <div class="accion-descripcion">Procesar devoluciones</div>
                            </div>
                        </a>
                    </div>
                </section>

                <section class="ventas-recientes">
                    <h3><i class="fas fa-history"></i> Ventas Recientes</h3>
                    <div class="ventas-lista">
                        <div class="venta-item">
                            <div class="venta-info">
                                <span class="venta-id">#00125</span>
                                <span class="venta-productos">Coca Cola, Galletas Oreo</span>
                            </div>
                            <div class="venta-total">$25.50</div>
                        </div>
                        <div class="venta-item">
                            <div class="venta-info">
                                <span class="venta-id">#00124</span>
                                <span class="venta-productos">Arroz, Aceite, Leche</span>
                            </div>
                            <div class="venta-total">$42.80</div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</body>
</html>