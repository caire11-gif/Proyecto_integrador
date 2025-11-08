document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/proveedores/selecprove.php')
        .then(response=>{
            const contentType = response.headers.get('content-type');
            
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new TypeError("Response wasn't JSON: " + text);
                });
            }

            return response.json();
        })
        .then(proveedor=>{
            const tabla=document.getElementById('tablaProveedores').getElementsByTagName('tbody')[0];

            proveedor.forEach(proveedor=>{
                const fila=tabla.insertRow();
                const celdaCodigo=fila.insertCell(0);
                const celdaNombre=fila.insertCell(1);
                const celdaRuc=fila.insertCell(2);
                const celdaTelefono=fila.insertCell(3);
                const celdaDireccion=fila.insertCell(4);
                const celdaAcciones=fila.insertCell(5);

                celdaCodigo.textContent=proveedor.codprove;
                celdaNombre.textContent=proveedor.nombre;
                celdaRuc.textContent=proveedor.ruc;
                celdaTelefono.textContent=proveedor.telefono;
                celdaDireccion.textContent=proveedor.direccion;

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
                                    window.location.href = `php/proveedores/eliminarprove.php?codprove=${encodeURIComponent(proveedor.codprove)}`;
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
                    document.getElementById('codigoActualizarProveedor').value = proveedor.codprove;
                    document.getElementById('nombreActualizarProveedor').value = proveedor.nombre;
                    document.getElementById('telefonoActualizarProveedor').value = proveedor.telefono;
                    document.getElementById('direccionActualizarProveedor').value = proveedor.direccion;

                    const modal = new bootstrap.Modal(document.getElementById('modalActualizarProveedor'));
                    modal.show();
                });

                celdaAcciones.appendChild(botonActualizar);
                celdaAcciones.appendChild(botonEliminar);
            });
        })
    .catch(function(error){
        console.error('Error al cargar los datos de los proveedores: ',error);
    })
});