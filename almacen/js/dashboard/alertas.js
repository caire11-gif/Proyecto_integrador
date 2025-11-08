document.addEventListener('DOMContentLoaded', async function() {
    try {
        // Realizar la solicitud fetch al archivo PHP que genera las alertas de stock bajo
        const response = await fetch('php/dashboard/alertas.php');
        
        // Verificar si la respuesta es JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            throw new TypeError("La respuesta no es JSON: " + text);
        }

        // Obtener los datos JSON de la respuesta
        const alerta = await response.json();

        // Obtener el contenedor donde se mostrarán las alertas
        const alertas = document.getElementById('alertas');

        if (alerta.length > 0) {
            // Si hay alertas, generar los elementos para cada una
            alerta.forEach(alertaItem => {
                const listItem = document.createElement('div');
                listItem.classList.add('list-group-item');

                // Crear el contenedor con la clase 'd-flex'
                const dFlexContainer = document.createElement('div');
                dFlexContainer.classList.add('d-flex', 'w-100', 'justify-content-between', 'align-items-center');

                // Crear el contenedor con la información del producto
                const productInfo = document.createElement('div');

                // Nombre del producto
                const productName = document.createElement('h6');
                productName.classList.add('mb-1');
                productName.innerText = alertaItem.nombre_producto;

                // Texto de stock
                const stockText = document.createElement('p');
                stockText.classList.add('mb-1');
                stockText.innerHTML = `Stock: <strong>${alertaItem.stock} unidades</strong>`;

                // Texto de la categoría
                const categoryText = document.createElement('small');
                categoryText.classList.add('text-muted');
                categoryText.innerText = `Categoría: ${alertaItem.nombre_categoria}`;

                // Agregar los elementos de información al contenedor de producto
                productInfo.appendChild(productName);
                productInfo.appendChild(stockText);
                productInfo.appendChild(categoryText);

                // Crear el badge de alerta
                const badge = document.createElement('span');
                if (alertaItem.stock === 0) {
                    badge.classList.add('badge', 'bg-danger'); // Badge rojo para "Urgente"
                    badge.innerText = 'Urgente'; // Mostrar 'Urgente' si el stock es 0
                } else if (alertaItem.stock <= 5) {
                    badge.classList.add('badge', 'bg-warning'); // Badge amarillo para "Bajo Stock"
                    badge.innerText = 'Bajo Stock'; // Mostrar 'Bajo Stock' si el stock es bajo
                }

                // Agregar el contenedor de producto y el badge al contenedor principal
                dFlexContainer.appendChild(productInfo);
                dFlexContainer.appendChild(badge);

                // Agregar el contenedor principal a la lista
                listItem.appendChild(dFlexContainer);

                // Agregar el item a la lista de alertas
                alertas.appendChild(listItem);
            });
        } else {
            // Si no hay alertas, mostrar el mensaje de "No hay alertas urgentes"
            const noAlertItem = document.createElement('div');
            noAlertItem.classList.add('list-group-item', 'text-center', 'py-4');
            noAlertItem.innerHTML = `
                <i class='fas fa-check-circle text-success fa-2x mb-2'></i>
                <p class='mb-0 text-muted'>No hay alertas urgentes</p>
                <small class='text-muted'>Todos los productos tienen stock suficiente</small>
            `;
            alertas.appendChild(noAlertItem);
        }
    } catch (error) {
        console.error('Error al cargar las alertas desde PHP:', error);
    }
});
