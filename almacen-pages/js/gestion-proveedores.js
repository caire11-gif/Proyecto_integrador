// Datos de ejemplo para los proveedores
let proveedores = [
    {
        id: 1,
        nombre: "Coca Cola Perú",
        descripcion: "Bebidas y refrescos",
        telefono: "987 654 321",
        direccion: "Av. Principal 123",
        email: "contacto@cocacola.pe",
        estado: "activo",
        ultimaEntrega: "15/12/2024",
        productos: ["Coca Cola 500ml", "Sprite 500ml", "Fanta 500ml", "Agua San Luis"],
        cantidadProductos: 12
    },
    {
        id: 2,
        nombre: "Nestlé Perú",
        descripcion: "Alimentos y galletas",
        telefono: "987 123 456",
        direccion: "Calle Comercio 456",
        email: "ventas@nestle.pe",
        estado: "activo",
        ultimaEntrega: "14/12/2024",
        productos: ["Galletas Oreo", "Leche Gloria", "Nescafé", "Maggi"],
        cantidadProductos: 8
    },
    {
        id: 3,
        nombre: "Unilever",
        descripcion: "Aceites y limpieza",
        telefono: "987 789 123",
        direccion: "Jr. Industrial 789",
        email: "info@unilever.com",
        estado: "inactivo",
        ultimaEntrega: "12/12/2024",
        productos: ["Aceite Primor", "Detergente OMO", "Jabón Lux", "Shampoo Sedal"],
        cantidadProductos: 6
    }
];

let siguienteId = 4;

// Función para inicializar la página de proveedores
function initProveedores() {
    console.log('Inicializando gestión de proveedores');
    
    // Inicializar eventos
    const btnGuardar = document.getElementById('btnGuardarProveedor');
    if (btnGuardar) {
        btnGuardar.addEventListener('click', guardarProveedor);
    }
    
    // Delegación de eventos para botones de editar y eliminar
    const tbody = document.getElementById('tbodyProveedores');
    if (tbody) {
        tbody.addEventListener('click', function(e) {
            if (e.target.closest('.btn-editar')) {
                const id = parseInt(e.target.closest('.btn-editar').getAttribute('data-id'));
                editarProveedor(id);
            }
            
            if (e.target.closest('.btn-eliminar')) {
                const id = parseInt(e.target.closest('.btn-eliminar').getAttribute('data-id'));
                eliminarProveedor(id);
            }
        });
    }
    
    // Limpiar formulario al abrir modal para nuevo proveedor
    const modalProveedor = document.getElementById('modalProveedor');
    if (modalProveedor) {
        modalProveedor.addEventListener('show.bs.modal', function() {
            if (!document.getElementById('proveedorId').value) {
                limpiarFormulario();
            }
        });
    }
    
    // Cargar datos iniciales
    actualizarTabla();
    actualizarProductosProveedores();
    
    if (window.showNotification) {
        showNotification('Gestión de proveedores cargada correctamente', 'success');
    }
}

function limpiarFormulario() {
    document.getElementById('proveedorId').value = '';
    document.getElementById('nombreProveedor').value = '';
    document.getElementById('descripcionProveedor').value = '';
    document.getElementById('telefonoProveedor').value = '';
    document.getElementById('emailProveedor').value = '';
    document.getElementById('direccionProveedor').value = '';
    document.getElementById('estadoProveedor').value = 'activo';
    document.getElementById('productosProveedor').value = '';
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-plus me-2"></i>Nuevo Proveedor';
}

function guardarProveedor() {
    const id = document.getElementById('proveedorId').value;
    const nombre = document.getElementById('nombreProveedor').value.trim();
    const descripcion = document.getElementById('descripcionProveedor').value.trim();
    const telefono = document.getElementById('telefonoProveedor').value.trim();
    const email = document.getElementById('emailProveedor').value.trim();
    const direccion = document.getElementById('direccionProveedor').value.trim();
    const estado = document.getElementById('estadoProveedor').value;
    const productosTexto = document.getElementById('productosProveedor').value.trim();
    
    // Validaciones básicas
    if (!nombre) {
        showNotification('El nombre del proveedor es obligatorio', 'danger');
        document.getElementById('nombreProveedor').focus();
        return;
    }
    
    if (!telefono) {
        showNotification('El teléfono es obligatorio', 'danger');
        document.getElementById('telefonoProveedor').focus();
        return;
    }
    
    if (!direccion) {
        showNotification('La dirección es obligatoria', 'danger');
        document.getElementById('direccionProveedor').focus();
        return;
    }
    
    if (!productosTexto) {
        showNotification('Debe ingresar al menos un producto', 'danger');
        document.getElementById('productosProveedor').focus();
        return;
    }
    
    const productos = productosTexto.split(',').map(p => p.trim()).filter(p => p);
    
    if (id) {
        // Editar proveedor existente
        const index = proveedores.findIndex(p => p.id === parseInt(id));
        if (index !== -1) {
            proveedores[index] = {
                ...proveedores[index],
                nombre,
                descripcion,
                telefono,
                email,
                direccion,
                estado,
                productos,
                cantidadProductos: productos.length
            };
            showNotification('Proveedor actualizado correctamente', 'success');
        }
    } else {
        // Nuevo proveedor
        const nuevoProveedor = {
            id: siguienteId++,
            nombre,
            descripcion,
            telefono,
            email,
            direccion,
            estado,
            ultimaEntrega: new Date().toLocaleDateString('es-ES'),
            productos,
            cantidadProductos: productos.length
        };
        proveedores.push(nuevoProveedor);
        showNotification('Proveedor agregado correctamente', 'success');
    }
    
    // Cerrar modal y actualizar tabla
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalProveedor'));
    if (modal) modal.hide();
    actualizarTabla();
    actualizarProductosProveedores();
}

