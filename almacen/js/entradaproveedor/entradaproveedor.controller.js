class EntradaProveedorController {
    constructor() {
        this.service = new EntradaProveedorService();
        this.productosData = []; // Array completo de productos
        this.preciosProductos = {};
        this.unidadesPorCaja = {};
        this.nombresProductos = {};
        this.filtroActual = 'todos';
        this.busquedaActual = '';
        this.init();
    }

    async init() {
        try {
            // Cargar datos iniciales
            const data = await this.service.obtenerDatos();
            
            // Actualizar UI con datos del usuario
            this.actualizarUsuario(data.usuario);
            
            // Guardar datos completos de productos
            this.productosData = data.productos;
            
            // Preparar datos de productos para cálculos
            this.prepararDatosProductos(data.productos);
            
            // Cargar datos en los select
            this.cargarSelects(data);
            
            // Configurar eventos
            this.configurarEventos();
            
            // Configurar fecha actual
            this.configurarFechaActual();
            
        } catch (error) {
            this.mostrarError('Error al cargar los datos: ' + error.message);
        }
    }

    actualizarUsuario(usuario) {
        document.getElementById('usuarioAvatar').textContent = usuario.iniciales;
        document.getElementById('userName').textContent = `${usuario.nombre} ${usuario.apellido}`;
    }

    configurarFechaActual() {
        const hoy = new Date().toISOString().split('T')[0];
        document.getElementById('fechaHoy').textContent = new Date().toLocaleDateString('es-PE');
        document.getElementById('fechaEntrada').value = hoy;
    }

    cargarSelects(data) {
        // Cargar proveedores
        const proveedorSelect = document.getElementById('proveedorSelect');
        proveedorSelect.innerHTML = '<option value="">Seleccione proveedor</option>';
        data.proveedores.forEach(proveedor => {
            proveedorSelect.innerHTML += `<option value="${proveedor.cod_proveedor}">${proveedor.nombre}</option>`;
        });

        // Cargar tipos de documento
        const tipoComprobante = document.getElementById('tipoComprobante');
        tipoComprobante.innerHTML = '<option value="">Seleccione documento</option>';
        data.tipos_documento.forEach(documento => {
            tipoComprobante.innerHTML += `<option value="${documento.cod_tipodocumento}">${documento.nombre}</option>`;
        });

        // Cargar productos en la primera fila
        this.actualizarSelectProductos();
    }

    prepararDatosProductos(productos) {
        // Guardar datos completos y crear arrays para cálculos rápidos
        productos.forEach(producto => {
            this.preciosProductos[producto.cod_producto] = producto.precio_costo;
            this.unidadesPorCaja[producto.cod_producto] = producto.unidades_por_caja;
            this.nombresProductos[producto.cod_producto] = producto.nombre;
        });
    }

    actualizarSelectProductos() {
        const selects = document.querySelectorAll('.product-select');
        selects.forEach(select => {
            const currentValue = select.value;
            select.innerHTML = '<option value="">Seleccione producto</option>';
            
            this.productosData.forEach(producto => {
                const selected = producto.cod_producto === currentValue ? 'selected' : '';
                select.innerHTML += `<option value="${producto.cod_producto}" 
                    data-precio="${producto.precio_costo}" 
                    data-unidades="${producto.unidades_por_caja}">
                    ${producto.nombre}
                </option>`;
            });
        });
    }

    cargarPrecioProducto(selectElement) {
        const productoId = selectElement.value;
        const row = selectElement.closest('tr');
        const precioInput = row.querySelector('.precio-caja-input');
        const unidadesCell = row.querySelector('.unidades-caja');
        
        if (productoId && this.preciosProductos[productoId]) {
            precioInput.value = parseFloat(this.preciosProductos[productoId]).toFixed(2);
            unidadesCell.textContent = this.unidadesPorCaja[productoId] || 0;
            this.calcularTotalFila(selectElement);
        } else {
            precioInput.value = '0.00';
            unidadesCell.textContent = '0';
            row.querySelector('.total-unidades').textContent = '0';
            row.querySelector('.total-producto').textContent = 'S/ 0.00';
        }
    }

    calcularTotalFila(inputElement) {
        const row = inputElement.closest('tr');
        const cantidadCajas = parseFloat(row.querySelector('.cantidad-input').value) || 0;
        const precioPorCaja = parseFloat(row.querySelector('.precio-caja-input').value) || 0;
        const productoSelect = row.querySelector('.product-select');
        const productoId = productoSelect.value;
        const totalUnidadesCell = row.querySelector('.total-unidades');
        
        // Calcular total basado en PRECIO POR CAJA (CORRECTO)
        const unidadesPorCajaProducto = this.unidadesPorCaja[productoId] || 1;
        const cantidadUnidades = cantidadCajas * unidadesPorCajaProducto;
        const total = cantidadCajas * precioPorCaja; // Total = cantidad_cajas * precio_por_caja
        
        // Actualizar todas las celdas
        totalUnidadesCell.textContent = cantidadUnidades;
        row.querySelector('.total-producto').textContent = 'S/ ' + total.toFixed(2);
        this.calcularTotalGeneral();
    }

    calcularTotalGeneral() {
        let totalGeneral = 0;
        document.querySelectorAll('.product-row').forEach(row => {
            const totalTexto = row.querySelector('.total-producto').textContent;
            const total = parseFloat(totalTexto.replace('S/ ', '')) || 0;
            totalGeneral += total;
        });
        
        document.getElementById('totalGeneral').textContent = 'S/ ' + totalGeneral.toFixed(2);
    }

   agregarFila() {
    console.log('Agregando nueva fila...');
    
    const tbody = document.getElementById('detallesEntrada');
    const newRow = document.createElement('tr');
    newRow.className = 'product-row';
    
    // Crear options para el select de productos
    let optionsHTML = '<option value="">Seleccione producto</option>';
    this.productosData.forEach(producto => {
        optionsHTML += `<option value="${producto.cod_producto}" 
            data-precio="${producto.precio_costo}" 
            data-unidades="${producto.unidades_por_caja}">
            ${producto.nombre}
        </option>`;
    });
    
    newRow.innerHTML = `
        <td>        
            <select class="form-select product-select" name="productos[]" required>
                ${optionsHTML}
            </select>
        </td>
        <td>
            <input type="number" class="form-control cantidad-input" name="cantidades[]" value="1" min="1" required>
        </td>
        <td class="unidades-caja text-center">0</td>
        <td class="total-unidades text-center">0</td>
        <td>
            <input type="number" class="form-control precio-caja-input" name="precios_caja[]" value="0.00" step="0.01" min="0" required>
        </td>
        <td class="total-producto">S/ 0.00</td>
        <td>
            <button type="button" class="btn btn-sm btn-danger action-btn">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(newRow);
    this.actualizarContadorProductos();
    this.configurarEventosFila(newRow);
    
    console.log('✅ Nueva fila agregada correctamente');
}
    eliminarFila(button) {
        const row = button.closest('tr');
        if (document.querySelectorAll('.product-row').length > 1) {
            row.remove();
            this.actualizarContadorProductos();
            this.calcularTotalGeneral();
        } else {
            alert('Debe haber al menos un producto en la entrada.');
        }
    }

    actualizarContadorProductos() {
        const count = document.querySelectorAll('.product-row').length;
        document.getElementById('contadorProductos').textContent = count + ' producto(s)';
    }

    async guardarEntrada(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        // Validar fecha
        const fechaEntrada = document.getElementById('fechaEntrada').value;
        const hoy = new Date();
        const fecha = new Date(fechaEntrada);
        hoy.setHours(0,0,0,0);
        fecha.setHours(0,0,0,0);

        if(fecha > hoy){
            alert("La fecha no puede ser superior a la de hoy");
            return;
        }

        // Validar que haya al menos un producto seleccionado
        const productos = formData.getAll('productos[]');
        const productosValidos = productos.filter(p => p !== '');
        if (productosValidos.length === 0) {
            alert("Debe agregar al menos un producto válido");
            return;
        }

        // Preparar datos para enviar
        const datosEntrada = {
            proveedor: formData.get('proveedor'),
            fecha_entrada: formData.get('fecha_entrada'),
            numero_factura: formData.get('numero_factura'),
            tipo_comprobante: formData.get('tipo_comprobante'),
            productos: formData.getAll('productos[]'),
            cantidades: formData.getAll('cantidades[]'),
            precios_caja: formData.getAll('precios_caja[]')
        };

        try {
            const resultado = await this.service.guardarEntrada(datosEntrada);
            alert(resultado.message);
            this.resetForm();
            // Recargar historial después de guardar
            await this.cargarHistorial();
        } catch (error) {
            this.mostrarError('Error al guardar la entrada: ' + error.message);
        }
    }

    resetForm() {
        document.getElementById('formEntrada').reset();
        // Mantener solo una fila
        const tbody = document.getElementById('detallesEntrada');
        const filas = tbody.querySelectorAll('.product-row');
        for (let i = 1; i < filas.length; i++) {
            filas[i].remove();
        }
        // Resetear valores de la primera fila
        const primeraFila = tbody.querySelector('.product-row');
        if (primeraFila) {
            primeraFila.querySelector('.product-select').value = '';
            primeraFila.querySelector('.cantidad-input').value = '1';
            primeraFila.querySelector('.precio-caja-input').value = '0.00';
            primeraFila.querySelector('.unidades-caja').textContent = '0';
            primeraFila.querySelector('.total-unidades').textContent = '0';
            primeraFila.querySelector('.total-producto').textContent = 'S/ 0.00';
        }
        document.getElementById('totalGeneral').textContent = 'S/ 0.00';
        this.actualizarContadorProductos();
        this.configurarFechaActual();
    }

    async cargarHistorial(filtro = this.filtroActual, busqueda = this.busquedaActual) {
        try {
            const data = await this.service.obtenerHistorial(filtro, busqueda);
            this.mostrarHistorial(data.compras);
            this.actualizarFiltrosUI(filtro, busqueda, data.total);
        } catch (error) {
            this.mostrarError('Error al cargar el historial: ' + error.message);
        }
    }

    mostrarHistorial(compras) {
        const tbody = document.querySelector('#tablaHistorial tbody');
        
        if (compras.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        No hay compras registradas
                        ${this.busquedaActual ? ` para la búsqueda "${this.busquedaActual}"` : ''}
                        ${this.filtroActual !== 'todos' ? ' en el período seleccionado' : ''}
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        compras.forEach(compra => {
            html += `
                <tr>
                    <td><strong>${compra.cod_compra}</strong></td>
                    <td>${new Date(compra.fecha).toLocaleDateString('es-PE')}</td>
                    <td>${compra.proveedor_nombre}</td>
                    <td><span class="badge bg-info">${compra.total_productos} productos</span></td>
                    <td><strong>S/ ${parseFloat(compra.total_compra).toFixed(2)}</strong></td>
                    <td>${compra.metodo_pago}</td>
                    <td>${compra.usuario_registro}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary action-btn" title="Ver detalles" onclick="entradaController.verDetallesCompra('${compra.cod_compra}')">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-success action-btn" title="Descargar PDF">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        tbody.innerHTML = html;
    }

    actualizarFiltrosUI(filtro, busqueda, total) {
        this.filtroActual = filtro;
        this.busquedaActual = busqueda;

        // Actualizar texto del dropdown de filtros
        const nombres_filtros = {
            'todos': 'Todos',
            'hoy': 'Hoy', 
            'semana': 'Esta semana',
            'mes': 'Este mes'
        };
        
        const filterDropdown = document.querySelector('#filterDropdown');
        if (filterDropdown) {
            filterDropdown.innerHTML = `<i class="fas fa-filter me-1"></i>${nombres_filtros[filtro]}`;
        }

        // Actualizar items activos del dropdown
        document.querySelectorAll('.filter-dropdown .dropdown-item').forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('data-filtro') === filtro) {
                item.classList.add('active');
            }
        });

        // Actualizar campo de búsqueda
        const buscarInput = document.getElementById('buscarHistorial');
        if (buscarInput) {
            buscarInput.value = busqueda;
        }

        // Actualizar contador total
        const totalElement = document.querySelector('.total-compras');
        if (totalElement) {
            totalElement.textContent = `Total: ${total} compra(s)`;
        }

        // Mostrar/ocultar botón limpiar filtros
        const btnLimpiarContainer = document.querySelector('.btn-limpiar-filtros');
        if (btnLimpiarContainer) {
            if (filtro !== 'todos' || busqueda) {
                btnLimpiarContainer.style.display = 'block';
            } else {
                btnLimpiarContainer.style.display = 'none';
            }
        }
    }

    async verDetallesCompra(codCompra) {
        try {
            const data = await this.service.obtenerDetallesCompra(codCompra);
            this.mostrarModalDetalles(data.compra, data.detalles);
        } catch (error) {
            this.mostrarError('Error al cargar los detalles: ' + error.message);
        }
    }

    mostrarModalDetalles(compra, detalles) {
        let html = `
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="mb-1"><strong>Código de Compra:</strong></h6>
                    <p class="mb-2">${compra.cod_compra}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-1"><strong>Fecha:</strong></h6>
                    <p class="mb-2">${new Date(compra.fecha).toLocaleDateString('es-PE')}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-1"><strong>Proveedor:</strong></h6>
                    <p class="mb-2">${compra.proveedor}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-1"><strong>Registrado por:</strong></h6>
                    <p class="mb-2">${compra.registrado_por}</p>
                </div>
            </div>

            <h6 class="mb-3"><strong>Detalles de Productos:</strong></h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Cajas</th>
                            <th class="text-center">Unidades x Caja</th>
                            <th class="text-center">Total Unidades</th>
                            <th class="text-end">Precio Unitario</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        detalles.forEach(detalle => {
            html += `
                <tr>
                    <td>${detalle.producto_nombre}</td>
                    <td class="text-center">${detalle.cantidad_cajas}</td>
                    <td class="text-center">${detalle.unidades_por_caja}</td>
                    <td class="text-center">${detalle.total_unidades}</td>
                    <td class="text-end">S/ ${parseFloat(detalle.precio_unitario).toFixed(2)}</td>
                    <td class="text-end"><strong>S/ ${parseFloat(detalle.total).toFixed(2)}</strong></td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <td colspan="5" class="text-end"><strong>Total General:</strong></td>
                            <td class="text-end"><strong>S/ ${parseFloat(compra.total_general).toFixed(2)}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;

        document.getElementById('detallesCompraContent').innerHTML = html;
        
        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalDetallesCompra'));
        modal.show();
    }

    aplicarFiltro(filtro) {
        this.cargarHistorial(filtro, this.busquedaActual);
    }

    buscarHistorial() {
        const busqueda = document.getElementById('buscarHistorial').value;
        this.cargarHistorial(this.filtroActual, busqueda);
    }

    limpiarFiltros() {
        this.filtroActual = 'todos';
        this.busquedaActual = '';
        this.cargarHistorial();
    }

 configurarEventos() {
    // Dropdown del usuario
    const dropdownBtn = document.getElementById("dropdownBtn");
    const dropdownList = document.getElementById("dropdownList");
    const arrow = document.getElementById("arrow");

    if (dropdownBtn && dropdownList) {
        dropdownBtn.addEventListener("click", () => {
            const isVisible = dropdownList.style.display === "block";
            dropdownList.style.display = isVisible ? "none" : "block";
            arrow.style.transform = isVisible ? "rotate(0deg)" : "rotate(180deg)";
        });
        
        // Cierra el menú si haces clic fuera
        document.addEventListener("click", (e) => {
            if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
                dropdownList.style.display = "none";
                arrow.style.transform = "rotate(0deg)";
            }
        });
    }

    // Form submission
    const formEntrada = document.getElementById('formEntrada');
    if (formEntrada) {
        formEntrada.addEventListener('submit', (e) => this.guardarEntrada(e));
    }

    // Botón agregar fila - CORREGIDO
    const btnAgregarFila = document.getElementById('btnAgregarFila');
    if (btnAgregarFila) {
        btnAgregarFila.addEventListener('click', () => {
            console.log('🔘 Botón agregar fila clickeado');
            this.agregarFila();
        });
    }

    // Botón cancelar - CORREGIDO
    const btnCancelar = document.getElementById('btnCancelar');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', () => {
            console.log('🔘 Botón cancelar clickeado');
            this.resetForm();
        });
    }

    // Eventos del historial
    this.configurarEventosHistorial();

    // Configurar eventos de todas las filas existentes
    document.querySelectorAll('.product-row').forEach(row => {
        this.configurarEventosFila(row);
    });
}

