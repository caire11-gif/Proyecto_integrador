// Variables globales para la venta
let productosVenta = [];
let metodoPagoSeleccionado = 'mp001';
let tipoDocumentoSeleccionado = 'boleta';
let subtotal = 0;
let igv = 0;
let total = 0;

// Configurar botones de tipo documento
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.documento-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.documento-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            tipoDocumentoSeleccionado = this.getAttribute('data-tipo');
            document.getElementById('inputTipoDocumento').value = tipoDocumentoSeleccionado;
            actualizarInterfazPorDocumento();
        });
    });

    document.querySelectorAll('.metodo-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.metodo-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            metodoPagoSeleccionado = this.getAttribute('data-metodo');
            document.getElementById('inputMetodoPago').value = metodoPagoSeleccionado;                
            
            const montoEfectivo = document.getElementById('montoEfectivo');
            if (metodoPagoSeleccionado === 'mp001') {
                montoEfectivo.style.display = 'block';
                actualizarCambio();
            } else {
                montoEfectivo.style.display = 'none';
            }
        });
    });

    actualizarInterfazPorDocumento();

    // Configurar filtro de productos
    const filtroProductos = document.getElementById('filtroProductos');
    const tablaProductos = document.getElementById('tablaProductos');
    const filasProductos = tablaProductos.getElementsByTagName('tr');

    filtroProductos.addEventListener('input', function() {
        const filtro = this.value.toLowerCase();
            
        for (let i = 0; i < filasProductos.length; i++) {
            const fila = filasProductos[i];
            const celdas = fila.getElementsByTagName('td');
            let mostrarFila = false;
                
            if (celdas.length >= 2) {
                const codigo = celdas[0].textContent.toLowerCase();
                const nombre = celdas[1].textContent.toLowerCase();
                    
                if (codigo.includes(filtro) || nombre.includes(filtro)) {
                    mostrarFila = true;
                }
            }
                
            fila.style.display = mostrarFila ? '' : 'none';
        }
    });

    // Auto-ocultar mensaje de éxito después de 5 segundos
    const alertSuccess = document.querySelector('.alert-success');
    if (alertSuccess) {
        setTimeout(() => {
            alertSuccess.classList.remove('show');
            setTimeout(() => {
                alertSuccess.remove();
            }, 300);
        }, 5000);
    }
});

function actualizarInterfazPorDocumento() {
    const btnFinalizar = document.getElementById('btnFinalizar');
    const datosClienteSection = document.getElementById('datosClienteSection');

    if (tipoDocumentoSeleccionado === 'factura') {
        btnFinalizar.innerHTML = '<i class="fas fa-file-invoice-dollar"></i> Generar Factura';
        datosClienteSection.style.display = 'block';
    } else {
        btnFinalizar.innerHTML = '<i class="fas fa-check-circle"></i> Finalizar Venta';
        datosClienteSection.style.display = 'none';
    }
}

// Función para agregar productos
function agregarProducto(codigo, nombre, precio, stock) {
    const productoExistente = productosVenta.find(p => p.codigo === codigo);        
    
    if (productoExistente) {
        if (productoExistente.cantidad < stock) {
            productoExistente.cantidad++;
            productoExistente.total = productoExistente.cantidad * precio;
        } else {
            Swal.fire({
                title: "Stock insuficiente",
                text: "No hay suficiente stock en este producto",
                icon: "warning",
                width: 350
            })
            return;
        }
    } else {
        if (stock <= 0) {
            alert('❌ Producto sin stock disponible');
            return;
        }   

        productosVenta.push({
            codigo: codigo,
            nombre: nombre,
            precio: parseFloat(precio),
            cantidad: 1,
            total: parseFloat(precio),
            stock: stock
        });
    }

    actualizarVenta();
}

function agregarProductoDesdeBusqueda(codigo, nombre, precio, stock) {
    agregarProducto(codigo, nombre, precio, stock);
}

