document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/registroventa/promven.php')
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
            document.getElementById('promedioVenta').innerHTML=data.promedio_ventas;
        })
        .catch(function(error){
            console.error('Error al cargar el contenido PHP: ',error);
        })
});