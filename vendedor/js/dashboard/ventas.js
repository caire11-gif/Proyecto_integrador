document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/dashboard/ventas.php')
        .then(function(response){
            const contentType = response.headers.get('content-type');
            
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new TypeError("Response wasn't JSON: " + text);
                });
            }

            return response.json();
        })
        .then(ventas => {
            const contenedor = document.getElementById('ventas');
            contenedor.innerHTML = ''; // Limpiar antes de agregar nuevas ventas

            if (ventas.length === 0) {
                const vacio = document.createElement('div');
                vacio.classList.add('empty-ventas');
                vacio.innerHTML = `
                    <i class="fas fa-shopping-cart"></i>
                    <p>No hay ventas hoy</p>
                    <small>Las ventas aparecerán aquí automáticamente</small>
                `;
                contenedor.appendChild(vacio);
                return;
            }

            ventas.forEach(venta => {
                const item = document.createElement('div');
                item.classList.add('venta-item');

                const info = document.createElement('div');
                info.classList.add('venta-info');

                const id = document.createElement('span');
                id.classList.add('venta-id');
                id.textContent = venta.id;

                const fecha = document.createElement('span');
                fecha.classList.add('venta-fecha');
                fecha.textContent = venta.fecha;

                const productos = document.createElement('span');
                productos.classList.add('venta-productos');
                productos.textContent = venta.productos;

                const metodo = document.createElement('span');
                metodo.classList.add('venta-metodo');
                if (venta.metodo_pago === 'mp001') {
                    metodo.classList.add('metodo-efectivo');
                    metodo.innerHTML = '<i class="fas fa-money-bill-wave"></i> Efectivo';
                } else if (venta.metodo_pago === 'mp002') {
                    metodo.classList.add('metodo-tarjeta');
                    metodo.innerHTML = '<i class="fas fa-credit-card"></i> Tarjeta';
                } else if (venta.metodo_pago === 'mp003') {
                    metodo.classList.add('metodo-transferencia');
                    metodo.innerHTML = '<i class="fas fa-mobile-alt"></i> Transferencia';
                }

                const unidades = document.createElement('span');
                unidades.classList.add('venta-unidades');
                unidades.innerHTML = `<i class="fas fa-box"></i> ${venta.cantidad_productos} unidades`;

                const total = document.createElement('span');
                total.classList.add('venta-total');
                total.innerHTML = `S/ `+venta.total;

                info.appendChild(id);
                info.appendChild(fecha);
                info.appendChild(productos);
                info.appendChild(metodo);
                info.appendChild(unidades);

                item.appendChild(info);
                item.appendChild(total);

                contenedor.appendChild(item);
            }); 
        })

        .catch(function(error){
            console.error('Error al cargar el contenido PHP: ',error);
        })
});