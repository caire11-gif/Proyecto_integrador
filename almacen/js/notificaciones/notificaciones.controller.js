import NotificacionesService from './notificaciones.service.js';

class NotificacionesController {
    constructor() {
        this.service = new NotificacionesService();
        this.alertas = [];
        this.proveedores = [];
        this.filtrosActivos = {
            estado: '',
            proveedor: ''
        };
        
        this.init();
    }

    async init() {
        try {
            await this.cargarDatosIniciales();
            this.configurarEventos();
            this.configurarInterfazUsuario();
            console.log('Controlador de notificaciones inicializado');
        } catch (error) {
            console.error('Error al inicializar controlador:', error);
            this.mostrarError('Error al cargar los datos iniciales');
        }
    }

    async cargarDatosIniciales() {
        try {
            // Cargar proveedores y alertas en paralelo
            const [proveedores, alertas] = await Promise.all([
                this.service.obtenerProveedores(),
                this.service.obtenerAlertas(this.filtrosActivos)
            ]);

            this.proveedores = proveedores;
            this.alertas = alertas;

            this.actualizarSelectProveedores();
            this.actualizarListaAlertas();
            this.actualizarResumen();
            this.actualizarFiltrosActivos();
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
            throw error;
        }
    }

    configurarEventos() {
        // Filtros
        document.getElementById('btnAplicarFiltros').addEventListener('click', () => this.aplicarFiltros());
        document.getElementById('btnLimpiarFiltros').addEventListener('click', () => this.limpiarFiltros());
        
        // Actualización
        document.getElementById('btnActualizarAlertas').addEventListener('click', () => this.actualizarAlertas());
        document.getElementById('btnActualizarAlertasRapido').addEventListener('click', () => this.actualizarAlertas());
        
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

    actualizarSelectProveedores() {
        const selectProveedor = document.getElementById('filtroProveedor');
        
        selectProveedor.innerHTML = '<option value="">Todos los proveedores</option>' +
            this.proveedores.map(prov => 
                `<option value="${prov.cod_proveedor}">${prov.nombre}</option>`
            ).join('');
    }

    actualizarListaAlertas() {
        const listaAlertas = document.getElementById('listaAlertas');
        const totalAlertas = document.getElementById('totalAlertas');
        const totalAlertasBadge = document.getElementById('totalAlertasBadge');
        
        totalAlertas.textContent = this.alertas.length;
        totalAlertasBadge.textContent = `${this.alertas.length} Alertas`;

        if (this.alertas.length === 0) {
            listaAlertas.innerHTML = `
                <div class='list-group-item text-center py-4'>
                    <i class='fas fa-check-circle text-success fa-2x mb-2'></i>
                    <p class='mb-0 text-muted'>No hay alertas de stock bajo en este momento</p>
                    <small class='text-muted'>El sistema genera alertas automáticas cuando el stock es menor a 20 unidades</small>
                </div>
            `;
            return;
        }

        listaAlertas.innerHTML = this.alertas.map(alerta => {
            const estadoBadge = alerta.cod_estadonotificacion === 'en001' ? 
                { class: 'bg-primary', text: 'PENDIENTE' } : 
                { class: 'bg-secondary', text: 'LEÍDA' };
                
            const priorityInfo = this.service.getPriorityInfo(alerta.mensaje);
            
            return `
                <div class='list-group-item'>
                    <div class='d-flex justify-content-between align-items-start'>
                        <div class='flex-grow-1'>
                            <div class='d-flex justify-content-between align-items-center mb-2'>
                                <h6 class='mb-0'>${alerta.producto_nombre}</h6>
                                <div>
                                    <span class='badge ${priorityInfo.class} me-2'>${priorityInfo.text}</span>
                                    <span class='badge ${estadoBadge.class}'>${estadoBadge.text}</span>
                                </div>
                            </div>
                            <p class='mb-2'>${alerta.mensaje}</p>
                            <small class='text-muted'>
                                <i class='fas fa-warehouse me-1'></i>Stock actual: <strong>${alerta.stock} unidades</strong>
                                | <i class='fas fa-truck me-1'></i>${alerta.proveedor_nombre}
                                | <i class='fas fa-clock me-1'></i>${alerta.fecha_alerta}
                            </small>
                            <div class='mt-2'>
                                <span class='badge bg-light text-dark me-1'>${alerta.categoria_nombre}</span>
                                <span class='badge bg-light text-dark'>Caja: ${alerta.unidades_por_caja} und.</span>
                            </div>
                        </div>
                        <div class='btn-group-vertical ms-3'>
                            <button type='button' class='btn btn-sm btn-success btn-marcar-leida' 
                                    data-codigo="${alerta.cod_notificacion}" 
                                    ${alerta.cod_estadonotificacion === 'en002' ? 'disabled' : ''}>
                                <i class='fas fa-check me-1'></i>Marcar
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Configurar eventos de los botones de marcar como leída
        listaAlertas.querySelectorAll('.btn-marcar-leida:not(:disabled)').forEach(btn => {
            btn.addEventListener('click', (e) => this.marcarAlertaLeida(e.target.closest('.btn-marcar-leida').dataset.codigo));
        });
    }

    actualizarResumen() {
        const contadores = this.service.contarAlertasPorPrioridad(this.alertas);
        
        document.getElementById('contadorAlta').textContent = contadores.alta;
        document.getElementById('contadorMedia').textContent = contadores.media;
        document.getElementById('contadorBaja').textContent = contadores.baja;
    }

    actualizarFiltrosActivos() {
        const filtrosActivosDiv = document.getElementById('filtrosActivos');
        const badgesContainer = document.getElementById('badgesFiltrosActivos');
        
        const filtros = [];
        
        if (this.filtrosActivos.estado) {
            const estadoText = this.filtrosActivos.estado === 'en001' ? 'Pendiente' : 'Leída';
            filtros.push(`
                <span class="badge bg-info">
                    Estado: ${estadoText}
                    <a href="javascript:void(0)" class="text-white ms-1" onclick="notificacionesController.removerFiltro('estado')">×</a>
                </span>
            `);
        }
        
        if (this.filtrosActivos.proveedor) {
            const proveedor = this.proveedores.find(p => p.cod_proveedor === this.filtrosActivos.proveedor);
            const proveedorNombre = proveedor ? proveedor.nombre : this.filtrosActivos.proveedor;
            filtros.push(`
                <span class="badge bg-success">
                    Proveedor: ${proveedorNombre}
                    <a href="javascript:void(0)" class="text-white ms-1" onclick="notificacionesController.removerFiltro('proveedor')">×</a>
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

    async aplicarFiltros() {
        const estado = document.getElementById('filtroEstado').value;
        const proveedor = document.getElementById('filtroProveedor').value;
        
        this.filtrosActivos = {
            estado: estado,
            proveedor: proveedor
        };
        
        await this.cargarAlertasConFiltros();
    }

    async cargarAlertasConFiltros() {
        try {
            this.alertas = await this.service.obtenerAlertas(this.filtrosActivos);
            this.actualizarListaAlertas();
            this.actualizarResumen();
            this.actualizarFiltrosActivos();
        } catch (error) {
            console.error('Error cargando alertas con filtros:', error);
            this.mostrarError('Error al aplicar los filtros');
        }
    }

    async limpiarFiltros() {
        document.getElementById('filtroEstado').value = '';
        document.getElementById('filtroProveedor').value = '';
        
        this.filtrosActivos = {
            estado: '',
            proveedor: ''
        };
        
        await this.cargarAlertasConFiltros();
    }

    removerFiltro(tipo) {
        if (tipo === 'estado') {
            document.getElementById('filtroEstado').value = '';
            this.filtrosActivos.estado = '';
        } else if (tipo === 'proveedor') {
            document.getElementById('filtroProveedor').value = '';
            this.filtrosActivos.proveedor = '';
        }
        
        this.cargarAlertasConFiltros();
    }

    async marcarAlertaLeida(codNotificacion) {
        try {
            const resultado = await this.service.marcarAlertaLeida(codNotificacion);
            
            this.mostrarExito('Alerta marcada como leída correctamente');
            
            // Recargar las alertas para reflejar el cambio
            await this.cargarAlertasConFiltros();
            
        } catch (error) {
            console.error('Error marcando alerta como leída:', error);
            this.mostrarError(error.message || 'Error al marcar la alerta como leída');
        }
    }

    async actualizarAlertas() {
        try {
            // Primero generar nuevas alertas automáticas
            await this.service.generarAlertasAutomaticas();
            
            // Luego recargar las alertas
            await this.cargarAlertasConFiltros();
            
            this.mostrarExito('Sistema de alertas actualizado');
            
        } catch (error) {
            console.error('Error actualizando alertas:', error);
            this.mostrarError(error.message || 'Error al actualizar las alertas');
        }
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
let notificacionesController;

document.addEventListener('DOMContentLoaded', () => {
    notificacionesController = new NotificacionesController();
});