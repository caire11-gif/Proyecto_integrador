document.addEventListener('DOMContentLoaded', function() {
    calcularCostoUnitario();
    validarPrecioVenta();
        
    calcularCostoUnitarioEdit();
    validarPrecioVentaEdit();
});

//============================================================================================
//                                        USUARIOS
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function() {
        // Realizar la llamada AJAX al archivo PHP
        fetch('../../modelo/almacen/usuarioinfo.php')
            .then(function(response){
                const contentType = response.headers.get('content-type');
                
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new TypeError("Response wasn't JSON: " + text);
                    });
                }

                return response.json();
            })
            .then(function(data1){
                document.getElementById('usuarioAvatar').innerHTML = data1.iniciales;
                document.getElementById('userName').innerHTML=data1.nombre_apellido;
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });

//============================================================================================
//                                      DROPDOWN
//============================================================================================
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

//============================================================================================
//                                      BARRA LATERAL
//============================================================================================
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const barraLateral = document.getElementById('barra-lateral');
        
    if(mobileMenuBtn && barraLateral) {
        mobileMenuBtn.addEventListener('click', () => {
            barraLateral.classList.toggle('mobile-open');
        });
    }

//============================================================================================
//                                         FUNCIONES
//============================================================================================
//============================================================================================
//                                  CALCULAR COSTO UNITARIO
//============================================================================================
    function calcularCostoUnitario() {
        const precioCosto = parseFloat(document.getElementById('precioCosto').value) || 0;
        const unidadesCaja = parseInt(document.getElementById('unidadesCaja').value) || 1;
            
        if (unidadesCaja > 0 && precioCosto > 0) {
            const costoUnitario = precioCosto / unidadesCaja;
            document.getElementById('costoUnitario').value = 'S/ ' + costoUnitario.toFixed(2);
            document.getElementById('costoUnitario').style.backgroundColor = '#e8f5e8';
            return costoUnitario;
        } else {
            document.getElementById('costoUnitario').value = '';
            document.getElementById('costoUnitario').style.backgroundColor = '';
            return 0;
        }
    }

//============================================================================================
//                              CALCULAR COSTO UNITARIO EDIT
//============================================================================================
    function calcularCostoUnitarioEdit() {
        const precioCosto = parseFloat(document.getElementById('precioCostoEdit').value) || 0;
        const unidadesCaja = parseInt(document.getElementById('unidadesCajaEdit').value) || 1;
            
        if (unidadesCaja > 0 && precioCosto > 0) {
            const costoUnitario = precioCosto / unidadesCaja;
            document.getElementById('costoUnitarioEdit').value = 'S/ ' + costoUnitario.toFixed(2);
            document.getElementById('costoUnitarioEdit').style.backgroundColor = '#e8f5e8';
            return costoUnitario;
        } else {
            document.getElementById('costoUnitarioEdit').value = '';
            document.getElementById('costoUnitarioEdit').style.backgroundColor = '';
            return 0;
        }
    }

//============================================================================================
//                               VALIDAR PRECIO VENTA
//============================================================================================
    function validarPrecioVenta() {
        const costoUnitario = calcularCostoUnitario();
        const precioVentaInput = document.getElementById('precioVenta');
        const precioVenta = parseFloat(precioVentaInput.value) || 0;
        const mensaje = document.getElementById('mensajePrecioVenta');
            
        if (costoUnitario > 0 && precioVenta > 0) {
            if (precioVenta > costoUnitario) {
                const margen = ((precioVenta - costoUnitario) / costoUnitario * 100).toFixed(1);
                mensaje.innerHTML = `<span class="text-success"><i class="fas fa-check-circle"></i> Margen: ${margen}%</span>`;
                precioVentaInput.style.borderColor = '#198754';
            } else if (precioVenta === costoUnitario) {
                mensaje.innerHTML = `<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Sin margen de ganancia</span>`;
                precioVentaInput.style.borderColor = '#ffc107';
            } else {
                mensaje.innerHTML = `<span class="text-danger"><i class="fas fa-times-circle"></i> El precio debe ser mayor al costo unitario (S/ ${costoUnitario.toFixed(2)})</span>`;
                precioVentaInput.style.borderColor = '#dc3545';
            }
        } else {
            mensaje.innerHTML = '';
            precioVentaInput.style.borderColor = '';
        }
    }

//============================================================================================
//                             VALIDAR PRECIO VENTA EDIT
//============================================================================================
    function validarPrecioVentaEdit() {
        const costoUnitario = calcularCostoUnitarioEdit();
        const precioVentaInput = document.getElementById('precioVentaEdit');
        const precioVenta = parseFloat(precioVentaInput.value) || 0;
        const mensaje = document.getElementById('mensajePrecioVentaEdit');
            
        if (costoUnitario > 0 && precioVenta > 0) {
            if (precioVenta > costoUnitario) {
                const margen = ((precioVenta - costoUnitario) / costoUnitario * 100).toFixed(1);
                mensaje.innerHTML = `<span class="text-success"><i class="fas fa-check-circle"></i> Margen: ${margen}%</span>`;
                precioVentaInput.style.borderColor = '#198754';
            } else if (precioVenta === costoUnitario) {
                mensaje.innerHTML = `<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Sin margen de ganancia</span>`;
                precioVentaInput.style.borderColor = '#ffc107';
            } else {                    
                mensaje.innerHTML = `<span class="text-danger"><i class="fas fa-times-circle"></i> El precio debe ser mayor al costo unitario (S/ ${costoUnitario.toFixed(2)})</span>`;
                precioVentaInput.style.borderColor = '#dc3545';
            }
        } else {
            mensaje.innerHTML = '';
            precioVentaInput.style.borderColor = '';
        }
    }

