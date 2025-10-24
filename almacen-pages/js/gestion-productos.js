// Base de datos simulada de productos
let productos = [
    {
        id: 1,
        nombre: "Coca Cola 500ml",
        referencia: "CC-500",
        categoria: "bebidas",
        proveedor: "coca",
        precioCosto: 2.50,
        precioVenta: 3.80,
        unidadesPorCaja: 24,
        stockAlmacen: 2,
        stockTienda: 48,
        stockMinAlmacen: 5,
        stockMinTienda: 20
    },
    {
        id: 2,
        nombre: "Galletas Oreo 120g",
        referencia: "GO-120",
        categoria: "galletas",
        proveedor: "nestle",
        precioCosto: 1.80,
        precioVenta: 2.50,
        unidadesPorCaja: 12,
        stockAlmacen: 8,
        stockTienda: 8,
        stockMinAlmacen: 10,
        stockMinTienda: 15
    },
    {
        id: 3,
        nombre: "Aceite Primor 1L",
        referencia: "AP-1L",
        categoria: "abarrotes",
        proveedor: "unilever",
        precioCosto: 8.50,
        precioVenta: 12.00,
        unidadesPorCaja: 6,
        stockAlmacen: 4,
        stockTienda: 18,
        stockMinAlmacen: 6,
        stockMinTienda: 12
    }
];

// Variables para paginación
let productosPorPagina = 10;
let paginaActual = 1;
let productosFiltrados = [];

// Función de inicialización
function initGestionProductos() {
    console.log('Inicializando Gestión de Productos...');
    
    setTimeout(() => {
        cargarProductos();
        
        const buscarProducto = document.getElementById('buscarProducto');
        const filtroCategoria = document.getElementById('filtroCategoria');
        const filtroProveedor = document.getElementById('filtroProveedor');
        
        if (buscarProducto) buscarProducto.addEventListener('input', filtrarProductos);
        if (filtroCategoria) filtroCategoria.addEventListener('change', filtrarProductos);
        if (filtroProveedor) filtroProveedor.addEventListener('change', filtrarProductos);
        
        const btnLimpiar = document.getElementById('btnLimpiarFiltros');
        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', function() {
                document.getElementById('buscarProducto').value = '';
                document.getElementById('filtroCategoria').value = '';
                document.getElementById('filtroProveedor').value = '';
                filtrarProductos();
                showNotification('Filtros limpiados', 'info');
            });
        }
        
        const btnNuevo = document.getElementById('btnNuevoProducto');
        if (btnNuevo) {
            btnNuevo.addEventListener('click', function() {
                document.getElementById('modalTitulo').textContent = 'Nuevo Producto';
                document.getElementById('formProducto').reset();
                document.getElementById('productoId').value = '';
            });
        }
        
        const btnGuardar = document.getElementById('btnGuardarProducto');
        if (btnGuardar) btnGuardar.addEventListener('click', guardarProducto);
        
        const btnActualizarStock = document.getElementById('btnActualizarStock');
        if (btnActualizarStock) btnActualizarStock.addEventListener('click', actualizarStock);
        
        const btnExportar = document.getElementById('btnExportar');
        if (btnExportar) btnExportar.addEventListener('click', exportarProductos);
        
        console.log('Gestión de Productos inicializada correctamente');
    }, 100);
}

function cargarProductos() {
    productosFiltrados = [...productos];
    actualizarTabla();
    actualizarPaginacion();
}

function filtrarProductos() {
    const busqueda = document.getElementById('buscarProducto').value.toLowerCase();
    const categoria = document.getElementById('filtroCategoria').value;
    const proveedor = document.getElementById('filtroProveedor').value;
    
    productosFiltrados = productos.filter(producto => {
        const coincideNombre = producto.nombre.toLowerCase().includes(busqueda) || 
                              producto.referencia.toLowerCase().includes(busqueda);
        const coincideCategoria = !categoria || producto.categoria === categoria;
        const coincideProveedor = !proveedor || producto.proveedor === proveedor;
        
        return coincideNombre && coincideCategoria && coincideProveedor;
    });
    
    paginaActual = 1;
    actualizarTabla();
    actualizarPaginacion();
}

