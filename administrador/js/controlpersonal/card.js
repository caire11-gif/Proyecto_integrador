document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/controlpersonal/card.php')
        .then(function(response){
            if(!response.ok){
                throw new Error("Error con la solicitud");
            }
            
            return response.json();
        })
        .then(function(data){
            document.getElementById('cantEmp').innerHTML=data.cantidad_empleado;
            document.getElementById('cantUsu').innerHTML=data.cantidad_usuario;
        })
        .catch(function(error){
            console.error('Error al cargar el contenido PHP: ',error);
        })
});