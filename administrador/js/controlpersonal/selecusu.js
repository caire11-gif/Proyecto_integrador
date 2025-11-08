document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/controlpersonal/selecusu.php')
        .then(function(response){
            const contentType = response.headers.get('content-type');

            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new TypeError("Response wasn't JSON: " + text);
                });
            }

            return response.json();
        })
        .then(usuario=>{
            const tabla=document.getElementById('tablaUsuario').getElementsByTagName('tbody')[0];

            usuario.forEach(usuario=>{
                const fila=tabla.insertRow();

                const celdaCodUsuario=fila.insertCell(0);
                const celdaCodEmpleado=fila.insertCell(1);
                const celdaUsuario=fila.insertCell(2);
                const celdaContraseña=fila.insertCell(3);
                const celdaEstadoUsuario=fila.insertCell(4);
                const celdaAcciones=fila.insertCell(5);
                
                celdaCodUsuario.textContent=usuario.cod_usuario;
                celdaCodEmpleado.textContent=usuario.cod_empleado;
                celdaUsuario.textContent=usuario.usuario;
                celdaContraseña.textContent=usuario.contraseña;
                celdaEstadoUsuario.textContent=usuario.estado_usuario;

                const btnActualizar=document.createElement('button');
                btnActualizar.innerHTML = "<i class='fas fa-edit'></i>";
                btnActualizar.classList.add('btn', 'btn-primary');

                btnActualizar.addEventListener('click', ()=>{
                    document.getElementById('codigoUsuario').value=usuario.cod_usuario;

                    const modal = new bootstrap.Modal(document.getElementById('modalActualizarUsuario'));
                    modal.show();
                });

                const btnCambiarContraseña=document.createElement('button');
                btnCambiarContraseña.innerHTML="<i class='fas fa-key'></i>";
                btnCambiarContraseña.classList.add('btn', 'btn-warning', 'me-1');

                btnCambiarContraseña.addEventListener('click', ()=>{
                    document.getElementById('codigoActualizarUsuario').value=usuario.cod_usuario;
                    document.getElementById('contraseñaActual').value=usuario.contraseña;

                    const modal=new bootstrap.Modal(document.getElementById('modalCambiarContraseña'));
                    modal.show();
                });

                celdaAcciones.appendChild(btnCambiarContraseña);
                celdaAcciones.appendChild(btnActualizar);
            })
        })
})