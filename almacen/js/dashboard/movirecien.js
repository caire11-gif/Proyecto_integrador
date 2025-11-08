document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/dashboard/movirecien.php')
        .then(function(response){
            const contentType = response.headers.get('content-type');
            
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new TypeError("Response wasn't JSON: " + text);
                });
            }

            return response.json();
        })
        .then(movimiento=>{
            const movirecien=document.getElementById('moviRecien');

            movimiento.forEach(movimiento => {
                const lista = document.createElement('div');
                lista.classList.add('timeline-item', 'mb-3');  // Añadir margen abajo para separar cada item

                // Crear un contenedor que mantenga el formato adecuado (en este caso, eliminar 'd-flex')
                const flexContenedor = document.createElement('div');
                flexContenedor.classList.add('d-block', 'w-100');  // Cambio a 'd-block' para que los items ocupen toda la línea

                // Crear el elemento para la fecha del movimiento
                const fechamovi = document.createElement('h6');
                fechamovi.classList.add('mb-1');
                fechamovi.innerHTML = movimiento.fecha_movimiento;

                // Crear el tipo de movimiento y el código
                const tipomovi = document.createElement('p');
                
                // Manejo de tipos de movimiento
                if (movimiento.codigo_tipomovimiento === 'TM001') {
                    tipomovi.classList.add('fas', 'fa-truck-loading', 'text-success');
                    tipomovi.innerHTML = "Entrada - " + movimiento.nombre_producto;
                } else if (movimiento.codigo_tipomovimiento === 'TM002') {
                    tipomovi.classList.add('tipomovi');
                    tipomovi.style.color='#';
                    tipomovi.innerHTML = "<i class='fas fa-arrow-right text-warning me-2'><i>  Salida - " + movimiento.nombre_producto;
                }

                const codigo = document.createElement('p');
                if(movimiento.codigo_tipomovimiento==='TM001'){
                    codigo.innerHTML = movimiento.codigo_compra;
                } else if(movimiento.codigo_tipomovimiento){
                    codigo.innerHTML = movimiento.codigo_venta;
                }

                // Añadir fecha, tipo de movimiento y código al contenedor principal
                flexContenedor.appendChild(fechamovi);
                flexContenedor.appendChild(tipomovi);
                flexContenedor.appendChild(codigo);
    
                // Añadir el contenedor principal (timeline-item) al elemento de la lista
                lista.appendChild(flexContenedor);

                // Añadir el item completo a la sección de movimientos recientes
                movirecien.appendChild(lista);
            });
        })
        .catch(function(error){
            console.error('Error al cargar el contenido PHP: ',error);
        })
});