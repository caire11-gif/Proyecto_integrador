class NotificacionesService {
    constructor() {
        this.apiBase = '/Integrador/almacen/php/notificaciones';
    }

    async obtenerAlertas(filtros = {}) {
        try {
            const params = new URLSearchParams();
            
            if (filtros.estado) params.append('filtroEstado', filtros.estado);
            if (filtros.proveedor) params.append('filtroProveedor', filtros.proveedor);
            
            const queryString = params.toString();
            const url = queryString ? 
                `${this.apiBase}/alertas.php?${queryString}` : 
                `${this.apiBase}/alertas.php`;

            const response = await fetch(url);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Error al obtener alertas');
            }
            
            return data.data;
        } catch (error) {
            console.error('Error en obtenerAlertas:', error);
            throw error;
        }
    }

    async obtenerProveedores() {
        try {
            const response = await fetch(`${this.apiBase}/alertas.php?tipo=proveedores`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Error al obtener proveedores');
            }
            
            return data.data;
        } catch (error) {
            console.error('Error en obtenerProveedores:', error);
            throw error;
        }
    }

    async marcarAlertaLeida(codNotificacion) {
        try {
            const response = await fetch(`${this.apiBase}/marcar_leida.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ cod_notificacion: codNotificacion })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Error al marcar alerta como leída');
            }
            
            return data;
        } catch (error) {
            console.error('Error en marcarAlertaLeida:', error);
            throw error;
        }
    }

    async generarAlertasAutomaticas() {
        try {
            const response = await fetch(`${this.apiBase}/generar_alertas.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Error al generar alertas automáticas');
            }
            
            return data;
        } catch (error) {
            console.error('Error en generarAlertasAutomaticas:', error);
            throw error;
        }
    }

    // Método para contar alertas por prioridad
    contarAlertasPorPrioridad(alertas) {
        let alta = 0, media = 0, baja = 0;
        
        alertas.forEach(alerta => {
            if (alerta.mensaje.includes('🚨 ALTA PRIORIDAD')) {
                alta++;
            } else if (alerta.mensaje.includes('⚠️')) {
                media++;
            } else if (alerta.mensaje.includes('ℹ️')) {
                baja++;
            }
        });
        
        return { alta, media, baja };
    }

    // Método para determinar la prioridad de una alerta
    getPriorityInfo(mensaje) {
        if (mensaje.includes('🚨 ALTA PRIORIDAD')) {
            return { class: 'bg-danger', text: 'ALTA' };
        } else if (mensaje.includes('⚠️')) {
            return { class: 'bg-warning', text: 'MEDIA' };
        } else if (mensaje.includes('ℹ️')) {
            return { class: 'bg-info', text: 'BAJA' };
        } else {
            return { class: 'bg-secondary', text: 'INFO' };
        }
    }
}

export default NotificacionesService;