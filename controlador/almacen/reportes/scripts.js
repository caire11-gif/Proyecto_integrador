/*OPCIONES*/
    /*Para productos*/
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../modelo/almacen/reportes/opciprod.php')
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
                const select = document.getElementById('filtroProducto');
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

    /*Para tipos de movimientos*/
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../modelo/almacen/reportes/opcitipomovi.php')
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
                const select = document.getElementById('filtroMovimiento');
                data.forEach(tipomovi => {
                    const option = document.createElement('option');
                    option.value = tipomovi.cod_tipomovimiento;
                    option.textContent = tipomovi.nombre;
                    select.appendChild(option);
                });
            })
            .catch(function(error){
            console.error('Error al cargar los datos de los roles: ',error);
            })
    });

//################################################################################################

/*FILTROS*/
    /*Para buscar*/
    const filtroProductos=document.getElementById('campo');
    const tablaProductos=document.getElementById('tablaMovimientos')
    const filasProductos=tablaProductos.getElementsByTagName('tr');

    filtroProductos.addEventListener('input', function(){
        const filtro=this.value.toLowerCase();

        for (let i = 0; i < filasProductos.length; i++) {
            const fila = filasProductos[i];
            const celdas = fila.getElementsByTagName('td');
            let mostrarFila = false;

            if (celdas.length >= 2) {
                const codigo = celdas[0].textContent.toLowerCase();      

                if (codigo.includes(filtro)) {
                    mostrarFila = true;
                }
            }

            fila.style.display = mostrarFila ? '' : 'none';
        }
    });

    /*Con las opciones*/
    const filtroProducto=document.getElementById('filtroProducto').addEventListener('change', aplicarFiltrosProducto);

    function aplicarFiltrosProducto() {
        const productoValue = document.getElementById('filtroProducto').value;
            
        const productoText = productoValue ? 
            document.getElementById('filtroProducto').options[document.getElementById('filtroProducto').selectedIndex].textContent : '';
       
        const rows = document.querySelectorAll('tbody tr');
            
        rows.forEach(row => {
            if (row.cells.length < 5) return;
                
            const producto = row.cells[2].textContent;
                
            const matchProducto = !productoValue || producto === productoText;
                
            row.style.display = (matchProducto) ? '' : 'none';
        });
    }

    const filtroMovimiento=document.getElementById('filtroMovimiento').addEventListener('change', aplicarFiltrosMovimiento);

    function aplicarFiltrosMovimiento() {
        const movimientoValue = document.getElementById('filtroMovimiento').value;
            
        const movimientoText = movimientoValue ? 
            document.getElementById('filtroMovimiento').options[document.getElementById('filtroMovimiento').selectedIndex].textContent : '';
       
        const rows = document.querySelectorAll('tbody tr');
            
        rows.forEach(row => {
            if (row.cells.length < 5) return;
                
            const movimiento = row.cells[3].textContent;
                
            const matchMovimiento = !movimientoValue || movimiento === movimientoText;
                
            row.style.display = (matchMovimiento) ? '' : 'none';
        });
    }

    document.getElementById('btnLimpiarFiltros').addEventListener('click', function(event) {
        document.getElementById('campo').value= '';
        document.getElementById('filtroProducto').selectedIndex = 0;
        document.getElementById('filtroMovimiento').selectedIndex = 0;

        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.style.display = '';
        });
        event.preventDefault();
    });

//####################################################################################################################

/*SELECCIÓN PARA LAS TABLAS*/
    /*Para los movimientos*/
    // Llamando a la función getDataMovimientos() al cargar la página
    document.addEventListener("DOMContentLoaded", getDataMovimientos);

    // Función para obtener datos con AJAX
    function getDataMovimientos() {
        let input = document.getElementById("campo").value
        let num_registros = document.getElementById("num_registros").value
        let content = document.getElementById("tablaMovimientos");
        let pagina = document.getElementById("pagina").value || 1;
        let orderCol = document.getElementById("orderCol").value
        let orderType = document.getElementById("orderType").value

        let formaData = new FormData()
        formaData.append('campo', input)
        formaData.append('registros', num_registros)
        formaData.append('pagina', pagina)
        formaData.append('orderCol', orderCol)
        formaData.append('orderType', orderType)

        fetch("../../modelo/almacen/reportes/movimientos.php", {
                method: "POST",
                body: formaData
            })
            .then(response => response.json())
            .then(data => {
                content.innerHTML = data.data
                document.getElementById("lbl-total").innerHTML = `Mostrando ${data.totalFiltro} de ${data.totalRegistros} registros`;
                document.getElementById("nav-paginacion").innerHTML = data.paginacion

                // Si la página actual no tiene resultados, ajustar la paginación para mostrar la primera página
                if (data.data.includes('Sin resultados') && parseInt(pagina) !== 1) {
                    nextPage(1); // Ir a la primera página
                }
            })
            .catch(err => console.log(err))
    }

    // Función para cambiar de página
    function nextPage(pagina) {
        document.getElementById('pagina').value = pagina
        getDataMovimientos()
    }

    // Función para ordenar columnas
    function ordenar(e) {
        let elemento = e.target;
        let orderType = elemento.classList.contains("asc") ? "desc" : "asc";

        document.getElementById('orderCol').value = elemento.cellIndex;
        document.getElementById("orderType").value = orderType;
        elemento.classList.toggle("asc");
        elemento.classList.toggle("desc");

        getDataMovimientos()
    }

    // Función para cambiar de página
    function resetPagina() {
        document.getElementById('pagina').value = 1
        getDataMovimientos()
    }

    // Event listeners para los eventos de cambio en el campo de entrada y el select
    document.getElementById("campo").addEventListener("keyup", resetPagina);
    document.getElementById("num_registros").addEventListener("change", getDataMovimientos);

    // Event listener para ordenar las columnas
    let columns = document.querySelectorAll(".sort");
    columns.forEach(column => {
        column.addEventListener("click", ordenar);
    });

    /*Para los productos más vendidos*/
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../modelo/almacen/reportes/productos_vendidos.php')
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
                let content = document.getElementById("tablaProductosVendidos");

                content.innerHTML=data.data;
            })
            .catch(function(error){
                console.error('Error al cargar los datos de los productos más vendidos: ',error);
            })
    });

