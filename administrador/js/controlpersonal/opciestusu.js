document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/controlpersonal/opciestusu.php')
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
            const select = document.getElementById('cambiarEstadoUsuario');
            data.forEach(rol => {
                const option = document.createElement('option');
                option.value = rol.cod_estadousuario;
                option.textContent = rol.nombre;
                select.appendChild(option);
            });
        })
        .catch(function(error){
        console.error('Error al cargar los datos del estado del usuario: ',error);
        })
})