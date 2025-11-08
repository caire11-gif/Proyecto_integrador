document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/proveedores/cantprove.php')
        .then(function(response){
            if(!response.ok){
                throw new Error("Error con la solicitud");
            }
            
            return response.json();
        })
        .then(function(data1){
            document.getElementById('cantProve').innerHTML=data1.cantidad_proveedor;
        })
        .catch(function(error){
            console.error('Error al cargar el contenido PHP: ',error);
        })
});