document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/registroventa/selecregven.php')
        .then(response=>{
            const contentType = response.headers.get('content-type');
            
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new TypeError("Response wasn't JSON: " + text);
                });
            }

            return response.json();
        })
        .then(regven=>{
            const tabla=document.getElementById('tablaRegistroVenta').getElementsByTagName('tbody')[0];

            regven.forEach(regven=>{
                const fila=tabla.insertRow();
                const celdaCodigo=fila.insertCell(0);
                const celdaFecha=fila.insertCell(1);
                const celdaVendedor=fila.insertCell(2);
                const celdaProducto=fila.insertCell(3);
                const celdaTotal=fila.insertCell(4);

                celdaCodigo.textContent=regven.codigo_venta;
                celdaFecha.textContent=regven.fecha_venta;
                celdaVendedor.textContent=regven.usuario_nombre;
                celdaProducto.textContent=regven.cantidad_ventas;
                celdaTotal.textContent=regven.total_ventas;
            });
        })
    .catch(function(error){
        console.error('Error al cargar los datos de los proveedores: ',error);
    })
});