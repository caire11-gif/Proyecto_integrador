//============================================================================================
//                                      OPCIONES
//============================================================================================
//============================================================================================
//                                       ESTADO
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/almacen/notificaciones/php/procesarSeleccionarEstado.php')
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
                const select = document.getElementById('filtroEstado');

                data.forEach(estnoti => {
                    const option = document.createElement('option');
                    option.value = estnoti.cod_estadonotificacion;
                    option.textContent = estnoti.nombre;
                    select.appendChild(option);
                });
            })
            .catch(function(error){
            console.error('Error al cargar los datos de los roles: ',error);
            })
    });

//============================================================================================
//                                      OPCIONES
//============================================================================================
//============================================================================================
//                                     PROVEEDORES
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/almacen/notificaciones/php/procesarSeleccionarProve.php')
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
                const select = document.getElementById('filtroProveedor');

                data.forEach(prove => {
                    const option = document.createElement('option');
                    option.value = prove.cod_proveedor;
                    option.textContent = prove.proveedor_nombre;
                    select.appendChild(option);
                });
            })
            .catch(function(error){
            console.error('Error al cargar los datos de los roles: ',error);
            })
    });

//============================================================================================
//                              SELECCIÓN PARA NOTIFICACIONES
//============================================================================================
document.addEventListener("DOMContentLoaded", getData);

function activarBotonesActualizar() {
    document.querySelectorAll('.btnActualizarEstado').forEach(boton => {
        boton.addEventListener('click', () => {
            const codestnoti = boton.dataset.codestnoti;
            const codnoti=boton.dataset.codnoti;

            Swal.fire({
                title: "¿Estás seguro?",
                text: "Esta acción actualizará el estado de la notificación",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Sí, estoy seguro"
            }).then((result1) => {                
                if (result1.isConfirmed) {
                    window.location.href =
                                `../../controlador/almacen/notificaciones/php/procesarActualizarEstado.php?codestnoti=${encodeURIComponent(codestnoti)}&codnoti=${encodeURIComponent(codnoti)}`;
                }
            });
        });
    });
}

function getData(){
    let formaData=new FormData();
    fetch('../../controlador/almacen/notificaciones/php/procesarSeleccionarNotificacion.php',{
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
            let content = document.getElementById('content');

            content.innerHTML=data.data;

            activarBotonesActualizar();
        })
        .catch(function(error){
            console.error('Error al cargar los datos de las notificaciones: ',error);
        })
}

//============================================================================================
//                                       RESUMEN
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/almacen/notificaciones/php/procesarCantidadProductos.php')
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
                const select1 = document.getElementById('notiAltas');
                const select2 = document.getElementById('notiMedias');
                const select3 = document.getElementById('notiBajas');

                select1.innerHTML=data.cantidad_productos_alto;
                select2.innerHTML=data.cantidad_productos_medio;
                select3.innerHTML=data.cantidad_productos_bajo;
            })
            .catch(function(error){
            console.error('Error al cargar la cantidad de productos: ',error);
            })
    });