document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/proveedores/cantmovi.php')
        .then(function(response){
            if(!response.ok){
                throw new Error("Error con la solicitud");
            }
            
            return response.json();
        })
        .then(function(data){
            document.getElementById('cantMovi').innerHTML=data.cantidad_movimiento;
        })
        .catch(function(error){
            console.error('Error al cargar el contenido PHP: ',error);
        })
});