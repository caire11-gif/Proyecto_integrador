document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/dashboard/consusu.php')
        .then(function(response){
            const contentType = response.headers.get('content-type');
            
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new TypeError("Response wasn't JSON: " + text);
                });
            }

            return response.json();
        })
        .then(function(data){
            document.getElementById('usuEmp').innerHTML=data.nombre_empleado+" "+data.apellido_empleado;
            document.getElementById('rolEmp').innerHTML=data.nombre_rol;
            document.getElementById('estUsuEmp').innerHTML=data.nombre_estadousuario;
        })
        .catch(function(error){
            console.error('Error al cargar el contenido PHP: ',error);
        })
});