//============================================================================================
//                             CONFIRMACIÓN DE ELIMINAR
//============================================================================================
    function confirmarEliminacion(cod_producto, nombre_producto) {
        Swal.fire({
            title: '¿Eliminar Producto?',
            html: `<div class="text-start">
                <p><strong>Código:</strong> ${cod_producto}</p>
                <p><strong>Nombre:</strong> ${nombre_producto}</p>
                <p class="text-danger mt-3">
                    <i class="fas fa-exclamation-triangle"></i> Esta acción eliminará:
                </p>
                <ul class="text-danger small">
                    <li>El producto</li>
                    <li>Su historial de movimientos</li>
                    <li>Detalles en compras y ventas</li>
                    <li>Registros de inventario</li>
                    <li>Notificaciones relacionadas</li>
                </ul>
                <p class="text-danger"><strong>¡Esta acción no se puede deshacer!</strong></p>
            </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="fas fa-trash"></i> Sí, eliminar todo',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
            width: 500
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirigir para eliminar
                window.location.href = '?eliminar=' + cod_producto;
            }
        });
    }

//============================================================================================
//                                  LIMPIAR FORMULARIO
//============================================================================================

function limpiarFormulario() {
    document.getElementById('codigoProducto').value = '';
    document.getElementById('nombreProducto').value = '';
    document.getElementById('precioCosto').value = '';
    document.getElementById('precioVenta').value = '';
    document.getElementById('unidadesCaja').value = '';
    document.getElementById('costoUnitario').value = '';
    document.getElementById('costoUnitario').style.backgroundColor = '';
    document.getElementById('mensajePrecioVenta').innerHTML = '';
    document.getElementById('precioVenta').style.borderColor = '';
    document.getElementById('stockProducto').value = '0';
    document.getElementById('categoriaProducto').selectedIndex = 0;
    document.getElementById('proveedorProducto').selectedIndex = 0;
}

//============================================================================================
//                                          FILTROS
//============================================================================================
//============================================================================================
//                                        PARA BÚSQUEDA
//============================================================================================
    document.getElementById('btnLimpiarFiltros').addEventListener('click', function() {
        document.getElementById('buscarProducto').value = '';
        document.getElementById('filtroCategoria').selectedIndex = 0;
        document.getElementById('filtroProveedor').selectedIndex = 0;
        aplicarFiltros();
    });

    document.getElementById('buscarProducto').addEventListener('input', aplicarFiltros);
    document.getElementById('filtroCategoria').addEventListener('change', aplicarFiltros);
    document.getElementById('filtroProveedor').addEventListener('change', aplicarFiltros);

    function aplicarFiltros() {
        const searchTerm = document.getElementById('buscarProducto').value.toLowerCase();
        const categoriaValue = document.getElementById('filtroCategoria').value;
        const proveedorValue = document.getElementById('filtroProveedor').value;
            
        const categoriaText = categoriaValue ? 
            document.getElementById('filtroCategoria').options[document.getElementById('filtroCategoria').selectedIndex].textContent : '';
        const proveedorText = proveedorValue ? 
            document.getElementById('filtroProveedor').options[document.getElementById('filtroProveedor').selectedIndex].textContent : '';
            
        const rows = document.querySelectorAll('tbody tr');
        let visibleCount = 0;
            
        rows.forEach(row => {
            if (row.cells.length < 8) return;
                
            const nombreProducto = row.cells[1].textContent.toLowerCase();
            const codigoProducto = row.cells[0].textContent.toLowerCase();
            const categoriaProducto = row.cells[7].textContent; // Cambié de 6 a 7 porque agregué columna
            const proveedorProducto = row.cells[8].textContent; // Cambié de 7 a 8
                
            const matchSearch = !searchTerm || nombreProducto.includes(searchTerm) || codigoProducto.includes(searchTerm);
            const matchCategoria = !categoriaValue || categoriaProducto === categoriaText;
            const matchProveedor = !proveedorValue || proveedorProducto === proveedorText;
                
            if (matchSearch && matchCategoria && matchProveedor) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
            
        // Actualizar contador
        const totalLabel = document.getElementById('lbl-total');
        if (totalLabel) {
            totalLabel.innerHTML = `<i class='fas fa-cube me-1'></i> Mostrando: <strong>${visibleCount}</strong> de <strong>${rows.length}</strong> productos`;
        }
    }