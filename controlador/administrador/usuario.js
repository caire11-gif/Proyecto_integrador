document.addEventListener('DOMContentLoaded', async function() {
    // Realizar la llamada AJAX al archivo PHP
    fetch('../../modelo/administrador/usuarioinfo.php')
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