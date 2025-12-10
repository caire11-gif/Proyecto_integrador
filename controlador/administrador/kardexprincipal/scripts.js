// Variables globales
let currentProductPage = 1;
let currentMovimientosPages = {};
const productosPorPagina = 5;
const movimientosPorPagina = 10;

// Elementos DOM
const productFilter = document.getElementById("productFilter");
const fechaInicio = document.getElementById("fechaInicio");
const fechaFin = document.getElementById("fechaFin");
const kardexContainer = document.getElementById("kardexContainer");
const paginationContainer = document.getElementById("paginationContainer");
const loadingSpinner = document.getElementById("loadingSpinner");
const btnResetFilters = document.getElementById("btnResetFilters");

// Función para establecer rangos de fecha predefinidos
function setRangoFecha(tipo) {
    const hoy = new Date();
    let inicio, fin;
    
    switch(tipo) {
        case 'hoy':
            inicio = new Date(hoy);
            fin = new Date(hoy);
            break;
        case 'ayer':
            inicio = new Date(hoy);
            inicio.setDate(hoy.getDate() - 1);
            fin = new Date(inicio);
            break;
        case 'semana':
            inicio = new Date(hoy);
            inicio.setDate(hoy.getDate() - hoy.getDay());
            fin = new Date(hoy);
            fin.setDate(hoy.getDate() + (6 - hoy.getDay()));
            break;
        case 'mes':
            inicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            fin = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
            break;
        case 'anio':
            inicio = new Date(hoy.getFullYear(), 0, 1);
            fin = new Date(hoy.getFullYear(), 11, 31);
            break;
        default:
            return;
    }
    
    // Formatear fechas a YYYY-MM-DD
    fechaInicio.value = inicio.toISOString().split('T')[0];
    fechaFin.value = fin.toISOString().split('T')[0];
    
    // Actualizar kardex
    resetearPaginasAlFiltrar();
    cargarKardex();
}

// Mostrar/Ocultar loading
function showLoading() {
    loadingSpinner.style.display = 'block';
    kardexContainer.innerHTML = '';
    paginationContainer.innerHTML = '';
}

function hideLoading() {
    loadingSpinner.style.display = 'none';
}

// Función para resetear páginas cuando cambia el filtro
function resetearPaginasAlFiltrar() {
    currentMovimientosPages = {};
}

// Validar rango de fechas
function validarFechas() {
    const inicio = new Date(fechaInicio.value);
    const fin = new Date(fechaFin.value);
    
    if (inicio > fin) {
        Swal.fire({
            title: "Error en la fecha",
            text: 'La fecha de inicio no puede ser mayor a la fecha de fin',
            icon: "warning",
        });
        fechaInicio.value = fechaFin.value;
        return false;
    }
    
    return true;
}

// Cargar kardex via AJAX
function cargarKardex() {
    if (!validarFechas()) return;
    
    showLoading();
    
    const productoFiltro = productFilter.value;
    const fechaInicioVal = fechaInicio.value;
    const fechaFinVal = fechaFin.value;
    
    const formData = new FormData();
    formData.append('producto_filtro', productoFiltro);
    formData.append('fecha_inicio', fechaInicioVal);
    formData.append('fecha_fin', fechaFinVal);
    formData.append('pagina_productos', currentProductPage);
    formData.append('productos_por_pagina', productosPorPagina);
    formData.append('movimientos_por_pagina', movimientosPorPagina);
    
    // Agregar páginas actuales de movimientos
    for (const [producto, pagina] of Object.entries(currentMovimientosPages)) {
        formData.append(`movimientos_${producto}`, pagina);
    }
    
    fetch('../../modelo/administrador/kardexprincipal/ajax_kardex.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            actualizarEstadisticas(data.estadisticas);
            renderizarKardex(data.kardex);
            renderizarPaginacion(data.paginacion);
            actualizarMovimientosPages(data.paginas_movimientos);
        } else {
            mostrarError(data.error || 'Error al cargar el kardex');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error de conexión');
    })
    .finally(() => {
        hideLoading();
    });
}

// Actualizar estadísticas
function actualizarEstadisticas(estadisticas) {
    document.getElementById('totalMovimientos').textContent = estadisticas.total_movimientos;
    document.getElementById('valorEntradas').textContent = 'S/ ' + parseFloat(estadisticas.total_entradas).toFixed(2);
    document.getElementById('valorSalidas').textContent = 'S/ ' + parseFloat(estadisticas.total_salidas).toFixed(2);
    document.getElementById('stockValorizado').textContent = 'S/ ' + parseFloat(estadisticas.stock_valorizado).toFixed(2);
}