//#######################################################################################################################

/*EXPORTAR*/
    /*Para movimientos*/
        /*Exportar para excel*/
        document.getElementById('exportarexcelmovimientos').addEventListener('click', function(btnElement){
            const originalText = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
            btnElement.disabled = true;

            fetch(`../../modelo/almacen/reportes/exportarmovi.php`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(movimientoCompleto => {
                    const workbook = XLSX.utils.book_new();
                    
                    const data = [];

                    const headers = ['Código', 'Fecha', 'Producto', 'Movimiento', 'Stock Actual', 'Usuario'];

                    data.push(headers);

                    movimientoCompleto.forEach(movi => {
                        const rowData = [
                            movi.cod_movimiento,
                            movi.fecha_movimiento,
                            movi.producto_nombre,
                            movi.tipo_movimiento,
                            movi.stock,
                            movi.usuario
                        ];
                        data.push(rowData);
                    });

                    const worksheet = XLSX.utils.aoa_to_sheet(data);

                    worksheet['!cols'] = [
                        { wch: 15 },
                        { wch: 25 },
                        { wch: 15 },
                        { wch: 15 },
                        { wch: 15 },
                        { wch: 15 }
                    ];

                    XLSX.utils.book_append_sheet(workbook, worksheet, 'Movimientos');

                    XLSX.writeFile(workbook, `Movimientos_Completo.xlsx`);

                    btnElement.innerHTML = originalText;
                    btnElement.disabled = false;
                    Swal.fire({
                        icon: "success",
                        title: "Descarga Completada",
                        width: "350px",
                    });
                })
                .catch(error => {
                    console.error('Error al exportar Excel:', error);
                    console.log('Error al exportar el archivo Excel. Por favor, intente nuevamente.');
                    
                    // Restaurar botón en caso de error
                    btnElement.innerHTML = originalText;
                    btnElement.disabled = false;
                });
        });

        /*Exportar para pdf*/
        document.getElementById('exportarpdfmovimientos').addEventListener('click', function(btnElement){
            const originalText = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
            btnElement.disabled = true;
    
            fetch(`../../modelo/almacen/reportes/exportarmovi.php`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(movimientoCompleto => {
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF('l', 'mm', 'a4');

                    // Título del documento
                    doc.setFontSize(16);
                    doc.text(`Movimientos Completos`, 14, 15);
                    doc.setFontSize(10);
                    doc.text(`Fecha de exportación: ${new Date().toLocaleDateString()}`, 14, 22);
                    doc.text(`Total de movimientos: ${movimientoCompleto.length}`, 14, 28);

                    const headers = [
                        ['Código', 'Fecha', 'Producto', 'Movimiento', 'Stock Actual', 'Usuario']
                    ];

                    const body = movimientoCompleto.map(movi => [
                        movi.cod_movimiento,
                        movi.fecha_movimiento,
                        movi.producto_nombre,
                        movi.tipo_movimiento,
                        movi.stock,
                        movi.usuario
                    ]);

                    // Crear tabla PDF
                    doc.autoTable({
                        head: headers,
                        body: body,
                        startY: 35,
                        styles: { 
                            fontSize: 6, 
                            cellPadding: 1,
                            lineColor: [0, 0, 0],
                            lineWidth: 0.1
                        },
                        headStyles: { 
                            fillColor: [52, 58, 64],
                            textColor: [255, 255, 255],
                            fontStyle: 'bold',
                            fontSize: 6
                        },
                        alternateRowStyles: { 
                            fillColor: [240, 240, 240]
                        },
                        margin: { top: 35 },
                        tableWidth: 'wrap'
                    });

                    // Descargar PDF
                    doc.save(`Movimientos_Completo.pdf`);

                    // Restaurar botón
                    btnElement.innerHTML = originalText;
                    btnElement.disabled = false;

                    Swal.fire({
                        icon: "success",
                        title: "Descarga Completada",
                        width: "350px",
                    });
                })
                .catch(error => {
                    console.error('Error al exportar PDF:', error);
                    alert('Error al exportar el archivo PDF. Por favor, intente nuevamente.');

                    // Restaurar botón en caso de error
                    btnElement.innerHTML = originalText;
                    btnElement.disabled = false;
                });
        });

    /*Para productos*/
        /*Exportar para excel*/
        document.getElementById('exportarexcelproductos').addEventListener('click', function(btnElement){
            const originalText = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
            btnElement.disabled = true;

            fetch(`../../modelo/almacen/reportes/exportarmasven.php`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(masVendidosCompleto => {
                    const workbook = XLSX.utils.book_new();

                    const data = [];

                    const headers = ['Producto', 'Categoría', 'Unidades Vendidas', 'Ingresos Totales', 'Stock Actual'];

                    data.push(headers);

                    masVendidosCompleto.forEach(masven => {
                        const rowData = [
                            masven.producto_nombre,
                            masven.categoria_nombre,
                            masven.unidades_vendidas,
                            masven.ingresos_totales,
                            masven.stock
                        ];
                        data.push(rowData);
                    });

                    const worksheet = XLSX.utils.aoa_to_sheet(data);

                    worksheet['!cols'] = [
                        { wch: 15 },
                        { wch: 30 },
                        { wch: 15 },
                        { wch: 15 },
                        { wch: 15 }
                    ];

                    XLSX.utils.book_append_sheet(workbook, worksheet, 'Productos_Mas_Vendidos');

                    XLSX.writeFile(workbook, `Productos_Mas_Vendidos_Completo.xlsx`);

                    btnElement.innerHTML = originalText;
                    btnElement.disabled = false;
                    Swal.fire({
                        icon: "success",
                        title: "Descarga Completada",
                        width: "350px",
                    });
                })
                .catch(error => {
                    console.error('Error al exportar Excel:', error);
                    console.log('Error al exportar el archivo Excel. Por favor, intente nuevamente.');

                    // Restaurar botón en caso de error
                    btnElement.innerHTML = originalText;
                    btnElement.disabled = false;
                });
        });

        /*Exportar para pdf*/
        document.getElementById('exportarpdfproductos').addEventListener('click', function(btnElement){
            const originalText = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
            btnElement.disabled = true;
    
            fetch(`../../modelo/almacen/reportes/exportarmasven.php`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(masVendidosCompleto => {
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF('l', 'mm', 'a4');

                    // Título del documento
                    doc.setFontSize(16);
                    doc.text(`Más Vendidos Completos`, 14, 15);
                    doc.setFontSize(10);
                    doc.text(`Fecha de exportación: ${new Date().toLocaleDateString()}`, 14, 22);
                    doc.text(`Total de productos más vendidos: ${masVendidosCompleto.length}`, 14, 28);

                    const headers = [
                        ['Producto', 'Categoría', 'Unidades Vendidas', 'Ingresos Totales', 'Stock Actual']
                    ];

                    const body = masVendidosCompleto.map(masven => [
                        masven.producto_nombre,
                        masven.categoria_nombre,
                        masven.unidades_vendidas,
                        masven.ingresos_totales,
                        masven.stock
                    ]);
            
                    // Crear tabla PDF
                    doc.autoTable({
                        head: headers,
                        body: body,
                        startY: 35,
                        styles: { 
                            fontSize: 6, 
                            cellPadding: 1,
                            lineColor: [0, 0, 0],
                            lineWidth: 0.1
                        },
                        headStyles: { 
                            fillColor: [52, 58, 64],
                            textColor: [255, 255, 255],
                            fontStyle: 'bold',
                            fontSize: 6
                        },
                        alternateRowStyles: { 
                            fillColor: [240, 240, 240]
                        },
                        margin: { top: 35 },
                        tableWidth: 'wrap'
                    });

                    // Descargar PDF
                    doc.save(`Productos_Mas_Vendidos_Completo.pdf`);

                    // Restaurar botón
                    btnElement.innerHTML = originalText;
                    btnElement.disabled = false;

                    Swal.fire({
                        icon: "success",
                        title: "Descarga Completada",
                        width: "350px",
                    });
                })
                .catch(error => {
                    console.error('Error al exportar PDF:', error);
                    alert('Error al exportar el archivo PDF. Por favor, intente nuevamente.');

                    // Restaurar botón en caso de error
                    btnElement.innerHTML = originalText;
                    btnElement.disabled = false;
                });
        });