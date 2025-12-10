//======================================================================================================
//                                        SELECCIONAR CARDS
//======================================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/administrador/dashboard/php/procesarSeleccionarCards.php')
            .then(function(response){
                if(!response.ok){
                    throw new Error("Error con la solicitud");
                }

                return response.json();
            })
            .then(function(data){
                document.getElementById('entradasMes').innerHTML=data.cantidad_entradas;
                document.getElementById('salidasMes').innerHTML=data.cantidad_salidas;
                document.getElementById('devolucionesMes').innerHTML=data.cantidad_devoluciones;
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });
//======================================================================================================
//                                       USUARIOS ACTIVOS
//======================================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/administrador/dashboard/php/procesarSeleccionarConUsu.php')
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
                let content=document.getElementById('personal-activo');

                content.innerHTML=data.data;
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });

//======================================================================================================
//                                      ÚLTIMOS REGISTROS
//======================================================================================================
//======================================================================================================
//                                      ÚLTIMAS ENTRADAS
//======================================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/administrador/dashboard/php/procesarSeleccionarEntradas.php')
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
                let content=document.getElementById('ultimas-entradas');

                content.innerHTML=data.data;
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });
    
//======================================================================================================
//                                      ÚLTIMAS SALIDAS
//======================================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/administrador/dashboard/php/procesarSeleccionarSalidas.php')
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
                let content=document.getElementById('ultimas-salidas');

                content.innerHTML=data.data;
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });

//======================================================================================================
//                                      ÚLTIMAS DEVOLUCIONES
//======================================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/administrador/dashboard/php/procesarSeleccionDevoluciones.php')
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
                let content=document.getElementById('ultimas-devoluciones');

                content.innerHTML=data.data;
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });