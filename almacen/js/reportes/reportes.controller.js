import ReportesService from './reportes.service.js';

class ReportesController {
    constructor() {
        this.service = new ReportesService();
        this.movimientos = [];
        this.productosVendidos = [];
        this.productos = [];
        this.filtrosActivos = {
            fechaInicio: '',
            fechaFin: '',
            producto: '',
            movimiento: ''
        };
        
        this.init();
    }

    async init() {
        try {
            await this.cargarDatosIniciales();
            this.configurarEventos();
            this.configurarInterfazUsuario();
            this.configurarFechasPorDefecto();
            console.log('Controlador de reportes inicializado');
        } catch (error) {
            console.error('Error al inicializar controlador:', error);
            this.mostrarError('Error al cargar los datos iniciales');
        }
    }

    async cargarDatosIniciales() {
        try {
            // Cargar productos para filtros
            this.productos = await this.service.obtenerProductos();
            this.actualizarSelectProductos();
            
            // Cargar datos iniciales con filtros por defecto
            await this.cargarDatosConFiltros();
            
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
            throw error;
        }
    }

    configurarEventos() {
        // Filtros
        document.getElementById('fechaInicio').addEventListener('change', () => this.aplicarFiltros());
        document.getElementById('fechaFin').addEventListener('change', () => this.aplicarFiltros());
        document.getElementById('filtroProducto').addEventListener('change', () => this.aplicarFiltros());
        document.getElementById('filtroMovimiento').addEventListener('change', () => this.aplicarFiltros());
        document.getElementById('btnLimpiarFiltros').addEventListener('click', () => this.limpiarFiltros());
        
        // Botones de acción
        document.getElementById('btnExportar').addEventListener('click', () => this.exportarReporte());
        document.getElementById('btnImprimir').addEventListener('click', () => this.imprimirReporte());
        
        // Pestañas
        document.querySelectorAll('#reportesTabs .nav-link').forEach(tab => {
            tab.addEventListener('click', (e) => this.cambiarPestaña(e.target));
        });
        
        // Configuración de interfaz
        this.configurarDropdownUsuario();
    }

    configurarInterfazUsuario() {
        // Inicializar avatar y nombre de usuario (simulado)
        const usuarioAvatar = document.getElementById('usuarioAvatar');
        const userName = document.getElementById('userName');
        
        // Esto debería venir de un servicio de autenticación
        usuarioAvatar.textContent = 'EU'; // Ejemplo: Encargado Usuario
        userName.textContent = 'Encargado Usuario';
    }

