document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/gestionproductos/selecprod.php')
        .then(response=>{
            const contentType = response.headers.get('content-type');
            
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new TypeError("Response wasn't JSON: " + text);
                });
            }

            return response.json();
        })
        .then(producto=>{
            const tabla=document.getElementById('tablaProductos').getElementsByTagName('tbody')[0];

            producto.forEach(producto=>{
                const fila=tabla.insertRow();
                
                const celdaCodigo=fila.insertCell(0);
                const celdaNomProd=fila.insertCell(1);
                const celdaPreCosto=fila.insertCell(2);
                const celdaPreVenta=fila.insertCell(3);
                const celdaStock=fila.insertCell(4);
                const celdaUniCaja=fila.insertCell(5);
                const celdaNomCate=fila.insertCell(6);
                const celdaNomProve=fila.insertCell(7);
                const celdaAcciones=fila.insertCell(8);

                celdaCodigo.textContent=producto.codigo_producto;
                celdaNomProd.textContent=producto.producto_nombre;
                celdaPreCosto.textContent=producto.precio_costo;
                celdaPreVenta.textContent=producto.precio_venta;
                celdaStock.textContent=producto.stock;
                celdaUniCaja.textContent=producto.unidades_caja;
                celdaNomCate.textContent=producto.categoria_nombre;
                celdaNomProve.textContent=producto.proveedor_nombre;

                const botonEliminar = document.createElement('button');
                botonEliminar.innerHTML ="<i class='fas fa-trash'></i>";
                botonEliminar.classList.add('btn', 'btn-danger', 'me-2'); // estilos Bootstrap opcionales

                botonEliminar.addEventListener('click', () => {
                    Swal.fire({
                        title: "¿Estás seguro?",
                        text: "Esta acción no se puede deshacer",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Si, estoy seguro!"
                    }).then((result1) => {
                        if (result1.isConfirmed) {
                            Swal.fire({
                                title: "¿Enserio?",
                                text: "¿Realmente está seguro?.",
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonColor: "#3085d6",
                                cancelButtonColor: "#d33",
                                confirmButtonText: "Realmente estoy seguro"
                            }).then((result2)=>{
                                if(result2.isConfirmed){
                                    window.location.href = `php/gestionproductos/eliminarprod.php?codigo_producto=${encodeURIComponent(producto.codigo_producto)}`;
                                }
                            });
                        }
                    });
                });

                // ---- Botón Actualizar ----
                const botonActualizar = document.createElement('button');
                
                botonActualizar.innerHTML = "<i class='fas fa-edit'></i>"
                botonActualizar.classList.add('btn', 'btn-primary', 'me-1');

                botonActualizar.addEventListener('click', () => {
                    document.getElementById('codigoActualizarProducto').value = producto.codigo_producto;
                    document.getElementById('nombreActualizarProducto').value = producto.producto_nombre;
                    document.getElementById('precioCostoActualizarProducto').value = producto.precio_costo;
                    document.getElementById('precioVentaActualizarProducto').value = producto.precio_venta;
                    document.getElementById('unidadesCajaActualizarProducto').value=producto.unidades_caja;
                    document.getElementById('stockActualizarProducto').value=producto.stock;

                    const modal = new bootstrap.Modal(document.getElementById('modalActualizarProducto'));
                    modal.show();
                });

                celdaAcciones.appendChild(botonActualizar);
                celdaAcciones.appendChild(botonEliminar);
            });
        })
        .catch(function(error){
        console.error('Error al cargar los datos de los productos: ',error);
    })
});