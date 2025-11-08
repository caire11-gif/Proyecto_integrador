document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/nuevaventa/productos.php')
        .then(response=>{
            const contentType = response.headers.get('content-type');
            
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new TypeError("Response wasn't JSON: " + text);
                });
            }

            return response.json();
        })
        .then(producto=>{
            const tabla=document.getElementById('tablaProductos').getElementsByTagName('tbody')[0];
            let productosVenta=[];
            let metodoPagoSeleccionado = 'mp001';
            let tipoDocumentoSeleccionado = 'boleta';
            let subtotal = 0;
            let igv = 0;
            let total = 0;

            producto.forEach(producto=>{
                const fila=tabla.insertRow();

                const celdaCodigo=fila.insertCell(0);
                const celdaNomProd=fila.insertCell(1);
                const celdaPreVenta=fila.insertCell(2);
                const celdaStock=fila.insertCell(3);
                const celdaAcciones=fila.insertCell(4);

                celdaCodigo.textContent=producto.codigo_producto;
                celdaNomProd.textContent=producto.nombre_producto;
                celdaPreVenta.textContent=producto.precio_venta;
                celdaStock.textContent=producto.stock;

                const botonAgregar=document.createElement('button');
                botonAgregar.classList.add('btn', 'btn-sm', 'btn-primary');
                botonAgregar.innerHTML='<i class="fas fa-plus"></i> Agregar';

                botonAgregar.addEventListener('click', ()=>{
                    const codigo=producto.codigo_producto;
                    const nombre=producto.nombre_producto;
                    const precio=producto.precio_venta;
                    const stock=producto.stock;

                    agregarProducto(codigo,nombre,precio,stock);
                })

                celdaAcciones.appendChild(botonAgregar);
            });
        })
        .catch(function(error){
        console.error('Error al cargar los datos de los productos: ',error);
    })
});