function actualizarTabla() {
    const tabla = document.getElementById('tablaProductos');
    if (!tabla) return;
    
    const inicio = (paginaActual - 1) * productosPorPagina;
    const fin = inicio + productosPorPagina;
    const productosPagina = productosFiltrados.slice(inicio, fin);
    
    tabla.innerHTML = '';
    
    productosPagina.forEach(producto => {
        const fila = document.createElement('tr');
        
        const stockMinAlmacen = producto.stockMinAlmacen || 5;
        const stockMinTienda = producto.stockMinTienda || 10;
        
        const claseAlmacen = producto.stockAlmacen < stockMinAlmacen ? 'bg-danger' : 
                            producto.stockAlmacen <= stockMinAlmacen + 2 ? 'bg-warning' : 'bg-success';
        
        const claseTienda = producto.stockTienda < stockMinTienda ? 'bg-danger' : 
                           producto.stockTienda <= stockMinTienda + 5 ? 'bg-warning' : 'bg-success';
        
        fila.innerHTML = `
            <td>
                <div>
                    <strong>${producto.nombre}</strong>
                    <br><small class="text-muted">REF: ${producto.referencia}</small>
                </div>
            </td>
            <td>${obtenerNombreCategoria(producto.categoria)}</td>
            <td>${obtenerNombreProveedor(producto.proveedor)}</td>
            <td>S/ ${producto.precioCosto.toFixed(2)}</td>
            <td>S/ ${producto.precioVenta.toFixed(2)}</td>
            <td>${producto.unidadesPorCaja}</td>
            <td>
                <span class="badge ${claseAlmacen}">${producto.stockAlmacen} cajas</span>
                <br><small>Mín: ${stockMinAlmacen}</small>
            </td>
            <td>
                <span class="badge ${claseTienda}">${producto.stockTienda} unid.</span>
                <br><small>Mín: ${stockMinTienda}</small>
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" title="Editar" onclick="editarProducto(${producto.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-warning" title="Stock" onclick="gestionarStock(${producto.id})">
                        <i class="fas fa-box"></i>
                    </button>
                    <button class="btn btn-outline-danger" title="Eliminar" onclick="eliminarProducto(${producto.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        
        tabla.appendChild(fila);
    });
    
    const totalProductos = document.getElementById('totalProductos');
    if (totalProductos) {
        totalProductos.textContent = productosFiltrados.length;
    }
}

function actualizarPaginacion() {
    const paginacion = document.getElementById('paginacion');
    if (!paginacion) return;
    
    const totalPaginas = Math.ceil(productosFiltrados.length / productosPorPagina);
    
    paginacion.innerHTML = '';
    
    const liAnterior = document.createElement('li');
    liAnterior.className = `page-item ${paginaActual === 1 ? 'disabled' : ''}`;
    liAnterior.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${paginaActual - 1})">Anterior</a>`;
    paginacion.appendChild(liAnterior);
    
    for (let i = 1; i <= totalPaginas; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === paginaActual ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${i})">${i}</a>`;
        paginacion.appendChild(li);
    }
    
    const liSiguiente = document.createElement('li');
    liSiguiente.className = `page-item ${paginaActual === totalPaginas ? 'disabled' : ''}`;
    liSiguiente.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${paginaActual + 1})">Siguiente</a>`;
    paginacion.appendChild(liSiguiente);
}

function cambiarPagina(pagina) {
    paginaActual = pagina;
    actualizarTabla();
    actualizarPaginacion();
}

function obtenerNombreCategoria(codigo) {
    const categorias = {
        'bebidas': 'Bebidas',
        'galletas': 'Galletas',
        'limpieza': 'Limpieza',
        'abarrotes': 'Abarrotes'
    };
    return categorias[codigo] || codigo;
}

function obtenerNombreProveedor(codigo) {
    const proveedores = {
        'coca': 'Coca Cola',
        'nestle': 'Nestlé',
        'unilever': 'Unilever'
    };
    return proveedores[codigo] || codigo;
}

