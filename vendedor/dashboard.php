<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Dashboard Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/vendedor-estilo.css">
</head>
<body>
    <?php
    session_start();
    
    // Inicializar contadores si no existen
    if (!isset($_SESSION['ventas_hoy'])) {
        $_SESSION['ventas_hoy'] = 0;
        $_SESSION['total_vendido'] = 0;
        $_SESSION['inicio_turno'] = time();
    }
    
    // Calcular tiempo activo
    $tiempo_activo = time() - $_SESSION['inicio_turno'];
    $horas = floor($tiempo_activo / 3600);
    $minutos = floor(($tiempo_activo % 3600) / 60);
    ?>
    
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
                    <small id="tiempoActivoSidebar"><?php echo $horas . 'h ' . $minutos . 'm activo'; ?></small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.php" class="nav-link active"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="nuevaventa.php" class="nav-link"><ul><i class="fas fa-cash-register"></i>Nueva Venta</ul></a>
                    <a href="registrodevolucion.php" class="nav-link"><ul><i class="fas fa-undo-alt"></i>Registrar Devolución</ul></a>
                    <a href="boletas-facturas.php" class="nav-link"><ul><i class="fas fa-receipt"></i>Boletas/Facturas</ul></a>
                    <a href="consulta-stock.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Consulta-stock</ul></a>
                    <a href="../login.html" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
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
                    <button class="btn btn-sm btn-outline-warning ms-2" onclick="reiniciarEstadisticas()" title="Reiniciar contadores">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
            </div>

            <div class="contenedor-dashboard">
                <!-- RESUMEN DEL TURNO -->
                <section class="turno-actual">
                    <div class="turno-card">
                        <h3><i class="fas fa-chart-line"></i> Resumen Turno Actual</h3>
                        <div class="turno-stats">
                            <div class="stat">
                                <span class="stat-value" id="ventasHoy">0</span>
                                <span class="stat-label">Ventas Hoy</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value" id="totalVendido">S/ 0.00</span>
                                <span class="stat-label">Total Vendido</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value" id="productosVendidos">0</span>
                                <span class="stat-label">Productos Vendidos</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value" id="tiempoActivo"><?php echo $horas . 'h ' . $minutos . 'm'; ?></span>
                                <span class="stat-label">Tiempo Activo</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ACCIONES RÁPIDAS -->
                <section class="acciones-principales">
                    <h3><i class="fas fa-bolt"></i> Acciones Rápidas</h3>
                    <div class="acciones-grid">
                        <a href="nuevaventa.php">
                            <div class="accion-card">
                                <div class="accion-icono"><i class="fas fa-cash-register"></i></div>
                                <div class="accion-titulo">Nueva Venta</div>
                                <div class="accion-descripcion">Registrar venta al público</div>
                            </div>
                        </a>

                        <a href="boletas-facturas.php">
                            <div class="accion-card">
                                <div class="accion-icono"><i class="fas fa-receipt"></i></div>
                                <div class="accion-titulo">Boletas/Facturas</div>
                                <div class="accion-descripcion">Crear boletas y facturas</div>
                            </div>
                        </a>

                        <a href="registrodevolucion.php">
                            <div class="accion-card">
                                <div class="accion-icono"><i class="fas fa-undo-alt"></i></div>
                                <div class="accion-titulo">Registrar Devolución</div>
                                <div class="accion-descripcion">Procesar devoluciones</div>
                            </div>
                        </a>

                        <a href="consulta-stock.php">
                            <div class="accion-card">
                                <div class="accion-icono"><i class="fas fa-boxes"></i></div>
                                <div class="accion-titulo">Consultar Stock</div>
                                <div class="accion-descripcion">Verificar inventario</div>
                            </div>
                        </a>
                    </div>
                </section>

                <!-- VENTAS RECIENTES -->
                <section class="ventas-recientes">
                    <h3><i class="fas fa-history"></i> Ventas Recientes</h3>
                    <div class="ventas-lista" id="listaVentasRecientes">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                            <p>No hay ventas recientes</p>
                            <small class="text-muted">Las ventas aparecerán aquí automáticamente</small>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        // ===== SISTEMA DE ACTUALIZACIÓN DEL DASHBOARD =====
        
        // Cargar y mostrar estadísticas del turno desde localStorage
        function cargarEstadisticasTurno() {
            try {
                const estadisticasTurno = JSON.parse(localStorage.getItem('estadisticasTurno') || '{"ventasHoy": 0, "totalVendido": 0, "productosVendidos": 0}');
                
                console.log('📊 Cargando estadísticas:', estadisticasTurno);
                
                // Actualizar los contadores en el dashboard
                document.getElementById('ventasHoy').textContent = estadisticasTurno.ventasHoy || 0;
                document.getElementById('totalVendido').textContent = 'S/ ' + (estadisticasTurno.totalVendido || 0).toFixed(2);
                document.getElementById('productosVendidos').textContent = estadisticasTurno.productosVendidos || 0;
                
                // Actualizar última venta si existe
                if (estadisticasTurno.ultimaVenta) {
                    console.log('🕒 Última venta:', estadisticasTurno.ultimaVenta);
                }
                
            } catch (error) {
                console.error('❌ Error al cargar estadísticas:', error);
                // Inicializar valores por defecto
                document.getElementById('ventasHoy').textContent = '0';
                document.getElementById('totalVendido').textContent = 'S/ 0.00';
                document.getElementById('productosVendidos').textContent = '0';
            }
        }
        
        // Cargar ventas recientes desde localStorage
        function cargarVentasRecientes() {
            try {
                const ventasRecientes = JSON.parse(localStorage.getItem('ventasRecientes') || '[]');
                const lista = document.getElementById('listaVentasRecientes');
                
                console.log('📋 Cargando ventas recientes:', ventasRecientes);
                
                if (ventasRecientes.length === 0) {
                    lista.innerHTML = `
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                            <p>No hay ventas recientes</p>
                            <small class="text-muted">Las ventas aparecerán aquí automáticamente</small>
                        </div>
                    `;
                    return;
                }
                
                let html = '';
                ventasRecientes.slice(0, 5).forEach(venta => {
                    // Formatear fecha de manera más legible
                    const fecha = new Date(venta.fecha);
                    const fechaFormateada = fecha.toLocaleDateString('es-PE', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    // Determinar icono según método de pago
                    const iconoPago = venta.metodo_pago === 'EFE' ? 'money-bill-wave' : 
                                    venta.metodo_pago === 'TAR' ? 'credit-card' : 'mobile-alt';
                    
                    html += `
                        <div class="venta-item">
                            <div class="venta-info">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <span class="venta-id badge bg-primary">#${venta.id}</span>
                                    <small class="text-muted">${fechaFormateada}</small>
                                </div>
                                <div class="venta-productos text-truncate mb-1">${venta.productos}</div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-${iconoPago} me-1"></i>
                                        ${venta.metodo_pago === 'EFE' ? 'Efectivo' : 
                                          venta.metodo_pago === 'TAR' ? 'Tarjeta' : 'Transferencia'}
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-box me-1"></i>
                                        ${venta.cantidad_productos || 0} unidades
                                    </small>
                                </div>
                            </div>
                            <div class="venta-total text-success fw-bold fs-5">S/ ${parseFloat(venta.total).toFixed(2)}</div>
                        </div>
                    `;
                });
                
                lista.innerHTML = html;
                
            } catch (error) {
                console.error('❌ Error al cargar ventas recientes:', error);
                const lista = document.getElementById('listaVentasRecientes');
                lista.innerHTML = `
                    <div class="text-center text-danger py-3">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Error al cargar ventas</p>
                    </div>
                `;
            }
        }
        
        // Actualizar tiempo en tiempo real
        function actualizarTiempo() {
            const inicioTurno = <?php echo $_SESSION['inicio_turno']; ?>;
            const ahora = Math.floor(Date.now() / 1000);
            const diferencia = ahora - inicioTurno;
            const horas = Math.floor(diferencia / 3600);
            const minutos = Math.floor((diferencia % 3600) / 60);
            
            document.getElementById('tiempoActivo').textContent = horas + 'h ' + minutos + 'm';
            document.getElementById('tiempoActivoSidebar').textContent = horas + 'h ' + minutos + 'm activo';
        }
        
        // Escuchar actualizaciones en tiempo real desde otras pestañas
        function configurarActualizacionesTiempoReal() {
            // Método 1: Usar BroadcastChannel (más eficiente)
            if (typeof BroadcastChannel !== 'undefined') {
                try {
                    const channel = new BroadcastChannel('dashboard_updates');
                    channel.addEventListener('message', function(event) {
                        if (event.data.type === 'venta_registrada') {
                            console.log('🔄 Actualización recibida via BroadcastChannel:', event.data);
                            cargarEstadisticasTurno();
                            cargarVentasRecientes();
                        }
                    });
                    console.log('📡 BroadcastChannel configurado');
                } catch (e) {
                    console.log('❌ BroadcastChannel no disponible');
                }
            }
            
            // Método 2: Verificar cambios en localStorage cada 2 segundos (fallback)
            setInterval(function() {
                cargarEstadisticasTurno();
                cargarVentasRecientes();
            }, 2000);
            
            console.log('🔄 Sistema de actualización configurado');
        }
        
        // Función para reiniciar estadísticas
        function reiniciarEstadisticas() {
            if(confirm('¿Estás seguro de que deseas reiniciar los contadores de ventas? Esto no afecta las ventas ya registradas.')) {
                const estadisticasReset = {
                    ventasHoy: 0,
                    totalVendido: 0,
                    productosVendidos: 0,
                    ultimaActualizacion: new Date().toISOString()
                };
                localStorage.setItem('estadisticasTurno', JSON.stringify(estadisticasReset));
                cargarEstadisticasTurno();
                alert('✅ Contadores reiniciados correctamente');
            }
        }
        
        // Función para cerrar turno
        function cerrarTurno() {
            if(confirm('¿Estás seguro de que deseas cerrar el turno? Se reiniciarán las estadísticas.')) {
                // Limpiar localStorage
                localStorage.removeItem('ventasRecientes');
                localStorage.removeItem('estadisticasTurno');
                
                // Redirigir para reiniciar sesión
                window.location.href = 'cerrar_turno.php';
            }
        }
        
        // Función para buscar globalmente
        function configurarBusquedaGlobal() {
            const busquedaInput = document.getElementById('globalSearch');
            busquedaInput.addEventListener('input', function() {
                const termino = this.value.toLowerCase();
                // Aquí puedes implementar búsqueda global
                console.log('Buscando:', termino);
            });
        }
        
        // Inicializar dashboard
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Inicializando dashboard...');
            
            // Cargar datos iniciales
            cargarEstadisticasTurno();
            cargarVentasRecientes();
            
            // Configurar actualizaciones en tiempo real
            configurarActualizacionesTiempoReal();
            
            // Configurar búsqueda global
            configurarBusquedaGlobal();
            
            // Actualizar tiempo cada minuto
            setInterval(actualizarTiempo, 60000);
            
            // Mostrar estado inicial
            console.log('✅ Dashboard inicializado correctamente');
            
            // Forzar una actualización inicial
            setTimeout(() => {
                cargarEstadisticasTurno();
                cargarVentasRecientes();
            }, 1000);
        });

        // También escuchar eventos de almacenamiento (para actualizaciones entre pestañas)
        window.addEventListener('storage', function(event) {
            if (event.key === 'estadisticasTurno' || event.key === 'ventasRecientes') {
                console.log('🔄 Evento de almacenamiento detectado:', event.key);
                cargarEstadisticasTurno();
                cargarVentasRecientes();
            }
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>