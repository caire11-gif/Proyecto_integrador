class EntradaProveedorService {
    constructor() {
        this.apiUrl = '/Integrador/almacen/php/entradaproveedor/entradaproveedor.php';
        this.guardarUrl = '/Integrador/almacen/php/entradaproveedor/guardar_entrada.php';
        this.historialUrl = '/Integrador/almacen/php/entradaproveedor/historial.php';
        this.detallesUrl = '/Integrador/almacen/php/entradaproveedor/detalles_compra.php';
    }

    async obtenerDatos() {
        try {
            console.log('🔍 Intentando acceder a:', this.apiUrl);
            const response = await fetch(this.apiUrl, {
                method: 'GET'
            });
            
            console.log('📡 Estado de respuesta:', response.status);
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error);
            }
            
            return data;
        } catch (error) {
            console.error('❌ Error al obtener datos:', error);
            throw error;
        }
    }

    async guardarEntrada(datosEntrada) {
        try {
            console.log('💾 Guardando entrada:', datosEntrada);
            const response = await fetch(this.guardarUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(datosEntrada)
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error);
            }
            
            return data;
        } catch (error) {
            console.error('❌ Error al guardar entrada:', error);
            throw error;
        }
    }

    async obtenerHistorial(filtro = 'todos', busqueda = '') {
        try {
            const params = new URLSearchParams();
            if (filtro !== 'todos') params.append('filtro', filtro);
            if (busqueda) params.append('busqueda', busqueda);

            const url = `${this.historialUrl}?${params.toString()}`;
            console.log('📋 Obteniendo historial:', url);

            const response = await fetch(url, {
                method: 'GET'
            });
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error);
            }
            
            return data;
        } catch (error) {
            console.error('❌ Error al obtener historial:', error);
            throw error;
        }
    }

    async obtenerDetallesCompra(codCompra) {
        try {
            const url = `${this.detallesUrl}?cod_compra=${codCompra}`;
            console.log('🔍 Obteniendo detalles:', url);

            const response = await fetch(url, {
                method: 'GET'
            });
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error);
            }
            
            return data;
        } catch (error) {
            console.error('❌ Error al obtener detalles:', error);
            throw error;
        }
    }
}