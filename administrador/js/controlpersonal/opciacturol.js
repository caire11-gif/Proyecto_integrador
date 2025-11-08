document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/controlpersonal/opcirol.php')
        .then(function(response){
            const contentType = response.headers.get('content-type');
            
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new TypeError("Response wasn't JSON: " + text);
                });
            }

            return response.json();
        })
        .then(data1=>{
            const select1=document.getElementById('rolActualizarEmpleado');
            data1.forEach(rol=>{
                const option1=document.createElement('option');
                option1.value=rol.cod_rol;
                option1.textContent=rol.nombre;
                select1.appendChild(option1);
            });
        })
        .catch(function(error){
        console.error('Error al cargar los datos de los proveedores: ',error);
        })
})