configurarEventosHistorial() {
    // Dropdown de filtros
    const filterItems = document.querySelectorAll('.filter-dropdown .dropdown-item');
    filterItems.forEach(item => {
        const filtro = item.getAttribute('data-filtro');
        if (filtro) {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                this.aplicarFiltro(filtro);
            });
        }
    });

    // Búsqueda - CORREGIDO
    const btnBuscar = document.getElementById('btnBuscarHistorial');
    const inputBuscar = document.getElementById('buscarHistorial');
    
    if (btnBuscar && inputBuscar) {
        btnBuscar.addEventListener('click', () => this.buscarHistorial());
        inputBuscar.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.buscarHistorial();
            }
        });
    }

    // Limpiar filtros - CORREGIDO
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', (e) => {
            e.preventDefault();
            this.limpiarFiltros();
        });
    }

    // Cargar historial inicial
    this.cargarHistorial();
}

    configurarEventosHistorial() {
        // Dropdown de filtros
        const filterItems = document.querySelectorAll('.filter-dropdown .dropdown-item');
        filterItems.forEach(item => {
            const filtro = item.getAttribute('data-filtro');
            if (filtro) {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.aplicarFiltro(filtro);
                });
            }
        });

        // Búsqueda
        const btnBuscar = document.querySelector('button.btn-primary');
        const inputBuscar = document.getElementById('buscarHistorial');
        
        if (btnBuscar && inputBuscar) {
            btnBuscar.addEventListener('click', () => this.buscarHistorial());
            inputBuscar.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.buscarHistorial();
                }
            });
        }

        // Limpiar filtros
        const btnLimpiar = document.querySelector('.btn-limpiar-filtros button');
        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', (e) => {
                e.preventDefault();
                this.limpiarFiltros();
            });
        }

        // Cargar historial inicial
        this.cargarHistorial();
    }

    configurarEventosFila(row) {
        if (!row) return;

        // Select de producto
        const select = row.querySelector('.product-select');
        if (select) {
            select.addEventListener('change', (e) => this.cargarPrecioProducto(e.target));
        }

        // Inputs de cantidad y precio
        const cantidadInput = row.querySelector('.cantidad-input');
        const precioInput = row.querySelector('.precio-caja-input');
        
        if (cantidadInput) {
            cantidadInput.addEventListener('input', (e) => this.calcularTotalFila(e.target));
        }
        
        if (precioInput) {
            precioInput.addEventListener('input', (e) => this.calcularTotalFila(e.target));
        }

        // Botón eliminar
        const btnEliminar = row.querySelector('.action-btn');
        if (btnEliminar) {
            btnEliminar.addEventListener('click', (e) => {
                e.preventDefault();
                this.eliminarFila(e.target);
            });
        }
    }

    mostrarError(mensaje) {
        console.error(mensaje);
        alert(mensaje);
    }
}

// Variable global para el controlador
let entradaController;

// Inicializar el controlador cuando se carga la página
document.addEventListener('DOMContentLoaded', () => {
    entradaController = new EntradaProveedorController();
});