// Función para actualizar la venta
function actualizarVenta() {
    const listaProductos = document.getElementById('listaProductosVenta');
        
    if (productosVenta.length === 0) {
        listaProductos.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-shopping-cart fa-3x mb-3 text-muted"></i>
                <p>No hay productos agregados</p>
                <small class="text-muted">Busca y agrega productos para comenzar</small>
            </div>
        `;
    } else {
        let html = '';
        subtotal = 0;

        productosVenta.forEach((producto, index) => {
            subtotal += producto.total;
            html += `
                <div class="producto-venta">
                    <div class="producto-info">
                    <div class="producto-nombre">${producto.nombre}</div>
                        <small class="text-muted">Código: ${producto.codigo}</small>
                        <div class="producto-precio">S/ ${producto.precio.toFixed(2)} c/u</div>
                    </div>
                    <div class="producto-controls">
                        <div class="cantidad-controls">
                            <button type="button" class="btn-cantidad" onclick="modificarCantidad(${index}, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="badge bg-primary badge-cantidad mx-2">${producto.cantidad}</span>
                            <button type="button" class="btn-cantidad" onclick="modificarCantidad(${index}, 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="producto-total text-success">
                            <strong>S/ ${producto.total.toFixed(2)}</strong>
                        </div>
                        <button type="button" class="btn-quitar" onclick="eliminarProducto(${index})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
        });
            
        listaProductos.innerHTML = html;
    }
        
    // Calcular totales
    igv = parseFloat((subtotal * 0.18).toFixed(2));
    total = parseFloat((subtotal + igv).toFixed(2))
    document.getElementById('subtotal').textContent = 'S/ ' + subtotal.toFixed(2);
    document.getElementById('igv').textContent = 'S/ ' + igv.toFixed(2);
    document.getElementById('totalVenta').textContent = 'S/ ' + total.toFixed(2);
        
    // Actualizar inputs hidden del formulario
    document.getElementById('inputTotal').value = total;
    document.getElementById('inputProductosJson').value = JSON.stringify(productosVenta);
        
    // Habilitar/deshabilitar botón finalizar
    const btnFinalizar = document.getElementById('btnFinalizar');
    btnFinalizar.disabled = productosVenta.length === 0;

    // Actualizar cambio si es pago en efectivo
    if (metodoPagoSeleccionado === 'mp001') {
        actualizarCambio();
    }
}

function modificarCantidad(index, cambio) {
    const producto = productosVenta[index];
    const nuevaCantidad = producto.cantidad + cambio;

    if (nuevaCantidad <= 0) {
        eliminarProducto(index);
        return;
    }
        
    if (nuevaCantidad > producto.stock) {
        Swal.fire({
            title: "Stock insuficiente",
            text: `❌ No hay suficiente stock. Stock disponible: ${producto.stock}`,
            icon: "warning",
            width: 350,
        });
        return;
    }
        
    producto.cantidad = nuevaCantidad;
    producto.total = producto.cantidad * producto.precio;
    actualizarVenta();
}

function eliminarProducto(index) {
    productosVenta.splice(index, 1);
    actualizarVenta();
}

// Evento para limpiar venta
document.getElementById('btnLimpiar').addEventListener('click', function() {
    if(productosVenta.length === 0) {
        Swal.fire({
            title: "Sin productos",
            text: "⚠️ No hay productos en la venta",
            icon: "warning",
            width: 350
        })
        return;
    }
        
    Swal.fire({
        title: "Borrar las ventas",
        text: "¿Estás seguro de que deseas limpiar toda la venta?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Si, estoy seguro!"
    }).then((result1) => {
        if (result1.isConfirmed) {
            productosVenta = [];
            actualizarVenta();
        }
    });
});

function actualizarCambio() {
    const efectivo = parseFloat(document.getElementById('inputEfectivo').value) || 0;
    const cambio = efectivo - total;
    document.getElementById('cambio').textContent = 'S/ ' + (cambio > 0 ? cambio.toFixed(2) : '0.00');
}

document.getElementById('inputEfectivo').addEventListener('input', actualizarCambio);

// Validar formulario antes de enviar
document.getElementById('formVenta').addEventListener('submit', function(e) {
    if (productosVenta.length === 0) {
        e.preventDefault();
        Swal.fire({
            title: "Sin productos",
            text: "❌ No hay productos en la venta",
            icon: "warning",
            width: 350
        })
        return;
    }
        
    // Validar datos del cliente para factura
    if (tipoDocumentoSeleccionado === 'factura') {
        const ruc = document.getElementById('inputRUC').value;
        const razonSocial = document.getElementById('inputRazonSocial').value;            

        if (!ruc || ruc.length !== 11) {
            e.preventDefault();
            alert('❌ Para factura debe ingresar un RUC válido de 11 dígitos');
            document.getElementById('inputRUC').focus();
            return;
        }
            
        if (!razonSocial) {
            e.preventDefault();
            alert('❌ Para factura debe ingresar la Razón Social del cliente');
            document.getElementById('inputRazonSocial').focus();
            return;
        }
    }
        
    if (metodoPagoSeleccionado === 'mp001') {
        const efectivo = parseFloat(document.getElementById('inputEfectivo').value) || 0;
        if (efectivo <= 0) {
            e.preventDefault();
            alert('❌ Ingrese el monto en efectivo recibido');
            document.getElementById('inputEfectivo').focus();
            return;
        }
            
        if (efectivo < total) {
            e.preventDefault();
            alert(`❌ El efectivo recibido (S/ ${efectivo.toFixed(2)}) es menor al total de la venta (S/ ${total.toFixed(2)})`);
            document.getElementById('inputEfectivo').focus();
            return;
        }
    }
        
    // Mostrar mensaje de procesamiento
    const btnFinalizar = document.getElementById('btnFinalizar');
    btnFinalizar.disabled = true;
    btnFinalizar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
});