//============================================================================================
//                                          OPCIONES
//============================================================================================
//============================================================================================
//                                     PARA PROVEEDORES
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/almacen/entradaproveedor/php/procesarSeleccionProve.php')
            .then(function(response){
                const contentType = response.headers.get('content-type');
                
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new TypeError("Response wasn't JSON: " + text);
                    });
                }

                return response.json();
            })
            .then(data => {
                const select = document.getElementById('proveedorSelect');

                data.forEach(prove => {
                    const option = document.createElement('option');
                    option.value = prove.cod_proveedor;
                    option.textContent = prove.proveedor_nombre;
                    select.appendChild(option);
                });
            })
            .catch(function(error){
            console.error('Error al cargar los datos de los proveedores: ',error);
            })
    });

//============================================================================================
//                                     PARA PRODUCTOS
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/almacen/entradaproveedor/php/procesarSeleccionProducto.php')
            .then(function(response){
                const contentType = response.headers.get('content-type');
                
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new TypeError("Response wasn't JSON: " + text);
                    });
                }

                return response.json();
            })
            .then(data => {
                const proveedorSelect = document.getElementById('proveedorSelect');
                const proveedorId = proveedorSelect.value;
            
                productosPorProveedor=data;

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
            })
            .catch(function(error){
            console.error('Error al cargar los datos de los productos: ',error);
            })
    });

//============================================================================================
//                                      PARA PRECIOS
//============================================================================================
    document.addEventListener("DOMContentLoaded", cargarPrecioProducto);
    function cargarPrecioProducto(selecElement){
        const selectElement=selecElement;
        fetch('../../controlador/almacen/entradaproveedor/php/procesarSeleccionPrecio.php')
            .then(function(response){
                const contentType = response.headers.get('content-type');
                
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new TypeError("Response wasn't JSON: " + text);
                    });
                }

                return response.json();
            })
            .then(data => {
                const productoId = document.getElementById('precioProducto');
                const row = selectElement.closest('tr');
                const precioInput = row.querySelector('.precio-caja-input');
                const unidadesCell = row.querySelector('.unidades-caja');
            
                preciosProductos=data;

                // Obtener todos los selects de productos
                const productSelects = document.querySelectorAll('.product-select');
                

                productSelects.forEach(select => {
                // Limpiar opciones actuales
                select.innerHTML = '<option value="">Seleccione producto</option>';
                
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
            });
            })
            .catch(function(error){
            console.error('Error al cargar los datos de los productos: ',error);
            })
    }