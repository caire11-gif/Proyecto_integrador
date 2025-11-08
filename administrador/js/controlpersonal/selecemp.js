document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/controlpersonal/selecemp.php')
        .then(function(response){
            const contentType = response.headers.get('content-type');
            
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new TypeError("Response wasn't JSON: " + text);
                });
            }

            return response.json();
        })
        .then(empleado=>{
            const tabla=document.getElementById('tablaEmpleado').getElementsByTagName('tbody')[0];

            empleado.forEach(empleado=>{
                const fila=tabla.insertRow();

                const celdaCodigo=fila.insertCell(0);
                const celdaNomApe=fila.insertCell(1);
                const celdaDni=fila.insertCell(2);
                const celdaFechaNac=fila.insertCell(3);
                const celdaTelefono=fila.insertCell(4);
                const celdaRol=fila.insertCell(5);
                const celdaAcciones=fila.insertCell(6);

                celdaCodigo.textContent=empleado.cod_empleado;
                celdaNomApe.textContent=empleado.nombre+" "+empleado.apellido;
                celdaDni.textContent=empleado.dni;
                celdaFechaNac.textContent=empleado.fecha_nacimiento;
                celdaTelefono.textContent=empleado.telefono;
                celdaRol.textContent=empleado.rol_nombre;

                const btnActualizar=document.createElement('button');
                btnActualizar.innerHTML = "<i class='fas fa-edit'></i>";
                btnActualizar.classList.add('btn', 'btn-primary', 'me-1');

                btnActualizar.addEventListener('click', ()=>{
                    document.getElementById('codigoActualizarEmpleado').value=empleado.cod_empleado;
                    document.getElementById('nombreActualizarEmpleado').value=empleado.nombre;
                    document.getElementById('apellidoActualizarEmpleado').value=empleado.apellido;
                    document.getElementById('dniActualizarEmpleado').value=empleado.dni;
                    document.getElementById('fechaNacActualizarEmpleado').value=empleado.fecha_nacimiento;
                    document.getElementById('telefonoActualizarEmpleado').value=empleado.telefono;

                    const modal = new bootstrap.Modal(document.getElementById('modalActualizarEmpleado'));
                    modal.show();
                });

                const btnEliminar=document.createElement('button');
                btnEliminar.innerHTML="<i class='fas fa-trash'></i>";
                btnEliminar.classList.add('btn', 'btn-danger', 'me-2');

                btnEliminar.addEventListener('click', ()=>{
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
                                    window.location.href = `php/controlpersonal/eliminaremp.php?cod_empleado=${encodeURIComponent(empleado.cod_empleado)}`;
                                }
                            });
                        }
                    });
                });

                celdaAcciones.appendChild(btnActualizar);
                celdaAcciones.appendChild(btnEliminar);
            })
        })
});