// Renderizar kardex
function renderizarKardex(kardexData) {
    if (kardexData.length === 0) {
        kardexContainer.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No se encontraron movimientos en el kardex para los filtros seleccionados.
            </div>
        `;
        return;
    }
    
    let html = '';
    
    kardexData.forEach(producto => {
        html += `
            <div class="card border-0 shadow-sm mb-4 product-kardex active" data-producto="${producto.cod_producto}">
                <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-cube me-2"></i>${producto.nombre}
                        <small class="text-muted">(${producto.cod_producto})</small>
                    </h5>
                    <div class="movimientos-info">
                        ${producto.total_movimientos > 0 ? 
                            `Mostrando ${producto.inicio_movimientos}-${producto.fin_movimientos} de ${producto.total_movimientos} movimientos` : 
                            'Sin movimientos'
                        }
                    </div>
                </div>
                <div class="card-body">
                    <div class="export-buttons d-flex justify-content-end mb-3">
                        <button class="btn btn-success btn-sm" onclick="exportarExcel('${producto.cod_producto}', '${producto.nombre}', this)">
                            <i class="fas fa-file-excel me-1"></i>Exportar Excel
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="exportarPDF('${producto.cod_producto}', '${producto.nombre}', this)">
                            <i class="fas fa-file-pdf me-1"></i>Exportar PDF
                        </button>
                    </div>
                    
                    <!-- SECCIÓN DE COSTO DE VENTAS DEL PRODUCTO -->
                    <div class="costo-ventas-container">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Costo de Ventas del Producto</h6>
                                <small class="text-muted">Suma total de todas las salidas en el período</small>
                            </div>
                            <div class="costo-ventas-value">
                                S/ ${parseFloat(producto.costo_ventas_total || producto.costo_ventas || 0).toFixed(2)}
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">Costo total de ventas: S/ ${parseFloat(producto.costo_ventas_total || producto.costo_ventas || 0).toFixed(2)}</small>
                        </div>
                    </div>
        `;
        
        if (producto.kardex && producto.kardex.length > 0) {
            html += `
                    <div class="table-responsive kardex-table mt-3">
                        <table class="table table-bordered table-sm" id="table-${producto.cod_producto}">
                            <thead class="table-light">
                                <tr>
                                    <th rowspan="2">FECHA</th>
                                    <th colspan="3" class="text-center">ENTRADAS</th>
                                    <th colspan="3" class="text-center">SALIDAS</th>
                                    <th colspan="3" class="text-center">SALDO FINAL</th>
                                </tr>
                                <tr>
                                    <th>CANTIDAD</th>
                                    <th>COSTO UNIT.</th>
                                    <th>COSTO TOTAL</th>
                                    <th>CANTIDAD</th>
                                    <th>COSTO UNIT.</th>
                                    <th>COSTO TOTAL</th>
                                    <th>UNIDADES</th>
                                    <th>COSTO UNIT.</th>
                                    <th>COSTO TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            producto.kardex.forEach(mov => {
                // No mostrar la fila de saldo inicial si está vacía
                if (mov.es_saldo_inicial && mov.saldo_costo_total == 0) {
                    return; // Saltar esta fila
                }
                
                html += `
                                <tr class="${mov.clase_fila}">
                                    <td><strong>${mov.fecha}</strong></td>
                                    <td>${mov.entradas_cantidad > 0 ? mov.html_entradas_cantidad : '-'}</td>
                                    <td>${mov.entradas_cantidad > 0 ? mov.html_entradas_costo_unitario : '-'}</td>
                                    <td>${mov.entradas_cantidad > 0 ? `<strong>S/ ${parseFloat(mov.entradas_costo_total).toFixed(2)}</strong>` : '-'}</td>
                                    <td>${mov.salidas_cantidad > 0 ? mov.html_salidas_cantidad : '-'}</td>
                                    <td>${mov.salidas_cantidad > 0 && !mov.error_stock ? mov.html_salidas_costo_unitario : '-'}</td>
                                    <td>${mov.salidas_cantidad > 0 && !mov.error_stock ? `<strong>S/ ${parseFloat(mov.salidas_costo_total).toFixed(2)}</strong>` : '-'}</td>
                                    <td>${mov.saldo_unidades_html}</td>
                                    <td>${mov.saldo_costos_html}</td>
                                    <td><strong class="text-primary">S/ ${parseFloat(mov.saldo_costo_total).toFixed(2)}</strong></td>
                                </tr>
                `;
            });
            
            html += `
                            </tbody>
                        </table>
                    </div>
            `;
            
            // Paginación de movimientos
            if (producto.total_paginas_movimientos > 1) {
                html += `
                    <div class="kardex-pagination">
                        ${producto.pagina_actual_movimientos > 1 ? 
                            `<button class="btn btn-sm btn-outline-primary" onclick="cambiarPaginaMovimientos('${producto.cod_producto}', ${producto.pagina_actual_movimientos - 1})">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </button>` : ''
                        }
                        <span class="mx-2">
                            Página ${producto.pagina_actual_movimientos} de ${producto.total_paginas_movimientos}
                        </span>
                        ${producto.pagina_actual_movimientos < producto.total_paginas_movimientos ? 
                            `<button class="btn btn-sm btn-outline-primary" onclick="cambiarPaginaMovimientos('${producto.cod_producto}', ${producto.pagina_actual_movimientos + 1})">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </button>` : ''
                        }
                    </div>
                `;
            }
        } else {
            html += `
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>No hay movimientos para este producto en el período seleccionado.
                    </div>
            `;
        }
        
        html += `
                </div>
            </div>
        `;
    });
    
    kardexContainer.innerHTML = html;
}

