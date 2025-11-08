document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/dashboard/result1.php')
        .then(function(response){
            if(!response.ok){
                throw new Error("Error con la solicitud");
            }

            return response.json();
        })
        .then(function(data1){
            document.getElementById('ventasMes').innerHTML=data1.total;
        })
        .catch(function(error){
            console.error('Error al cargar el contenido PHP: ',error);
        })
});