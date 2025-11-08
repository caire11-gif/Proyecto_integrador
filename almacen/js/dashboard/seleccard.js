document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/dashboard/seleccard.php')
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
            document.getElementById('cantProd').innerHTML=data.cantidad_producto;
            document.getElementById('cantCate').innerHTML=data.cantidad_categoria;
            document.getElementById('cantMovi').innerHTML=data.cantidad_movimiento;
            document.getElementById('cantProdBajo').innerHTML=data.cantidad_producto_bajo;
        })
        .catch(function(error){
            console.error('Error al cargar el contenido PHP: ',error);
        })
});