// Renderizar paginación
function renderizarPaginacion(paginacion) {
    if (paginacion.total_paginas <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    let html = `
        <div class="pagination-container">
            <nav>
                <ul class="pagination">
    `;
    
    // Botón anterior
    if (paginacion.pagina_actual > 1) {
        html += `
            <li class="page-item">
                <button class="page-link" onclick="cambiarPaginaProductos(${paginacion.pagina_actual - 1})">
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
            </li>
        `;
    }
    
    // Números de página
    for (let i = 1; i <= paginacion.total_paginas; i++) {
        const active = i === paginacion.pagina_actual ? 'active' : '';
        html += `
            <li class="page-item ${active}">
                <button class="page-link" onclick="cambiarPaginaProductos(${i})">${i}</button>
            </li>
        `;
    }
    
    // Botón siguiente
    if (paginacion.pagina_actual < paginacion.total_paginas) {
        html += `
            <li class="page-item">
                <button class="page-link" onclick="cambiarPaginaProductos(${paginacion.pagina_actual + 1})">
                    Siguiente <i class="fas fa-chevron-right"></i>
                </button>
            </li>
        `;
    }
    
    html += `
                </ul>
            </nav>
        </div>
    `;
    
    paginationContainer.innerHTML = html;
}

// Cambiar página de productos
function cambiarPaginaProductos(pagina) {
    currentProductPage = pagina;
    cargarKardex();
}

// Cambiar página de movimientos
function cambiarPaginaMovimientos(codProducto, pagina) {
    currentMovimientosPages[codProducto] = pagina;
    cargarKardex();
}

// Actualizar páginas de movimientos
function actualizarMovimientosPages(paginasMovimientos) {
    currentMovimientosPages = { ...currentMovimientosPages, ...paginasMovimientos };
}

// Mostrar error
function mostrarError(mensaje) {
    kardexContainer.innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>${mensaje}
        </div>
    `;
}

// Event listeners para filtros
productFilter.addEventListener('change', function() {
    resetearPaginasAlFiltrar();
    cargarKardex();
});

fechaInicio.addEventListener('change', function() {
    resetearPaginasAlFiltrar();
    cargarKardex();
});

fechaFin.addEventListener('change', function() {
    resetearPaginasAlFiltrar();
    cargarKardex();
});

// Limpiar filtros
btnResetFilters.addEventListener('click', function() {
    productFilter.value = '';
    
    // Establecer rango por defecto (último mes)
    const hoy = new Date();
    const haceUnMes = new Date();
    haceUnMes.setMonth(hoy.getMonth() - 1);
    
    fechaInicio.value = haceUnMes.toISOString().split('T')[0];
    fechaFin.value = hoy.toISOString().split('T')[0];
    
    currentProductPage = 1;
    currentMovimientosPages = {};
    cargarKardex();
});

// Funciones de exportación
// Actualizar la función exportarExcel en kardexprincipal.php
// Función exportarExcel corregida
function exportarExcel(codProducto, nombreProducto, btnElement) {
    const originalText = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
    btnElement.disabled = true;
    
    const fechaInicioVal = document.getElementById('fechaInicio').value;
    const fechaFinVal = document.getElementById('fechaFin').value;
    let url = `../../modelo/administrador/kardexprincipal/obtener_kardex_completo.php?cod_producto=${codProducto}`;
    
    if (fechaInicioVal && fechaFinVal) {
        url += `&fecha_inicio=${fechaInicioVal}&fecha_fin=${fechaFinVal}`;
    }
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            const kardexCompleto = data.kardex;
            const costoVentasExport = data.costo_ventas_total || 0;
            
            const workbook = XLSX.utils.book_new();
            const dataArray = [];
            
            // Encabezados principales - SIN COMBINAR CELDAS
            dataArray.push(['PRODUCTO:', nombreProducto]);
            dataArray.push(['CÓDIGO:', codProducto]);
            dataArray.push(['COSTO DE VENTAS TOTAL:', `S/ ${parseFloat(costoVentasExport).toFixed(2)}`]);
            dataArray.push(['RANGO DE FECHAS:', `${fechaInicioVal || 'Inicio'} al ${fechaFinVal || 'Fin'}`]);
            dataArray.push([]); // Línea vacía
            
            // Encabezados de tabla
            const headers = [
                'FECHA', 'TIPO', 
                'ENTRADAS CANTIDAD', 'ENTRADAS COSTO UNIT.', 'ENTRADAS COSTO TOTAL',
                'SALIDAS CANTIDAD', 'SALIDAS COSTO UNIT.', 'SALIDAS COSTO TOTAL',
                'SALDO UNIDADES', 'SALDO COSTO UNIT.', 'SALDO COSTO TOTAL'
            ];
            
            dataArray.push(headers);
            
            // Agregar filas de datos
            kardexCompleto.forEach(mov => {
                const rowData = [
                    mov.fecha,
                    mov.tipo,
                    mov.entradas_cantidad > 0 ? formatearCantidadExport(mov.entradas_cantidad) : '',
                    mov.entradas_cantidad > 0 ? parseFloat(mov.entradas_costo_unitario).toFixed(2) : '',
                    mov.entradas_cantidad > 0 ? parseFloat(mov.entradas_costo_total).toFixed(2) : '',
                    mov.salidas_cantidad > 0 ? formatearCantidadExport(mov.salidas_cantidad) : '',
                    mov.salidas_cantidad > 0 && !mov.error_stock ? parseFloat(mov.salidas_costo_unitario).toFixed(2) : '',
                    mov.salidas_cantidad > 0 && !mov.error_stock ? parseFloat(mov.salidas_costo_total).toFixed(2) : '',
                    mov.saldo_unidades_texto || '',
                    mov.saldo_costos_texto || '',
                    parseFloat(mov.saldo_costo_total).toFixed(2)
                ];
                dataArray.push(rowData);
            });
            
            const worksheet = XLSX.utils.aoa_to_sheet(dataArray);
            
            // Ajustar ancho de columnas
            worksheet['!cols'] = [
                { wch: 12 },  // Fecha
                { wch: 20 },  // Tipo
                { wch: 15 },  // Entradas Cantidad
                { wch: 15 },  // Entradas Costo Unit.
                { wch: 15 },  // Entradas Costo Total
                { wch: 15 },  // Salidas Cantidad
                { wch: 15 },  // Salidas Costo Unit.
                { wch: 15 },  // Salidas Costo Total
                { wch: 20 },  // Saldo Unidades
                { wch: 20 },  // Saldo Costo Unit.
                { wch: 15 }   // Saldo Costo Total
            ];
            
            // NO combinar celdas para los encabezados principales
            // Eliminar cualquier propiedad de merge
            if (worksheet['!merges']) {
                delete worksheet['!merges'];
            }
            
            XLSX.utils.book_append_sheet(workbook, worksheet, 'Kardex');
            
            const rangoTexto = fechaInicioVal && fechaFinVal ? 
                `${fechaInicioVal}_${fechaFinVal}` : 'COMPLETO';
            
            XLSX.writeFile(workbook, `Kardex_${nombreProducto}_${codProducto}_${rangoTexto}.xlsx`);
            
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        })
        .catch(error => {
            console.error('Error al exportar Excel:', error);
            Swal.fire({
                title: "warning",
                text: 'Error al exportar el archivo Excel. Por favor, intente nuevamente',
                icon: "warning",
            })
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        });
}

// Función exportarPDF corregida
function exportarPDF(codProducto, nombreProducto, btnElement) {
    const originalText = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
    btnElement.disabled = true;
    
    const fechaInicioVal = document.getElementById('fechaInicio').value;
    const fechaFinVal = document.getElementById('fechaFin').value;
    let url = `../../modelo/administrador/kardexprincipal/obtener_kardex_completo.php?cod_producto=${codProducto}`;
    
    if (fechaInicioVal && fechaFinVal) {
        url += `&fecha_inicio=${fechaInicioVal}&fecha_fin=${fechaFinVal}`;
    }
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            const kardexCompleto = data.kardex;
            const costoVentasPDF = data.costo_ventas_total || 0;
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4');
            
            const rangoTexto = fechaInicioVal && fechaFinVal ? 
                `${fechaInicioVal} al ${fechaFinVal}` : 'Completo';
            
            // Título y información
            doc.setFontSize(16);
            doc.text(`Kardex - ${nombreProducto}`, 14, 15);
            doc.setFontSize(10);
            doc.text(`Código: ${codProducto}`, 14, 22);
            doc.text(`Período: ${rangoTexto}`, 14, 28);
            doc.text(`Costo de Ventas Total: S/ ${parseFloat(costoVentasPDF).toFixed(2)}`, 14, 34);
            doc.text(`Fecha de exportación: ${new Date().toLocaleDateString()}`, 14, 40);
            
            // Encabezados de tabla
            const headers = [
                ['FECHA', 'TIPO', 
                 'ENTRADAS', 'ENTRADAS', 'ENTRADAS',
                 'SALIDAS', 'SALIDAS', 'SALIDAS',
                 'SALDO', 'SALDO', 'SALDO'],
                ['', '',
                 'CANTIDAD', 'COSTO UNIT.', 'COSTO TOTAL',
                 'CANTIDAD', 'COSTO UNIT.', 'COSTO TOTAL',
                 'UNIDADES', 'COSTO UNIT.', 'COSTO TOTAL']
            ];
            
            // Datos de la tabla
            const body = kardexCompleto.map(mov => [
                mov.fecha,
                mov.tipo,
                mov.entradas_cantidad > 0 ? formatearCantidadExport(mov.entradas_cantidad) : '',
                mov.entradas_cantidad > 0 ? `S/ ${parseFloat(mov.entradas_costo_unitario).toFixed(2)}` : '',
                mov.entradas_cantidad > 0 ? `S/ ${parseFloat(mov.entradas_costo_total).toFixed(2)}` : '',
                mov.salidas_cantidad > 0 ? formatearCantidadExport(mov.salidas_cantidad) : '',
                mov.salidas_cantidad > 0 && !mov.error_stock ? `S/ ${parseFloat(mov.salidas_costo_unitario).toFixed(2)}` : '',
                mov.salidas_cantidad > 0 && !mov.error_stock ? `S/ ${parseFloat(mov.salidas_costo_total).toFixed(2)}` : '',
                mov.saldo_unidades_texto || '',
                mov.saldo_costos_texto || '',
                `S/ ${parseFloat(mov.saldo_costo_total).toFixed(2)}`
            ]);
            
            // Crear tabla
            doc.autoTable({
                head: headers,
                body: body,
                startY: 50,
                headStyles: { 
                    fillColor: [52, 58, 64],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    fontSize: 8
                },
                columnStyles: {
                    0: { cellWidth: 15 },
                    1: { cellWidth: 18 },
                    2: { cellWidth: 12 },
                    3: { cellWidth: 15 },
                    4: { cellWidth: 15 },
                    5: { cellWidth: 12 },
                    6: { cellWidth: 15 },
                    7: { cellWidth: 15 },
                    8: { cellWidth: 25 },
                    9: { cellWidth: 25 },
                    10: { cellWidth: 15 }
                },
                styles: { 
                    fontSize: 7, 
                    cellPadding: 2,
                    lineColor: [0, 0, 0],
                    lineWidth: 0.1
                },
                alternateRowStyles: { 
                    fillColor: [240, 240, 240]
                }
            });
            
            const rangoArchivo = fechaInicioVal && fechaFinVal ? 
                `${fechaInicioVal}_${fechaFinVal}` : 'COMPLETO';
            
            const nombreArchivo = `Kardex_${nombreProducto}_${codProducto}_${rangoArchivo}.pdf`;
            
            doc.save(nombreArchivo);
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        })
        .catch(error => {
            console.error('Error al exportar PDF:', error);
            Swal.fire({
                title: "warning",
                text: 'Error al exportar el archivo PDF. Por favor, intente nuevamente',
                icon: "warning",
            })
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        });
}

// Función auxiliar para formatear cantidades en exportación
function formatearCantidadExport(cantidad) {
    const num = parseFloat(cantidad);
    if (Math.floor(num) === num) {
        return num.toString();
    }
    return num.toFixed(2);
}

// Actualizar la función exportarPDF en kardexprincipal.php (similar a exportarExcel)
function exportarPDF(codProducto, nombreProducto, btnElement) {
    const originalText = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
    btnElement.disabled = true;
    
    const fechaInicioVal = document.getElementById('fechaInicio').value;
    const fechaFinVal = document.getElementById('fechaFin').value;
    let url = `../../modelo/administrador/kardexprincipal/obtener_kardex_completo.php?cod_producto=${codProducto}`;
    
    if (fechaInicioVal && fechaFinVal) {
        url += `&fecha_inicio=${fechaInicioVal}&fecha_fin=${fechaFinVal}`;
    }
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            const kardexCompleto = data.kardex;
            const costoVentasPDF = data.costo_ventas_total || 0;
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a3');
            
            const rangoTexto = fechaInicioVal && fechaFinVal ? 
                `${fechaInicioVal} al ${fechaFinVal}` : 'Completo';
            
            const titulo = `Kardex - ${nombreProducto} (${codProducto})`;
            const subtitulo = `Período: ${rangoTexto}`;
            const costoVentasTexto = `Costo de Ventas: S/ ${parseFloat(costoVentasPDF).toFixed(2)}`;
            
            doc.setFontSize(16);
            doc.text(titulo, 20, 20);
            doc.setFontSize(10);
            doc.text(subtitulo, 20, 28);
            doc.text(`Fecha de exportación: ${new Date().toLocaleDateString()}`, 20, 34);
            doc.text(costoVentasTexto, 20, 40);
            doc.text(`Total de movimientos: ${kardexCompleto.length}`, 20, 46);
            
            // Encabezados mejorados (estilo de la segunda función)
            const headers = [
                ['FECHA', 'TIPO', 
                 'ENT. CANT', 'ENT. C/U', 'ENT. TOTAL',
                 'SAL. CANT', 'SAL. C/U', 'SAL. TOTAL',
                 'SALDO UND', 'SALDO C/U', 'SALDO TOTAL']
            ];
            
            // Procesar el kardex para formatear correctamente los lotes (funcionalidad de la segunda)
            const body = kardexCompleto.map(mov => {
                // Formatear unidades de saldo
                let saldoUnidadesFormatted = '';
                let saldoCostosFormatted = '';
                
                if (mov.saldo_unidades_texto && mov.saldo_costos_texto) {
                    // Separar los lotes por saltos de línea
                    const unidadesArray = mov.saldo_unidades_texto.split('\n');
                    const costosArray = mov.saldo_costos_texto.split('\n');
                    
                    // Emparejar unidades y costos
                    const maxLength = Math.max(unidadesArray.length, costosArray.length);
                    for (let i = 0; i < maxLength; i++) {
                        const unidad = unidadesArray[i] || '';
                        const costo = costosArray[i] || '';
                        if (unidad || costo) {
                            saldoUnidadesFormatted += unidad + '\n';
                            saldoCostosFormatted += costo + '\n';
                        }
                    }
                    
                    // Eliminar último salto de línea
                    saldoUnidadesFormatted = saldoUnidadesFormatted.trim();
                    saldoCostosFormatted = saldoCostosFormatted.trim();
                } else {
                    saldoUnidadesFormatted = mov.saldo_unidades_texto || '';
                    saldoCostosFormatted = mov.saldo_costos_texto || '';
                }
                
                return [
                    mov.fecha,
                    mov.tipo,
                    mov.entradas_cantidad > 0 ? mov.entradas_cantidad.toString() : '',
                    mov.entradas_cantidad > 0 ? parseFloat(mov.entradas_costo_unitario).toFixed(2) : '',
                    mov.entradas_cantidad > 0 ? parseFloat(mov.entradas_costo_total).toFixed(2) : '',
                    mov.salidas_cantidad > 0 ? mov.salidas_cantidad.toString() : '',
                    mov.salidas_cantidad > 0 && !mov.error_stock ? parseFloat(mov.salidas_costo_unitario).toFixed(2) : '',
                    mov.salidas_cantidad > 0 && !mov.error_stock ? parseFloat(mov.salidas_costo_total).toFixed(2) : '',
                    saldoUnidadesFormatted,
                    saldoCostosFormatted,
                    parseFloat(mov.saldo_costo_total).toFixed(2)
                ];
            });
            
            // Configuración de la tabla combinando estilos
            doc.autoTable({
                head: headers,
                body: body,
                startY: 52,
                styles: { 
                    fontSize: 8, 
                    cellPadding: 2,
                    lineColor: [0, 0, 0],
                    lineWidth: 0.1,
                    overflow: 'linebreak'
                },
                headStyles: { 
                    fillColor: [52, 58, 64], // Manteniendo el color oscuro de la primera
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    fontSize: 9,
                    halign: 'center',
                    valign: 'middle'
                },
                alternateRowStyles: { 
                    fillColor: [240, 240, 240] // Manteniendo filas alternadas de la primera
                },
                columnStyles: {
                    0: { cellWidth: 18, fontSize: 8, halign: 'center', cellPadding: 2 },
                    1: { cellWidth: 22, fontSize: 8, halign: 'center', cellPadding: 2 },
                    2: { cellWidth: 14, fontSize: 8, halign: 'center', cellPadding: 2 },
                    3: { cellWidth: 16, fontSize: 8, halign: 'right', cellPadding: 2 },
                    4: { cellWidth: 18, fontSize: 8, halign: 'right', cellPadding: 2 },
                    5: { cellWidth: 14, fontSize: 8, halign: 'center', cellPadding: 2 },
                    6: { cellWidth: 16, fontSize: 8, halign: 'right', cellPadding: 2 },
                    7: { cellWidth: 18, fontSize: 8, halign: 'right', cellPadding: 2 },
                    8: { 
                        cellWidth: 30, 
                        fontSize: 7, 
                        halign: 'left', 
                        cellPadding: 1.5,
                        minCellHeight: 10,
                        valign: 'top'
                    },
                    9: { 
                        cellWidth: 30, 
                        fontSize: 7, 
                        halign: 'left', 
                        cellPadding: 1.5,
                        minCellHeight: 10,
                        valign: 'top'
                    },
                    10: { cellWidth: 18, fontSize: 8, halign: 'right', cellPadding: 2 }
                },
                margin: { left: 10, right: 10 },
                tableWidth: 'auto',
                // Manteniendo el manejo de altura para múltiples líneas
                didParseCell: function(data) {
                    // Para columnas de saldo, calcular altura basada en número de líneas
                    if (data.column.index === 8 || data.column.index === 9) {
                        if (data.cell.raw) {
                            const lines = data.cell.raw.split('\n');
                            if (lines.length > 1) {
                                data.cell.minCellHeight = 6 + (lines.length * 4);
                                data.cell.styles.fontSize = 7;
                            }
                        }
                    }
                }
            });
            
            const rangoArchivo = fechaInicioVal && fechaFinVal ? 
                `${fechaInicioVal}_${fechaFinVal}` : 'COMPLETO';
            
            const nombreArchivo = `Kardex_${nombreProducto}_${codProducto}_${rangoArchivo}.pdf`;
            
            doc.save(nombreArchivo);
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        })
        .catch(error => {
            console.error('Error al exportar PDF:', error);
            Swal.fire({
                title: "warning",
                text: 'Error al exportar el archivo PDF. Por favor, intente nuevamente',
                icon: "warning",
            })
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        });
}

// Otra alternativa: Crear una función que procese los lotes en formato tabla
function exportarPDFConTablaAnidada(codProducto, nombreProducto, btnElement) {
    const originalText = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
    btnElement.disabled = true;
    
    const fechaInicioVal = document.getElementById('fechaInicio').value;
    const fechaFinVal = document.getElementById('fechaFin').value;
    let url = `../../modelo/administrador/kardexprincipal/obtener_kardex_completo.php?cod_producto=${codProducto}`;
    
    if (fechaInicioVal && fechaFinVal) {
        url += `&fecha_inicio=${fechaInicioVal}&fecha_fin=${fechaFinVal}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            const kardexCompleto = data.kardex;
            const costoVentasPDF = data.costo_ventas_total || 0;
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a3');
            
            const rangoTexto = fechaInicioVal && fechaFinVal ? 
                `${fechaInicioVal} al ${fechaFinVal}` : 'Completo';
            
            // Título y información
            doc.setFontSize(16);
            doc.text(`KARDEX - ${nombreProducto}`, 20, 20);
            doc.setFontSize(10);
            doc.text(`Código: ${codProducto}`, 20, 28);
            doc.text(`Período: ${rangoTexto}`, 20, 34);
            doc.text(`Costo de Ventas: S/ ${parseFloat(costoVentasPDF).toFixed(2)}`, 20, 40);
            doc.text(`Exportado: ${new Date().toLocaleDateString()}`, 20, 46);
            
            // ENCABEZADOS
            const headers = [
                ['FECHA', 'TIPO', 
                 'ENT. CANT', 'ENT. C/U', 'ENT. TOTAL',
                 'SAL. CANT', 'SAL. C/U', 'SAL. TOTAL',
                 'SALDO (LOTES)', 'SALDO TOTAL']
            ];
            
            // Procesar kardex con formato mejorado para lotes
            const body = kardexCompleto.map(mov => {
                // Crear texto formateado para los lotes
                let lotesText = '';
                let saldoTotal = parseFloat(mov.saldo_costo_total).toFixed(2);
                
                if (mov.saldo_unidades_texto && mov.saldo_costos_texto) {
                    const unidadesArray = mov.saldo_unidades_texto.split('\n');
                    const costosArray = mov.saldo_costos_texto.split('\n');
                    
                    for (let i = 0; i < unidadesArray.length; i++) {
                        if (unidadesArray[i] && costosArray[i]) {
                            lotesText += `${unidadesArray[i].padEnd(10)} | ${costosArray[i]}\n`;
                        } else if (unidadesArray[i]) {
                            lotesText += `${unidadesArray[i]}\n`;
                        } else if (costosArray[i]) {
                            lotesText += `${costosArray[i]}\n`;
                        }
                    }
                    lotesText = lotesText.trim();
                }
                
                // Si no hay lotes, usar texto simple
                if (!lotesText && mov.saldo_unidades_texto) {
                    lotesText = mov.saldo_unidades_texto;
                }
                
                return [
                    mov.fecha,
                    mov.tipo,
                    mov.entradas_cantidad > 0 ? formatearCantidadExport(mov.entradas_cantidad) : '',
                    mov.entradas_cantidad > 0 ? parseFloat(mov.entradas_costo_unitario).toFixed(2) : '',
                    mov.entradas_cantidad > 0 ? parseFloat(mov.entradas_costo_total).toFixed(2) : '',
                    mov.salidas_cantidad > 0 ? formatearCantidadExport(mov.salidas_cantidad) : '',
                    mov.salidas_cantidad > 0 && !mov.error_stock ? parseFloat(mov.salidas_costo_unitario).toFixed(2) : '',
                    mov.salidas_cantidad > 0 && !mov.error_stock ? parseFloat(mov.salidas_costo_total).toFixed(2) : '',
                    lotesText,
                    `S/ ${saldoTotal}`
                ];
            });
            
            // Crear tabla con menos columnas
            doc.autoTable({
                head: headers,
                body: body,
                startY: 52,
                headStyles: { 
                    fillColor: [41, 128, 185],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    fontSize: 9,
                    cellPadding: 3,
                    halign: 'center'
                },
                columnStyles: {
                    0: { cellWidth: 18, fontSize: 8, halign: 'center' },
                    1: { cellWidth: 22, fontSize: 8, halign: 'center' },
                    2: { cellWidth: 14, fontSize: 8, halign: 'center' },
                    3: { cellWidth: 16, fontSize: 8, halign: 'right' },
                    4: { cellWidth: 18, fontSize: 8, halign: 'right' },
                    5: { cellWidth: 14, fontSize: 8, halign: 'center' },
                    6: { cellWidth: 16, fontSize: 8, halign: 'right' },
                    7: { cellWidth: 18, fontSize: 8, halign: 'right' },
                    8: { 
                        cellWidth: 60, 
                        fontSize: 6, // Tamaño pequeño para texto formateado
                        halign: 'left', 
                        cellPadding: 1,
                        minCellHeight: 8,
                        valign: 'top'
                    },
                    9: { cellWidth: 20, fontSize: 8, halign: 'right' }
                },
                styles: { 
                    font: 'courier', // Fuente monoespaciada para alinear columnas
                    fontSize: 6,
                    cellPadding: 1,
                    lineColor: [100, 100, 100],
                    lineWidth: 0.2,
                    overflow: 'linebreak'
                },
                margin: { left: 10, right: 10 },
                tableWidth: 'auto',
                theme: 'grid'
            });
            
            const rangoArchivo = fechaInicioVal && fechaFinVal ? 
                `${fechaInicioVal}_${fechaFinVal}` : 'COMPLETO';
            
            const nombreArchivo = `Kardex_${nombreProducto}_${codProducto}_${rangoArchivo}.pdf`;
            
            doc.save(nombreArchivo);
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        })
        .catch(error => {
            console.error('Error al exportar PDF:', error);
            Swal.fire({
                title: "warning",
                text: 'Error al exportar el archivo PDF. Por favor, intente nuevamente',
                icon: "warning",
            })
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        });
}

