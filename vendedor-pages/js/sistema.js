// ===== SISTEMA PRINCIPAL CON CARGA DINÁMICA =====

class SistemaMadMarket {
    constructor() {
        this.paginaActual = 'dashboard';
        this.usuarioActual = null;
        this.turnoActual = null;
        this.init();
    }

    async init() {
        await this.inicializarUsuario();
        await this.inicializarTurno();
        this.configurarEventListeners();
        await this.cargarPagina('dashboard');
        this.iniciarActualizacionesTiempoReal();
    }

    // Inicializar usuario automáticamente
    async inicializarUsuario() {
        this.usuarioActual = {
            id: 1,
            nombre: "María López",
            rol: "Cajero",
            iniciales: "ML",
            turno: "08:00 - 16:00"
        };
        
        this.actualizarUIUsuario();
    }

    // Inicializar turno automáticamente
    async inicializarTurno() {
        let turno = MadUtils.loadData('currentTurno');
        
        if (!turno) {
            turno = {
                id: 'turno_' + Date.now(),
                usuarioId: this.usuarioActual.id,
                fechaInicio: new Date().toISOString(),
                estado: 'activo',
                ventas: []
            };
            MadUtils.saveData('currentTurno', turno);
        }
        
        this.turnoActual = turno;
        this.actualizarTiempoActivo();
    }

