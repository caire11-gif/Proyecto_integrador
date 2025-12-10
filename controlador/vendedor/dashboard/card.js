document.addEventListener('DOMContentLoaded', async function(){
    fetch('')
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
            document.getElementById('ventasHoy').innerHTML=data.ventas_hoy;
            document.getElementById('totalVendido').innerHTML=data.total_vendido;
            document.getElementById('productosVendidos').innerHTML=data.productos_vendidos;
        })
        .catch(function(error){
            console.error('Error al cargar el contenido PHP: ',error);
        })
});