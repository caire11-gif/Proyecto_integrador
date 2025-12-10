// Array con precios de productos (se llena con PHP)
        

        // Variable para almacenar el código de compra actual
        let codCompraActual = '';

        // NUEVA FUNCIÓN: Filtrar productos según el proveedor seleccionado
        function filtrarProductosPorProveedor() {
            const proveedorSelect = document.getElementById('proveedorSelect');
            const proveedorId = proveedorSelect.value;
            
            // Obtener todos los selects de productos
            const productSelects = document.querySelectorAll('.product-select');
            
            productSelects.forEach(select => {
                // Limpiar opciones actuales
                select.innerHTML = '<option value="">Seleccione producto</option>';
                
                if (proveedorId && productosPorProveedor[proveedorId]) {
                    // Agregar productos del proveedor seleccionado
                    productosPorProveedor[proveedorId].forEach(producto => {
                        const option = document.createElement('option');
                        option.value = producto.cod_producto;
                        option.textContent = producto.nombre;
                        option.setAttribute('data-precio', producto.precio_caja);
                        option.setAttribute('data-unidades', producto.unidades_por_caja);
                        select.appendChild(option);
                    });
                } else if (!proveedorId) {
                    select.innerHTML = '<option value="">Seleccione proveedor primero</option>';
                } else {
                    select.innerHTML = '<option value="">Este proveedor no tiene productos</option>';
                }
            });
            
            // Resetear precios y cálculos
            resetearCalculos();
        }

        function resetearCalculos() {
            document.querySelectorAll('.product-row').forEach(row => {
                row.querySelector('.precio-caja-input').value = '0.00';
                row.querySelector('.unidades-caja').textContent = '0';
                row.querySelector('.total-unidades').textContent = '0';
                row.querySelector('.total-producto').textContent = 'S/ 0.00';
            });
            calcularTotalGeneral();
        }

        // Funciones JavaScript para la interactividad
        function cargarPrecioProducto(selectElement) {
            const productoId = selectElement.value;
            const row = selectElement.closest('tr');
            const precioInput = row.querySelector('.precio-caja-input');
            const unidadesCell = row.querySelector('.unidades-caja');
            
            if (productoId && preciosProductos[productoId]) {
                precioInput.value = parseFloat(preciosProductos[productoId]).toFixed(2);
                unidadesCell.textContent = unidadesPorCaja[productoId] || 0;
                calcularTotalFila(selectElement);
            } else {
                precioInput.value = '0.00';
                unidadesCell.textContent = '0';
                row.querySelector('.total-unidades').textContent = '0';
                row.querySelector('.total-producto').textContent = 'S/ 0.00';
            }
        }

        function calcularTotalFila(inputElement) {
            const row = inputElement.closest('tr');
            const cantidadCajas = parseFloat(row.querySelector('.cantidad-input').value) || 0;
            const precioPorCaja = parseFloat(row.querySelector('.precio-caja-input').value) || 0;
            const productoSelect = row.querySelector('.product-select');
            const productoId = productoSelect.value;
            const totalUnidadesCell = row.querySelector('.total-unidades');
            
            // Calcular total basado en PRECIO POR CAJA (CORRECTO)
            const unidadesPorCajaProducto = unidadesPorCaja[productoId] || 1;
            const cantidadUnidades = cantidadCajas * unidadesPorCajaProducto;
            const total = cantidadCajas * precioPorCaja; // Total = cantidad_cajas * precio_por_caja
            
            // Actualizar todas las celdas
            totalUnidadesCell.textContent = cantidadUnidades;
            row.querySelector('.total-producto').textContent = 'S/ ' + total.toFixed(2);
            calcularTotalGeneral();
        }

        function calcularTotalGeneral() {
            let totalGeneral = 0;
            document.querySelectorAll('.product-row').forEach(row => {
                const totalTexto = row.querySelector('.total-producto').textContent;
                const total = parseFloat(totalTexto.replace('S/ ', '')) || 0;
                totalGeneral += total;
            });
            
            document.getElementById('totalGeneral').textContent = 'S/ ' + totalGeneral.toFixed(2);
        }

        function agregarFila() {
            const tbody = document.getElementById('detallesEntrada');
            const newRow = document.createElement('tr');
            newRow.className = 'product-row';
            newRow.innerHTML = `
                <td>        
                    <select class="form-select product-select" name="productos[]" required onchange="cargarPrecioProducto(this)">
                        <option value="">Seleccione proveedor primero</option>
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control cantidad-input" name="cantidades[]" value="1" min="1" required onchange="calcularTotalFila(this)">
                </td>
                <td class="unidades-caja text-center">0</td>
                <td class="total-unidades text-center">0</td>
                <td>
                    <input type="number" class="form-control precio-caja-input" name="precios_caja[]" value="0.00" step="0.01" min="0" required onchange="calcularTotalFila(this)">
                </td>
                <td class="total-producto">S/ 0.00</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger action-btn" onclick="eliminarFila(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(newRow);
            actualizarContadorProductos();
            
            // Si hay un proveedor seleccionado, cargar sus productos en la nueva fila
            const proveedorSelect = document.getElementById('proveedorSelect');
            if (proveedorSelect.value) {
                filtrarProductosPorProveedor();
            }
        }

        function eliminarFila(button) {
            const row = button.closest('tr');
            if (document.querySelectorAll('.product-row').length > 1) {
                row.remove();
                actualizarContadorProductos();
                calcularTotalGeneral();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Faltan productos',
                    text: 'Debe haber al menos un producto en la entrada',
                    width: '350px'
                })
            }
        }

        function actualizarContadorProductos() {
            const count = document.querySelectorAll('.product-row').length;
            document.getElementById('contadorProductos').textContent = count + ' producto(s)';
        }

        function resetForm() {
            document.getElementById('formEntrada').reset();
            // Resetear totales
            document.querySelectorAll('.product-row').forEach(row => {
                row.querySelector('.total-producto').textContent = 'S/ 0.00';
                row.querySelector('.unidades-caja').textContent = '0';
                row.querySelector('.total-unidades').textContent = '0';
            });
            document.getElementById('totalGeneral').textContent = 'S/ 0.00';
            actualizarContadorProductos();
            
            // Resetear selects de productos
            const productSelects = document.querySelectorAll('.product-select');
            productSelects.forEach(select => {
                select.innerHTML = '<option value="">Seleccione proveedor primero</option>';
            });
        }

        // Funciones para el filtrado del historial
        function aplicarFiltro(filtro) {
            const url = new URL(window.location.href);
            url.searchParams.set('filtro', filtro);
            url.searchParams.set('tab', 'historialEntradas');
            const busqueda = document.getElementById('buscarHistorial').value;
            if (busqueda) {
                url.searchParams.set('busqueda', busqueda);
            }
            window.location.href = url.toString();
        }

        

        // Función para generar PDF con formato de boleta
        function generarPdf(codCompra) {
            window.open('obtener_detalles_compra.php?cod_compra=' + codCompra + '&pdf=1', '_blank');
        }

        // Función para descargar PDF desde el modal
        function descargarPDFCompra() {
            if (codCompraActual) {
                generarPdf(codCompraActual);
            } else {
                // Intentar extraer el código de compra del contenido del modal
                const content = document.getElementById('detallesCompraContent');
                const text = content.innerText || content.textContent;
                
                // Buscar patrones de código de compra
                const codMatch = text.match(/COM\d+/);
                if (codMatch) {
                    generarPdf(codMatch[0]);
                } else {
                    alert('No se pudo obtener el código de compra');
                }
            }
        }

        // Función para ver detalles de compra (llamada desde el historial)
        function verDetallesCompra(codCompra) {
            // Guardar el código actual
            codCompraActual = codCompra;
            
            // Cargar detalles
            fetch(`obtener_detalles_compra.php?cod_compra=${codCompra}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detallesCompraContent').innerHTML = html;
                    
                    // Mostrar modal
                    const modal = new bootstrap.Modal(document.getElementById('modalDetallesCompra'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los detalles de la compra');
                });
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            actualizarContadorProductos();
            calcularTotalGeneral();
            
            // Configurar búsqueda en tiempo real
            const buscarHistorial = document.getElementById('buscarHistorial');
            if (buscarHistorial) {
                buscarHistorial.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        document.getElementById('formBusqueda').submit();
                    }
                });
            }
        });

//#################################################################################################

/*ENTRADAS*/