    // Configurar event listeners
    configurarEventListeners() {
        // Navegación del sidebar
        document.querySelectorAll('.nav-link[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const pagina = e.currentTarget.getAttribute('data-page');
                this.cambiarPagina(pagina);
            });
        });

        // Menú móvil
        document.getElementById('mobileMenuBtn').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        });

        // Búsqueda global
        const searchInput = document.getElementById('globalSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.manejarBusquedaGlobal(e.target.value);
            });
        }

        // Cerrar sidebar al hacer clic fuera en móvil
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 992) {
                const sidebar = document.getElementById('sidebar');
                const mobileBtn = document.getElementById('mobileMenuBtn');
                if (!sidebar.contains(e.target) && !mobileBtn.contains(e.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });
    }

    // Cambiar de página
    async cambiarPagina(pagina) {
        // Actualizar navegación activa
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        document.querySelector(`.nav-link[data-page="${pagina}"]`).classList.add('active');
        
        // Actualizar título de página
        this.actualizarTituloPagina(pagina);
        
        // Actualizar placeholder de búsqueda
        this.actualizarBusquedaGlobal(pagina);
        
        // Cargar contenido de la página
        await this.cargarPagina(pagina);
        
        // Cerrar sidebar en móvil
        if (window.innerWidth < 992) {
            document.getElementById('sidebar').classList.remove('mobile-open');
        }
    }

    // Actualizar título de página
    actualizarTituloPagina(pagina) {
        const titulos = {
            'dashboard': 'Dashboard',
            'ventas': 'Punto de Venta',
            'stock': 'Consultar Stock',
            'reposicion': 'Solicitar Reposición',
            'devoluciones': 'Registrar Devolución',
            'documentos': 'Boletas y Facturas'
        };
        
        document.getElementById('pageTitle').textContent = titulos[pagina] || 'MAD MARKET';
    }

    // Actualizar búsqueda global según la página
    actualizarBusquedaGlobal(pagina) {
        const placeholders = {
            'dashboard': 'Buscar productos, ventas...',
            'ventas': 'Buscar productos por código o nombre...',
            'stock': 'Buscar productos en stock...',
            'reposicion': 'Buscar productos para reposición...',
            'devoluciones': 'Buscar ventas o productos...',
            'documentos': 'Buscar documentos por número...'
        };
        
        const searchInput = document.getElementById('globalSearch');
        if (searchInput) {
            searchInput.setAttribute('data-page', pagina);
            searchInput.placeholder = placeholders[pagina] || 'Buscar...';
        }
    }

    // Cargar contenido de página
    async cargarPagina(pagina) {
        const contentArea = document.getElementById('content-area');
        
        // Mostrar loading
        contentArea.innerHTML = `
            <div class="loading">
                <div class="spinner"></div>
                <p>Cargando ${this.obtenerNombrePagina(pagina)}...</p>
            </div>
        `;

        try {
            // Simular carga
            await MadUtils.delay(500);
            
            // Cargar contenido según la página
            const contenido = await this.generarContenidoPagina(pagina);
            contentArea.innerHTML = contenido;
            
            // Inicializar funcionalidades específicas de la página
            await this.inicializarPagina(pagina);
            
        } catch (error) {
            contentArea.innerHTML = `
                <div class="alert alert-danger">
                    <h4><i class="fas fa-exclamation-triangle"></i> Error al cargar la página</h4>
                    <p>${error.message}</p>
                    <button class="btn btn-primary" onclick="sistema.cargarPagina('dashboard')">
                        <i class="fas fa-arrow-left"></i> Volver al Dashboard
                    </button>
                </div>
            `;
        }
    }

    // Obtener nombre legible de la página
    obtenerNombrePagina(pagina) {
        const nombres = {
            'dashboard': 'Dashboard',
            'ventas': 'Punto de Venta',
            'stock': 'Consultar Stock',
            'reposicion': 'Solicitar Reposición',
            'devoluciones': 'Registrar Devolución',
            'documentos': 'Boletas y Facturas'
        };
        return nombres[pagina] || pagina;
    }

    // Generar contenido HTML para cada página
    async generarContenidoPagina(pagina) {
        const contenidos = {
            'dashboard': await this.generarDashboard(),
            'ventas': await this.generarVentas(),
            'stock': await this.generarStock(),
            'reposicion': await this.generarReposicion(),
            'devoluciones': await this.generarDevoluciones(),
            'documentos': await this.generarDocumentos()
        };
        
        return contenidos[pagina] || '<p>Página no encontrada</p>';
    }

    // Generar contenido del Dashboard
    async generarDashboard() {
        const stats = await this.obtenerEstadisticas();
        
        return `
            <section class="info-turno">
                <div class="turno-card">
                    <h3>📊 Resumen Turno Actual</h3>
                    <div class="turno-stats">
                        <div class="stat">
                            <span class="stat-value">${stats.ventasHoy}</span>
                            <span class="stat-label">Ventas Hoy</span>
                        </div>
                        <div class="stat">
                            <span class="stat-value">${MadUtils.formatCurrency(stats.totalVendido)}</span>
                            <span class="stat-label">Total Vendido</span>
                        </div>
                        <div class="stat">
                            <span class="stat-value" id="tiempoActivo">0h 0m</span>
                            <span class="stat-label">Tiempo Activo</span>
                        </div>
                        <div class="stat">
                            <span class="stat-value">${stats.alertasActivas}</span>
                            <span class="stat-label">Alertas Activas</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="acciones-principales">
                <h3>⚡ Acciones Rápidas</h3>
                <div class="acciones-grid">
                    <a href="#" class="accion-card accion-venta" onclick="sistema.cambiarPagina('ventas')">
                        <div class="accion-icon"><i class="fas fa-cash-register"></i></div>
                        <div class="accion-title">Nueva Venta</div>
                        <div class="accion-desc">Registrar venta al público</div>
                    </a>

                    <a href="#" class="accion-card accion-stock" onclick="sistema.cambiarPagina('stock')">
                        <div class="accion-icon"><i class="fas fa-boxes"></i></div>
                        <div class="accion-title">Consultar Stock</div>
                        <div class="accion-desc">Ver disponibilidad productos</div>
                    </a>

                    <a href="#" class="accion-card accion-reposicion" onclick="sistema.cambiarPagina('reposicion')">
                        <div class="accion-icon"><i class="fas fa-truck-loading"></i></div>
                        <div class="accion-title">Solicitar Reposición</div>
                        <div class="accion-desc">Pedir productos al almacén</div>
                    </a>

                    <a href="#" class="accion-card accion-devolucion" onclick="sistema.cambiarPagina('devoluciones')">
                        <div class="accion-icon"><i class="fas fa-undo-alt"></i></div>
                        <div class="accion-title">Registrar Devolución</div>
                        <div class="accion-desc">Procesar devoluciones</div>
                    </a>
                </div>
            </section>

            <section class="alertas-urgentes" id="seccionAlertas">
                <h3><i class="fas fa-bell"></i> Alertas Importantes</h3>
                <div class="alertas-list" id="listaAlertas">
                    ${await this.generarAlertas()}
                </div>
            </section>

            <section class="ventas-recientes">
                <h3><i class="fas fa-receipt"></i> Ventas Recientes</h3>
                <div class="ventas-list" id="listaVentas">
                    ${await this.generarVentasRecientes()}
                </div>
            </section>
        `;
    }

    // Generar contenido de Ventas (Punto de Venta)
    async generarVentas() {
        return `
            <div class="ventas-main">
                <div class="panel-busqueda">
                    <div class="busqueda-header">
                        <h3><i class="fas fa-search"></i> Buscar Producto</h3>
                        <button class="btn btn-primary" onclick="ventasManager.simularScanner()">
                            <i class="fas fa-barcode"></i> Usar Scanner
                        </button>
                    </div>
                    
                    <div class="busqueda-input">
                        <input type="text" id="inputBusqueda" placeholder="Código de barras, nombre o código..." autofocus>
                        <button class="btn btn-secondary" onclick="ventasManager.buscarProductos()">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                    
                    <div class="resultados-busqueda" id="resultadosBusqueda"></div>
                    
                    <div class="productos-frecuentes">
                        <h4><i class="fas fa-star"></i> Productos Más Vendidos</h4>
                        <div class="frecuentes-grid" id="gridFrecuentes"></div>
                    </div>
                </div>

                <div class="panel-venta">
                    <div class="venta-header">
                        <h3><i class="fas fa-shopping-cart"></i> Venta Actual</h3>
                        <button class="btn btn-secondary" onclick="ventasManager.limpiarVenta()">
                            <i class="fas fa-trash"></i> Limpiar Todo
                        </button>
                    </div>
                    
                    <div class="lista-productos" id="listaProductosVenta">
                        <div class="empty-state">
                            <p>No hay productos agregados</p>
                            <small>Busca y agrega productos para comenzar</small>
                        </div>
                    </div>
                    
                    <div class="resumen-venta">
                        <div class="resumen-linea">
                            <span>Subtotal:</span>
                            <span id="subtotal">$0.00</span>
                        </div>
                        <div class="resumen-linea">
                            <span>IGV (18%):</span>
                            <span id="igv">$0.00</span>
                        </div>
                        <div class="resumen-linea total">
                            <span>TOTAL:</span>
                            <span id="totalVenta">$0.00</span>
                        </div>

                        <div class="metodos-pago">
                            <h4><i class="fas fa-credit-card"></i> Método de Pago</h4>
                            <div class="metodos-grid">
                                <button class="metodo-btn active" data-metodo="efectivo">
                                    <i class="fas fa-money-bill-wave"></i> Efectivo
                                </button>
                                <button class="metodo-btn" data-metodo="tarjeta">
                                    <i class="fas fa-credit-card"></i> Tarjeta
                                </button>
                                <button class="metodo-btn" data-metodo="transferencia">
                                    <i class="fas fa-mobile-alt"></i> Transferencia
                                </button>
                            </div>

                            <div class="monto-efectivo" id="montoEfectivo">
                                <label>Efectivo recibido:</label>
                                <input type="number" id="inputEfectivo" placeholder="0.00" step="0.01">
                                <div class="cambio-info">
                                    Cambio: <span id="cambio">$0.00</span>
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-success btn-large" id="btnFinalizar" disabled>
                            <i class="fas fa-check"></i> FINALIZAR VENTA
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Generar contenido de Stock
    async generarStock() {
        return `
            <div class="stock-main">
                <div class="filtros-stock">
                    <div class="filtro-group">
                        <input type="text" id="busquedaStock" placeholder="Buscar producto..." class="input-busqueda">
                        <select id="filtroCategoria" class="select-filtro">
                            <option value="">Todas las categorías</option>
                        </select>
                        <select id="filtroEstado" class="select-filtro">
                            <option value="">Todos los estados</option>
                            <option value="bajo">Stock Bajo</option>
                            <option value="normal">Stock Normal</option>
                        </select>
                        <button class="btn btn-primary" id="btnExportar">
                            <i class="fas fa-file-export"></i> Exportar
                        </button>
                    </div>
                </div>

                <div class="tabla-stock">
                    <div class="table-container">
                        <table class="stock-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Precio</th>
                                    <th>Stock Actual</th>
                                    <th>Stock Mínimo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyStock">
                                ${await this.generarFilasStock()}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    // ... (continuaría con los demás métodos para generar contenido)

    // Manejar búsqueda global
    manejarBusquedaGlobal(termino) {
        if (termino.length > 2) {
            console.log('Buscando:', termino, 'en página:', this.paginaActual);
            // Implementar búsqueda según la página actual
        }
    }

    // Actualizar UI del usuario
    actualizarUIUsuario() {
        document.getElementById('userName').textContent = this.usuarioActual.nombre;
        document.getElementById('userPosition').textContent = `${this.usuarioActual.rol} - Turno Activo`;
        document.getElementById('userAvatar').textContent = this.usuarioActual.iniciales;
        document.getElementById('sidebarUserName').textContent = this.usuarioActual.nombre;
        document.getElementById('sidebarUserRole').textContent = `${this.usuarioActual.rol} - Turno Activo`;
    }

    // Actualizar tiempo activo
    actualizarTiempoActivo() {
        if (this.turnoActual && this.turnoActual.fechaInicio) {
            const tiempo = MadUtils.timeDiff(this.turnoActual.fechaInicio);
            document.getElementById('tiempoActivoSidebar').textContent = tiempo + ' activo';
            
            const tiempoActivoElement = document.getElementById('tiempoActivo');
            if (tiempoActivoElement) {
                tiempoActivoElement.textContent = tiempo;
            }
        }
    }

    // Cerrar turno
    cerrarTurno() {
        if (confirm('¿Estás seguro de que deseas cerrar el turno?')) {
            MadUtils.showNotification('Turno cerrado correctamente', 'info');
            localStorage.removeItem('mad_currentTurno');
            setTimeout(() => location.reload(), 1000);
        }
    }

    // Iniciar actualizaciones en tiempo real
    iniciarActualizacionesTiempoReal() {
        setInterval(() => {
            this.actualizarTiempoActivo();
            this.actualizarEstadoSistema();
        }, 60000); // Actualizar cada minuto
    }

    // Actualizar estado del sistema
    actualizarEstadoSistema() {
        document.getElementById('estadoConexion').innerHTML = '<i class="fas fa-circle"></i> En línea';
        document.getElementById('ultimaSincronizacion').textContent = 
            `Última sincronización: ${new Date().toLocaleTimeString('es-PE')}`;
    }

    // Obtener estadísticas para el dashboard
    async obtenerEstadisticas() {
        const ventas = MadUtils.loadData('ventas') || [];
        const productos = MadUtils.loadData('productos') || [];
        
        const ventasHoy = ventas.filter(v => {
            const hoy = new Date().toDateString();
            const fechaVenta = new Date(v.fechaVenta).toDateString();
            return fechaVenta === hoy;
        }).length;

        const totalVendido = ventas.reduce((sum, v) => sum + (v.total || 0), 0);
        const alertasActivas = productos.filter(p => p.stock_tienda_unidades <= p.stock_minimo_tienda).length;

        return {
            ventasHoy,
            totalVendido,
            alertasActivas
        };
    }

    // Generar alertas para el dashboard
    async generarAlertas() {
        const productos = MadUtils.loadData('productos') || [];
        const alertas = productos.filter(p => p.stock_tienda_unidades <= p.stock_minimo_tienda);
        
        if (alertas.length === 0) {
            return '<div class="empty-state"><p>No hay alertas activas</p></div>';
        }

        return alertas.map(producto => `
            <div class="alerta-item ${producto.stock_tienda_unidades <= 5 ? 'critica' : ''}">
                <div class="alerta-texto">
                    <strong>${producto.nombre}</strong> - 
                    Stock bajo: ${producto.stock_tienda_unidades}/${producto.stock_minimo_tienda} unidades
                </div>
                <div class="alerta-accion">
                    <button class="btn btn-small btn-primary" onclick="sistema.cambiarPagina('reposicion')">
                        Solicitar
                    </button>
                </div>
            </div>
        `).join('');
    }

    // Generar ventas recientes para el dashboard
    async generarVentasRecientes() {
        const ventas = MadUtils.loadData('ventas') || [];
        const ventasRecientes = ventas.slice(-5).reverse();
        
        if (ventasRecientes.length === 0) {
            return '<div class="empty-state"><p>No hay ventas recientes</p><small>Las ventas aparecerán aquí</small></div>';
        }

        return ventasRecientes.map(venta => `
            <div class="venta-item">
                <div class="venta-info">
                    <span class="venta-id">#${venta.id_venta || venta.id}</span>
                    <span class="venta-productos">
                        ${venta.productos.slice(0, 2).map(p => p.nombre).join(', ')}
                        ${venta.productos.length > 2 ? `... (+${venta.productos.length - 2})` : ''}
                    </span>
                </div>
                <div class="venta-total">${MadUtils.formatCurrency(venta.total)}</div>
            </div>
        `).join('');
    }

    // Generar filas de stock
    async generarFilasStock() {
        const productos = MadUtils.loadData('productos') || [];
        
        if (productos.length === 0) {
            return '<tr><td colspan="6" class="text-center py-4"><div class="empty-state"><p>No hay productos</p></div></td></tr>';
        }

        return productos.map(producto => {
            const estado = producto.stock_tienda_unidades <= producto.stock_minimo_tienda ? 'bajo' : 'normal';
            const colorEstado = estado === 'bajo' ? 'stock-bajo' : 'stock-normal';
            
            return `
                <tr>
                    <td><strong>${producto.nombre}</strong></td>
                    <td>${producto.categoria || 'General'}</td>
                    <td>${MadUtils.formatCurrency(producto.precio_venta)}</td>
                    <td><span class="${colorEstado}">${producto.stock_tienda_unidades}</span></td>
                    <td>${producto.stock_minimo_tienda}</td>
                    <td>
                        <span class="estado-${estado}">${estado === 'bajo' ? '⚠️ Bajo' : '✅ Normal'}</span>
                    </td>
                </tr>
            `;
        }).join('');
    }
}

// ===== INICIALIZACIÓN DEL SISTEMA =====
document.addEventListener('DOMContentLoaded', function() {
    window.sistema = new SistemaMadMarket();
});

// ===== CLASES ESPECÍFICAS PARA MÓDULOS =====
class VentasManager {
    // ... (implementación del punto de venta)
}

class StockManager {
    // ... (implementación de gestión de stock)
}

// Exportar para uso global
window.VentasManager = VentasManager;
window.StockManager = StockManager;