function editarProveedor(id) {
    const proveedor = proveedores.find(p => p.id === id);
    if (proveedor) {
        document.getElementById('proveedorId').value = proveedor.id;
        document.getElementById('nombreProveedor').value = proveedor.nombre;
        document.getElementById('descripcionProveedor').value = proveedor.descripcion || '';
        document.getElementById('telefonoProveedor').value = proveedor.telefono;
        document.getElementById('emailProveedor').value = proveedor.email || '';
        document.getElementById('direccionProveedor').value = proveedor.direccion;
        document.getElementById('estadoProveedor').value = proveedor.estado;
        document.getElementById('productosProveedor').value = proveedor.productos.join(', ');
        document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Proveedor';
        
        const modal = new bootstrap.Modal(document.getElementById('modalProveedor'));
        modal.show();
    }
}

function eliminarProveedor(id) {
    if (confirm('¿Está seguro de que desea eliminar este proveedor?')) {
        proveedores = proveedores.filter(p => p.id !== id);
        showNotification('Proveedor eliminado correctamente', 'success');
        actualizarTabla();
        actualizarProductosProveedores();
    }
}

function actualizarTabla() {
    const tbody = document.getElementById('tbodyProveedores');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    proveedores.forEach(proveedor => {
        const badgeEstado = proveedor.estado === 'activo' ? 
            '<span class="badge bg-success">Activo</span>' : 
            '<span class="badge bg-warning">Inactivo</span>';
        
        const row = document.createElement('tr');
        row.setAttribute('data-id', proveedor.id);
        row.innerHTML = `
            <td>
                <div>
                    <strong>${proveedor.nombre}</strong>
                    <br><small class="text-muted">${proveedor.descripcion}</small>
                </div>
            </td>
            <td>
                <div>
                    <i class="fas fa-phone me-1"></i> ${proveedor.telefono}
                    ${proveedor.email ? `<br><i class="fas fa-envelope me-1"></i> ${proveedor.email}` : ''}
                    <br><i class="fas fa-map-marker me-1"></i> ${proveedor.direccion}
                </div>
            </td>
            <td>
                <span class="badge bg-primary">${proveedor.cantidadProductos} productos</span>
            </td>
            <td>${proveedor.ultimaEntrega}</td>
            <td>${badgeEstado}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary btn-editar" data-id="${proveedor.id}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger btn-eliminar" data-id="${proveedor.id}">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function actualizarProductosProveedores() {
    const container = document.getElementById('productosProveedores');
    if (!container) return;
    
    container.innerHTML = '';
    
    proveedores.forEach(proveedor => {
        const productosHTML = proveedor.productos.map(producto => 
            `<span class="badge bg-light text-dark me-1 mb-1">${producto}</span>`
        ).join('');
        
        const col = document.createElement('div');
        col.className = 'col-md-4 mb-3';
        col.setAttribute('data-proveedor-id', proveedor.id);
        col.innerHTML = `
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title">${proveedor.nombre}</h6>
                    <p class="text-muted small mb-2">${proveedor.descripcion}</p>
                    <div class="mt-2">
                        ${productosHTML}
                    </div>
                </div>
            </div>
        `;
        container.appendChild(col);
    });
}

function showNotification(mensaje, tipo) {
    if (window.showNotification) {
        window.showNotification(mensaje, tipo);
        return;
    }
    
    const notificacion = document.createElement('div');
    notificacion.className = `alert alert-${tipo} alert-dismissible fade show`;
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

// Hacer funciones disponibles globalmente
window.initProveedores = initProveedores;

// Auto-inicialización
console.log('Script de Gestión de Proveedores cargado');
setTimeout(initProveedores, 200);