// Función auxiliar para formatear cantidades
function formatearCantidadExport(cantidad) {
    const num = parseFloat(cantidad);
    if (Math.floor(num) === num) {
        return num.toString();
    }
    return num.toFixed(2);
}

// Cargar kardex al iniciar
document.addEventListener('DOMContentLoaded', function() {
    // Establecer valores por defecto
    if (!fechaInicio.value) {
        const hoy = new Date();
        const haceUnMes = new Date();
        haceUnMes.setMonth(hoy.getMonth() - 1);
        
        fechaInicio.value = haceUnMes.toISOString().split('T')[0];
        fechaFin.value = hoy.toISOString().split('T')[0];
    }
    
    cargarKardex();
});

//##############################################################################################

/*FECHAS*/
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../modelo/administrador/kardexprincipal/fechas.php')
            .then(function(response){
                if(!response.ok){
                    throw new Error("Error con la solicitud");
                }
                
                return response.json();
            })
            .then(data=>{
                const inicio=document.getElementById('fechaInicio');
                const fin=document.getElementById('fechaFin');

                const ini=document.createElement('input');
                ini.classList.add('form-control');
                ini.type='date';

                ini.value=data.value_minima;
                ini.min=data.fecha_minima;
                ini.max=data.fecha_maxima;

                inicio.appendChild(ini);

                const fi=document.createElement('input');
                fi.classList.add('form-control');
                fi.type='date';

                fi.value=data.value_maxima;
                fi.min=data.fecha_minima;
                fi.max=data.fecha_maxima;

                fin.appendChild(fi);
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });

//############################################################################################

/*OPCIONES*/
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../modelo/administrador/kardexprincipal/opciprod.php')
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
                const select = document.getElementById('productFilter');
                data.forEach(prod => {
                    const option = document.createElement('option');
                    option.value = prod.cod_producto;
                    option.textContent = prod.nombre;
                    select.appendChild(option);
                });
            })
            .catch(function(error){
            console.error('Error al cargar los datos de los roles: ',error);
            })
    });