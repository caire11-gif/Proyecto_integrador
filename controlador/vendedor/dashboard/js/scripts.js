//============================================================================================
//                                          CARDS
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/vendedor/dashboard/php/procesarSeleccionarCards.php')
            .then(function(response){
                const contentType = response.headers.get('content-type');
                
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new TypeError("Response wasn't JSON: " + text);
                    });
                }

                return response.json();
            })
            .then(data=>{
                const select1=document.getElementById('ventasHoy');
                const select2=document.getElementById('totalVendido');
                const select3=document.getElementById('productosVendidos');

                select1.innerHTML=data.total_ventas;
                select2.innerHTML=data.total_vendido;
                select3.innerHTML=data.total_productos;
            })
            .catch(function(error){
            console.error('Error al cargar los datos de las ventas: ',error);
            })
    });

//============================================================================================
//                                  SELECCIÓN PARA VENTAS
//============================================================================================
    document.addEventListener("DOMContentLoaded", getData);    

    function getData(){
        let formaData=new FormData();
        fetch('../../controlador/vendedor/dashboard/php/procesarSeleccionarVentas.php',{
                method: "POST",
                body: formaData
            })
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
                let content = document.getElementById('listaVentasRecientes');

                content.innerHTML=data.data;
            })
            .catch(function(error){
                console.error('Error al cargar los datos de las ventas: ',error);
            })
    }