function editarProducto(id) {
    const producto = productos.find(p => p.id === id);
    if (!producto) return;
    
    document.getElementById('modalTitulo').textContent = 'Editar Producto';
    document.getElementById('productoId').value = producto.id;
    document.getElementById('nombreProducto').value = producto.nombre;
    document.getElementById('codigoReferencia').value = producto.referencia;
    document.getElementById('categoria').value = producto.categoria;
    document.getElementById('proveedor').value = producto.proveedor;
    document.getElementById('precioCosto').value = producto.precioCosto;
    document.getElementById('precioVenta').value = producto.precioVenta;
    document.getElementById('unidadesPorCaja').value = producto.unidadesPorCaja;
    
    const modalElement = document.getElementById('modalProducto');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

function guardarProducto() {
    const form = document.getElementById('formProducto');
    if (!form || !form.checkValidity()) {
        if (form) form.reportValidity();
        return;
    }
    
    const id = document.getElementById('productoId').value;
    const producto = {
        nombre: document.getElementById('nombreProducto').value,
        referencia: document.getElementById('codigoReferencia').value,
        categoria: document.getElementById('categoria').value,
        proveedor: document.getElementById('proveedor').value,
        precioCosto: parseFloat(document.getElementById('precioCosto').value),
        precioVenta: parseFloat(document.getElementById('precioVenta').value),
        unidadesPorCaja: parseInt(document.getElementById('unidadesPorCaja').value)
    };
    
    if (id) {
        const index = productos.findIndex(p => p.id == id);
        if (index !== -1) {
            producto.id = parseInt(id);
            producto.stockAlmacen = productos[index].stockAlmacen;
            producto.stockTienda = productos[index].stockTienda;
            producto.stockMinAlmacen = productos[index].stockMinAlmacen;
            producto.stockMinTienda = productos[index].stockMinTienda;
            productos[index] = producto;
            showNotification('Producto actualizado correctamente', 'success');
        }
    } else {
        producto.id = productos.length > 0 ? Math.max(...productos.map(p => p.id)) + 1 : 1;
        producto.stockAlmacen = 0;
        producto.stockTienda = 0;
        producto.stockMinAlmacen = 5;
        producto.stockMinTienda = 10;
        productos.push(producto);
        showNotification('Producto agregado correctamente', 'success');
    }
    
    const modalElement = document.getElementById('modalProducto');
    if (modalElement) {
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) modal.hide();
    }
    
    filtrarProductos();
}

function gestionarStock(id) {
    const producto = productos.find(p => p.id === id);
    if (!producto) return;
    
    document.getElementById('stockProductoId').value = producto.id;
    document.getElementById('stockProductoNombre').value = producto.nombre;
    document.getElementById('stockAlmacenActual').value = producto.stockAlmacen;
    document.getElementById('stockTiendaActual').value = producto.stockTienda;
    document.getElementById('stockAlmacenNuevo').value = producto.stockAlmacen;
    document.getElementById('stockTiendaNuevo').value = producto.stockTienda;
    
    const modalElement = document.getElementById('modalStock');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

function actualizarStock() {
    const id = document.getElementById('stockProductoId').value;
    const stockAlmacen = parseInt(document.getElementById('stockAlmacenNuevo').value);
    const stockTienda = parseInt(document.getElementById('stockTiendaNuevo').value);
    
    if (isNaN(stockAlmacen) || stockAlmacen < 0 || isNaN(stockTienda) || stockTienda < 0) {
        showNotification('Los valores de stock deben ser números positivos', 'error');
        return;
    }
    
    const producto = productos.find(p => p.id == id);
    if (producto) {
        producto.stockAlmacen = stockAlmacen;
        producto.stockTienda = stockTienda;
        
        const modalElement = document.getElementById('modalStock');
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) modal.hide();
        }
        
        showNotification('Stock actualizado correctamente', 'success');
        filtrarProductos();
    }
}

function eliminarProducto(id) {
    if (confirm('¿Está seguro de que desea eliminar este producto?')) {
        productos = productos.filter(p => p.id !== id);
        showNotification('Producto eliminado correctamente', 'success');
        filtrarProductos();
    }
}

function exportarProductos() {
    let csvContent = "Nombre,Referencia,Categoría,Proveedor,Precio Costo,Precio Venta,Unidades por Caja,Stock Almacén,Stock Tienda,Stock Mín Almacén,Stock Mín Tienda\n";
    
    productosFiltrados.forEach(producto => {
        const stockMinAlmacen = producto.stockMinAlmacen || 5;
        const stockMinTienda = producto.stockMinTienda || 10;
        
        csvContent += `"${producto.nombre}","${producto.referencia}","${obtenerNombreCategoria(producto.categoria)}","${obtenerNombreProveedor(producto.proveedor)}",${producto.precioCosto},${producto.precioVenta},${producto.unidadesPorCaja},${producto.stockAlmacen},${producto.stockTienda},${stockMinAlmacen},${stockMinTienda}\n`;
    });
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', 'productos.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Productos exportados correctamente', 'success');
}

function showNotification(mensaje, tipo) {
    if (window.showNotification) {
        window.showNotification(mensaje, tipo);
        return;
    }
    
    const notificacion = document.createElement('div');
    notificacion.className = `alert alert-${tipo === 'error' ? 'danger' : tipo} alert-dismissible fade show`;
    notificacion.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
    notificacion.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notificacion);
    
    setTimeout(() => {
        if (notificacion.parentNode) {
            notificacion.remove();
        }
    }, 3000);
}

window.editarProducto = editarProducto;
window.gestionarStock = gestionarStock;
window.eliminarProducto = eliminarProducto;
window.cambiarPagina = cambiarPagina;
window.initGestionProductos = initGestionProductos;

console.log('Script de Gestión de Productos cargado');
setTimeout(initGestionProductos, 200);