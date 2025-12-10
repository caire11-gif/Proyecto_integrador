/*ALERTAS*/
    document.addEventListener('DOMContentLoaded', async function() {
        try {
            const response = await fetch('../../modelo/almacen/dashboard/alertas.php');
        
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new TypeError("La respuesta no es JSON: " + text);
            }

            const data = await response.json();
            const alertas = data.alertas;
            const estadisticas = data.estadisticas;

            const alertasContainer = document.getElementById('alertas');
            const totalAlertasElement = document.getElementById('totalAlertas');
            const cantAlertasElement = document.getElementById('cantAlertas');
            const alertasUrgentesElement = document.getElementById('alertasUrgentes');

            // Actualizar estadísticas
            if (totalAlertasElement) {
                totalAlertasElement.textContent = `${estadisticas.total} ${estadisticas.total === 1 ? 'alerta' : 'alertas'}`;
            }
            
            if (cantAlertasElement) {
                cantAlertasElement.textContent = estadisticas.total;
            }
            
            if (alertasUrgentesElement) {
                alertasUrgentesElement.textContent = `${estadisticas.alta} requieren atención urgente`;
            }

            if (alertas.length > 0) {
                // Mostrar todas las alertas (alta, media y baja)
                alertas.forEach(alertaItem => {
                    const listItem = document.createElement('div');
                    listItem.classList.add('alerta-item');
                    
                    // Determinar clase de prioridad
                    let priorityClass = 'baja';
                    if (alertaItem.prioridad === 'Alta' || alertaItem.prioridad === 'Crítico') {
                        priorityClass = 'alta';
                    } else if (alertaItem.prioridad === 'Media') {
                        priorityClass = 'media';
                    }
                    
                    listItem.classList.add(priorityClass);

                    // Determinar texto de prioridad
                    let priorityText = alertaItem.prioridad;
                    let priorityBadgeClass = priorityClass;
                    
                    // Texto más descriptivo
                    if (alertaItem.stock === 0) {
                        priorityText = 'AGOTADO';
                        priorityBadgeClass = 'alta';
                    } else if (alertaItem.stock <= 2) {
                        priorityText = 'MUY BAJO';
                        priorityBadgeClass = 'alta';
                    } else if (alertaItem.stock <= 5) {
                        priorityText = 'BAJO';
                        priorityBadgeClass = 'media';
                    } else {
                        priorityText = 'ATENCIÓN';
                        priorityBadgeClass = 'baja';
                    }

                    listItem.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="alerta-product-name">${alertaItem.nombre_producto}</div>
                                <div class="alerta-stock-info">
                                    <i class="fas fa-box me-1"></i> Stock: <strong>${alertaItem.stock} unidades</strong>
                                </div>
                                <div class="alerta-category">
                                    <i class="fas fa-tag me-1"></i> ${alertaItem.nombre_categoria}
                                </div>
                            </div>
                            <span class="alerta-priority-badge ${priorityBadgeClass}">
                                ${priorityText}
                            </span>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-truck me-1"></i> ${alertaItem.proveedor_nombre}
                        </small>
                    `;

                    alertasContainer.appendChild(listItem);
                });
            } else {
                // Si no hay alertas
                const noAlertItem = document.createElement('div');
                noAlertItem.classList.add('empty-state');
                noAlertItem.innerHTML = `
                    <i class='fas fa-check-circle text-success'></i>
                    <h6 class='mt-3 mb-1'>No hay alertas activas</h6>
                    <small class='text-muted'>Todos los productos tienen stock suficiente</small>
                `;
                alertasContainer.appendChild(noAlertItem);
            }
        } catch (error) {
            console.error('Error al cargar las alertas:', error);
            
            const errorItem = document.createElement('div');
            errorItem.classList.add('empty-state');
            errorItem.innerHTML = `
                <i class='fas fa-exclamation-circle text-danger'></i>
                <h6 class='mt-3 mb-1'>Error al cargar alertas</h6>
                <small class='text-muted'>Intenta recargar la página</small>
            `;
            document.getElementById('alertas').appendChild(errorItem);
        }
    });

//############################################################################################################################################################

/*MOVIMIENTOS RECIENTES*/
    document.addEventListener('DOMContentLoaded', async function(){
        try {
            const response = await fetch('../../modelo/almacen/dashboard/movirecien.php');
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new TypeError("Response wasn't JSON: " + text);
            }

            const movimientos = await response.json();
            const movirecien = document.getElementById('moviRecien');
            const totalMovimientosElement = document.getElementById('totalMovimientos');

            if (totalMovimientosElement) {
                totalMovimientosElement.textContent = `${movimientos.length} movimientos`;
            }

            if (movimientos.length > 0) {
                movirecien.innerHTML = ''; // Limpiar contenido
                
                movimientos.forEach((movimiento, index) => {
                    const movimientoCard = document.createElement('div');
                    movimientoCard.classList.add('movimiento-item');
                    
                    // Alternar colores para mejor visibilidad
                    if (index % 2 === 0) {
                        movimientoCard.style.backgroundColor = '#f9f9f9';
                    }
                    
                    // Determinar icono según tipo
                    let tipoIcono = 'fa-exchange-alt text-secondary';
                    let tipoColor = 'info';
                    
                    if (movimiento.tipo === 'entrada') {
                        tipoIcono = 'fa-truck-loading text-success';
                        tipoColor = 'success';
                    } else if (movimiento.observacion.toLowerCase().includes('venta')) {
                        tipoIcono = 'fa-shopping-cart text-warning';
                        tipoColor = 'warning';
                    }
                    
                    movimientoCard.innerHTML = `
                        <div class="movimiento-simple">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div class="movimiento-fecha">
                                    <i class="fas fa-clock me-1"></i>
                                    <strong>${movimiento.fecha_movimiento}</strong>
                                </div>
                                <div class="movimiento-tipo">
                                    <span class="badge bg-${tipoColor}">
                                        <i class="fas ${tipoIcono} me-1"></i>
                                        ${movimiento.tipo === 'entrada' ? 'Entrada' : 'Venta'}
                                    </span>
                                </div>
                            </div>
                        
                            <div class="movimiento-producto mb-1">
                                <i class="fas fa-box me-1"></i>
                                ${movimiento.producto_nombre}
                            </div>
                            
                            <div class="movimiento-descripcion">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    ${movimiento.observacion}
                                </small>
                            </div>
                        </div>
                    `;

                    movirecien.appendChild(movimientoCard);
                });
            
                // Agregar pie de información
                const infoPie = document.createElement('div');
                infoPie.classList.add('text-center', 'py-2', 'border-top', 'bg-light');
                infoPie.innerHTML = `
                    <small class="text-muted">
                        <i class="fas fa-history me-1"></i>
                        Últimos 6 movimientos registrados
                    </small>
                `;
                movirecien.appendChild(infoPie);
                
            } else {
                // Si no hay movimientos
                const noMovimientosItem = document.createElement('div');
                noMovimientosItem.classList.add('empty-state-simple');
                noMovimientosItem.innerHTML = `
                    <i class='fas fa-history fa-2x text-muted'></i>
                    <p class='mt-3 mb-0 text-muted'>No hay movimientos registrados</p>
                `;
                movirecien.appendChild(noMovimientosItem);
            }
        } catch (error) {
            console.error('Error al cargar movimientos:', error);
            
            const errorItem = document.createElement('div');
            errorItem.classList.add('empty-state-simple');
            errorItem.innerHTML = `
                <i class='fas fa-exclamation-triangle fa-2x text-danger'></i>
                <p class='mt-3 mb-0 text-muted'>Error al cargar movimientos</p>
                <small class='text-muted'>Revisa la consola para más detalles</small>
            `;
            movirecien.innerHTML = '';
            movirecien.appendChild(errorItem);
        }
    });

//###############################################################################################################################################################

/*CARDS*/
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../modelo/almacen/dashboard/seleccard.php')
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
                // Actualizar tarjetas principales
                document.getElementById('cantProd').innerHTML = data.cantidad_producto;
                document.getElementById('cantCate').innerHTML = data.cantidad_categoria;
                document.getElementById('cantMovi').innerHTML = data.cantidad_movimiento;
                document.getElementById('cantProdBajo').innerHTML = data.cantidad_producto_bajo;
                
                // Cargar alertas después de las estadísticas básicas
                setTimeout(() => {
                    fetch('../../modelo/almacen/dashboard/alertas.php')
                        .then(response => response.json())
                        .then(alertasData => {
                            const estadisticas = alertasData.estadisticas;
                            
                            // Actualizar tarjeta de alertas
                            const alertasCard = document.querySelector('.stats-card.warning .number');
                            if (alertasCard) {
                                alertasCard.textContent = estadisticas.total;
                            }
                            
                            const alertasUrgentes = document.querySelector('.stats-card.warning small');
                            if (alertasUrgentes) {
                                alertasUrgentes.textContent = `${estadisticas.alta} requieren atención urgente`;
                            }
                        })
                        .catch(error => console.error('Error al cargar estadísticas de alertas:', error));
                }, 100);
            })
            .catch(function(error){
                console.error('Error al cargar las estadísticas: ', error);
            });
    });