    configurarDropdownUsuario() {
        const dropdownBtn = document.getElementById("dropdownBtn");
        const dropdownList = document.getElementById("dropdownList");
        const arrow = document.getElementById("arrow");

        dropdownBtn.addEventListener("click", () => {
            const isVisible = dropdownList.style.display === "block";
            dropdownList.style.display = isVisible ? "none" : "block";
            arrow.style.transform = isVisible ? "rotate(0deg)" : "rotate(180deg)";
        });
        
        document.addEventListener("click", (e) => {
            if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
                dropdownList.style.display = "none";
                arrow.style.transform = "rotate(0deg)";
            }
        });
    }

    configurarFechasPorDefecto() {
        const hoy = new Date();
        const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        
        const fechaInicio = document.getElementById('fechaInicio');
        const fechaFin = document.getElementById('fechaFin');
        
        fechaInicio.value = this.formatearFechaInput(primerDiaMes);
        fechaFin.value = this.formatearFechaInput(hoy);
        
        this.filtrosActivos.fechaInicio = fechaInicio.value;
        this.filtrosActivos.fechaFin = fechaFin.value;
    }

    formatearFechaInput(fecha) {
        return fecha.toISOString().split('T')[0];
    }

    actualizarSelectProductos() {
        const selectProducto = document.getElementById('filtroProducto');
        
        selectProducto.innerHTML = '<option value="">Todos los productos</option>' +
            this.productos.map(prod => 
                `<option value="${prod.cod_producto}">${prod.nombre}</option>`
            ).join('');
    }

    async cargarDatosConFiltros() {
        try {
            // Actualizar filtros activos
            this.actualizarFiltrosDesdeFormulario();
            
            // Cargar datos en paralelo
            const [movimientos, productosVendidos] = await Promise.all([
                this.service.obtenerMovimientos(this.filtrosActivos),
                this.service.obtenerProductosVendidos(this.filtrosActivos)
            ]);

            this.movimientos = movimientos;
            this.productosVendidos = productosVendidos;

            this.actualizarTablaMovimientos();
            this.actualizarTablaProductosVendidos();
            this.actualizarContadores();
            this.actualizarRangosFechas();
            this.actualizarFiltrosActivos();

        } catch (error) {
            console.error('Error cargando datos con filtros:', error);
            throw error;
        }
    }

    actualizarFiltrosDesdeFormulario() {
        this.filtrosActivos = {
            fechaInicio: document.getElementById('fechaInicio').value,
            fechaFin: document.getElementById('fechaFin').value,
            producto: document.getElementById('filtroProducto').value,
            movimiento: document.getElementById('filtroMovimiento').value
        };
    }

    async aplicarFiltros() {
        await this.cargarDatosConFiltros();
    }

    async limpiarFiltros() {
        document.getElementById('fechaInicio').value = '';
        document.getElementById('fechaFin').value = '';
        document.getElementById('filtroProducto').value = '';
        document.getElementById('filtroMovimiento').value = '';
        
        await this.cargarDatosConFiltros();
    }

    actualizarTablaMovimientos() {
        const tbody = document.getElementById('tablaMovimientos');
        
        if (this.movimientos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan='6' class='text-center py-4'>
                        <i class='fas fa-info-circle fa-2x text-muted mb-2'></i>
                        <p class='mb-0 text-muted'>No hay movimientos registrados con los filtros aplicados</p>
                        <small class='text-muted'>Intenta cambiar los criterios de búsqueda</small>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.movimientos.map(movimiento => {
            const badgeClass = this.service.getMovimientoBadgeClass(movimiento.cod_tipomovimiento);
            const stockBadgeClass = this.service.getStockBadgeClass(movimiento.stock);
            
            return `
                <tr>
                    <td>${this.service.formatearFecha(movimiento.fecha_movimiento)}</td>
                    <td><strong>${movimiento.producto_nombre}</strong></td>
                    <td><span class='badge ${badgeClass}'>${movimiento.tipo_movimiento}</span></td>
                    <td>${movimiento.observacion}</td>
                    <td>
                        <span class='badge ${stockBadgeClass}'>
                            ${movimiento.stock} und.
                        </span>
                    </td>
                    <td>${movimiento.usuario}</td>
                </tr>
            `;
        }).join('');
    }

    actualizarTablaProductosVendidos() {
        const tbody = document.getElementById('tablaProductosVendidos');
        
        if (this.productosVendidos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan='6' class='text-center py-4'>
                        <i class='fas fa-chart-line fa-2x text-muted mb-2'></i>
                        <p class='mb-0 text-muted'>No hay datos de ventas en el período seleccionado</p>
                        <small class='text-muted'>Intenta cambiar el rango de fechas</small>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.productosVendidos.map((venta, index) => {
            const stockBadgeClass = this.service.getStockBadgeClass(venta.stock);
            
            return `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${venta.producto_nombre}</strong></td>
                    <td>${venta.categoria_nombre}</td>
                    <td>${venta.unidades_vendidas} unidades</td>
                    <td>S/ ${parseFloat(venta.ingresos_totales).toFixed(2)}</td>
                    <td>
                        <span class='badge ${stockBadgeClass}'>
                            ${venta.stock} und.
                        </span>
                    </td>
                </tr>
            `;
        }).join('');
    }

    actualizarContadores() {
        document.getElementById('badgeMovimientos').textContent = this.movimientos.length;
        document.getElementById('badgeProductosVendidos').textContent = this.productosVendidos.length;
    }

    actualizarRangosFechas() {
        const rangoMovimientos = document.getElementById('rangoFechasMovimientos');
        const rangoVentas = document.getElementById('rangoFechasVentas');
        
        if (this.filtrosActivos.fechaInicio && this.filtrosActivos.fechaFin) {
            const fechaInicio = this.service.formatearFecha(this.filtrosActivos.fechaInicio);
            const fechaFin = this.service.formatearFecha(this.filtrosActivos.fechaFin);
            
            rangoMovimientos.textContent = `- ${fechaInicio} al ${fechaFin}`;
            rangoVentas.textContent = `- ${fechaInicio} al ${fechaFin}`;
        } else {
            rangoMovimientos.textContent = '';
            rangoVentas.textContent = '';
        }
    }

    actualizarFiltrosActivos() {
        const filtrosActivosDiv = document.getElementById('filtrosActivos');
        const badgesContainer = document.getElementById('badgesFiltrosActivos');
        
        const filtros = [];
        
        if (this.filtrosActivos.fechaInicio && this.filtrosActivos.fechaFin) {
            const fechaInicio = this.service.formatearFecha(this.filtrosActivos.fechaInicio);
            const fechaFin = this.service.formatearFecha(this.filtrosActivos.fechaFin);
            
            filtros.push(`
                <span class="badge bg-primary">
                    Fechas: ${fechaInicio} - ${fechaFin}
                    <a href="javascript:void(0)" class="text-white ms-1" onclick="reportesController.removerFiltro('fecha')">×</a>
                </span>
            `);
        }
        
        if (this.filtrosActivos.producto) {
            const producto = this.productos.find(p => p.cod_producto === this.filtrosActivos.producto);
            const productoNombre = producto ? producto.nombre : this.filtrosActivos.producto;
            filtros.push(`
                <span class="badge bg-success">
                    Producto: ${productoNombre}
                    <a href="javascript:void(0)" class="text-white ms-1" onclick="reportesController.removerFiltro('producto')">×</a>
                </span>
            `);
        }
        
        if (this.filtrosActivos.movimiento) {
            const movimientos = {
                'entrada': 'Entradas', 
                'salida': 'Salidas', 
                'ajuste': 'Ajustes'
            };
            const movimientoTexto = movimientos[this.filtrosActivos.movimiento] || this.filtrosActivos.movimiento;
            
            filtros.push(`
                <span class="badge bg-info">
                    Movimiento: ${movimientoTexto}
                    <a href="javascript:void(0)" class="text-white ms-1" onclick="reportesController.removerFiltro('movimiento')">×</a>
                </span>
            `);
        }
        
        if (filtros.length > 0) {
            badgesContainer.innerHTML = filtros.join('');
            filtrosActivosDiv.style.display = 'block';
        } else {
            filtrosActivosDiv.style.display = 'none';
        }
    }

    removerFiltro(tipo) {
        if (tipo === 'fecha') {
            document.getElementById('fechaInicio').value = '';
            document.getElementById('fechaFin').value = '';
        } else if (tipo === 'producto') {
            document.getElementById('filtroProducto').value = '';
        } else if (tipo === 'movimiento') {
            document.getElementById('filtroMovimiento').value = '';
        }
        
        this.aplicarFiltros();
    }

    cambiarPestaña(tabElement) {
        // Remover clase active de todas las pestañas
        document.querySelectorAll('#reportesTabs .nav-link').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Agregar clase active a la pestaña clickeada
        tabElement.classList.add('active');
    }

    async exportarReporte() {
        try {
            this.mostrarExito('Exportando reporte a Excel...');
            
            // Simular proceso de exportación
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            this.mostrarExito('Reporte exportado correctamente');
            
        } catch (error) {
            console.error('Error exportando reporte:', error);
            this.mostrarError('Error al exportar el reporte');
        }
    }

    imprimirReporte() {
        this.mostrarExito('Preparando para impresión...');
        window.print();
    }

    mostrarExito(mensaje) {
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: mensaje,
            width: '350px',
            timer: 2000,
            showConfirmButton: false
        });
    }

    mostrarError(mensaje) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: mensaje,
            width: '350px'
        });
    }
}

// Inicializar la aplicación cuando el DOM esté listo
let reportesController;

document.addEventListener('DOMContentLoaded', () => {
    reportesController = new ReportesController();
});