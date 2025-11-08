class ReportesService {
    constructor() {
        this.apiBase = '/Integrador/almacen/php/reportes';
    }

    async obtenerMovimientos(filtros = {}) {
        try {
            const params = new URLSearchParams();
            
            if (filtros.fechaInicio) params.append('fecha_inicio', filtros.fechaInicio);
            if (filtros.fechaFin) params.append('fecha_fin', filtros.fechaFin);
            if (filtros.producto) params.append('producto', filtros.producto);
            if (filtros.movimiento) params.append('movimiento', filtros.movimiento);
            
            const queryString = params.toString();
            const url = queryString ? 
                `${this.apiBase}/movimientos.php?${queryString}` : 
                `${this.apiBase}/movimientos.php`;

            const response = await fetch(url);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Error al obtener movimientos');
            }
            
            return data.data;
        } catch (error) {
            console.error('Error en obtenerMovimientos:', error);
            throw error;
        }
    }

    async obtenerProductosVendidos(filtros = {}) {
        try {
            const params = new URLSearchParams();
            
            if (filtros.fechaInicio) params.append('fecha_inicio', filtros.fechaInicio);
            if (filtros.fechaFin) params.append('fecha_fin', filtros.fechaFin);
            
            const queryString = params.toString();
            const url = queryString ? 
                `${this.apiBase}/productos_vendidos.php?${queryString}` : 
                `${this.apiBase}/productos_vendidos.php`;

            const response = await fetch(url);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Error al obtener productos vendidos');
            }
            
            return data.data;
        } catch (error) {
            console.error('Error en obtenerProductosVendidos:', error);
            throw error;
        }
    }

    async obtenerProductos() {
        try {
            const response = await fetch(`${this.apiBase}/productos.php`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Error al obtener productos');
            }
            
            return data.data;
        } catch (error) {
            console.error('Error en obtenerProductos:', error);
            throw error;
        }
    }

    // Método para formatear fechas
    formatearFecha(fecha) {
        return new Date(fecha).toLocaleDateString('es-ES');
    }

    // Método para determinar el color del badge según el tipo de movimiento
    getMovimientoBadgeClass(codTipoMovimiento) {
        const clases = {
            'mov001': 'bg-success', // Entrada
            'mov002': 'bg-danger',  // Salida
            'mov003': 'bg-warning'  // Ajuste
        };
        return clases[codTipoMovimiento] || 'bg-secondary';
    }

    // Método para determinar el color del badge según el stock
    getStockBadgeClass(stock) {
        return stock < 20 ? 'bg-warning' : 'bg-success';
    }
}

export default ReportesService;