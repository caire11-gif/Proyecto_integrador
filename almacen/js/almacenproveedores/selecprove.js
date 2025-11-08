document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/almacenproveedores/selecprove.php')
        .then(response=>{
            const contentType = response.headers.get('content-type');
            
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new TypeError("Response wasn't JSON: " + text);
                });
            }

            return response.json();
        })
        .then(proveedor=>{
            const tabla=document.getElementById('tablaProveedores').getElementsByTagName('tbody')[0];

            proveedor.forEach(proveedor=>{
                const fila=tabla.insertRow();
                const celdaCodigo=fila.insertCell(0);
                const celdaNombre=fila.insertCell(1);
                const celdaTelefono=fila.insertCell(2);
                const celdaDireccion=fila.insertCell(3);

                celdaCodigo.textContent=proveedor.codprove;
                celdaNombre.textContent=proveedor.nombre;
                celdaTelefono.textContent=proveedor.telefono;
                celdaDireccion.textContent=proveedor.direccion;
            });
        })
    .catch(function(error){
        console.error('Error al cargar los datos de los proveedores: ',error);
    })
});