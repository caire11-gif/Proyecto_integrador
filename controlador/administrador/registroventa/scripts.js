/*CARDS*/
    /*Cantidad de productos*/
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../modelo/administrador/registroventa/cantprod.php')
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
                document.getElementById('productosVendidos').innerHTML=data.cantidad_productos;
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });

    /*Cantidad de ventas*/
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../modelo/administrador/registroventa/cantven.php')
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
                document.getElementById('cantVentas').innerHTML=data.cantidad_ventas;
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });

    /*Monto total*/
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../modelo/administrador/registroventa/montototal.php')
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
                document.getElementById('montoTotal').innerHTML=data.suma_ventas;
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });

    /*Promedio ventas*/
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../modelo/administrador/registroventa/promven.php')
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
                document.getElementById('promedioVenta').innerHTML=data.promedio_ventas;
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });

//###############################################################################################################################################################

/*FILTROS*/
    /*Para input*/
    const filtroVentas=document.getElementById('campo');
    const tablaVentas=document.getElementById('content');
    const filaVentas=tablaVentas.getElementsByTagName('tr');

    filtroVentas.addEventListener('input', function(){
        const filtro = this.value.toLowerCase();

        for (let i = 0; i < filaVentas.length; i++) {
            const fila = filaVentas[i];
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

    /*Para vendedor*/
    const filtro=document.getElementById('filtroVendedor').addEventListener('change', aplicarFiltros);

    function aplicarFiltros() {
        const vendedorValue = document.getElementById('filtroVendedor').value;
            
        const vendedorText = vendedorValue ? 
            document.getElementById('filtroVendedor').options[document.getElementById('filtroVendedor').selectedIndex].textContent : '';
        
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            if (row.cells.length < 4) return;

            const vendedorProducto = row.cells[2].textContent;

            const matchVendedor = !vendedorValue || vendedorProducto === vendedorText;

            row.style.display = (matchVendedor) ? '' : 'none';
        });
    }

    document.getElementById('filtrar').addEventListener('click', function(event){
        const fechaInicial=document.getElementById('fechaInicio').value;
        const fechaFinal=document.getElementById('fechaFin').value;

        console.log(fechaInicial);
        console.log(fechaFinal);
        event.preventDefault();
    })

    function filtrarFechas(event){
        
    }

    document.getElementById('btnLimpiarFiltros').addEventListener('click', function(event) {
        document.getElementById('campo').value='';
        document.getElementById('filtroVendedor').selectedIndex = 0;

        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.style.display = '';
        });
        event.preventDefault();
    });

//###############################################################################################################################################################

/*EXPORTAR*/
    /*Exportar excel*/
    document.getElementById('exportarexcel').addEventListener('click', function(btnElement){
        const originalText = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
        btnElement.disabled = true;

        fetch(`../../modelo/administrador/registroventa/exportarventa.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(ventaCompleta => {
                const workbook = XLSX.utils.book_new();
                
                const data = [];

                const headers = ['Código', 'Fecha Venta', 'Usuario', 'Cantidad', 'Total'];

                data.push(headers);

                ventaCompleta.forEach(ven => {
                    const rowData = [
                        ven.cod_venta,
                        ven.fecha_venta,
                        ven.usuario,
                        ven.cantidad,
                        ven.total
                    ];
                    data.push(rowData);
                });

                const worksheet = XLSX.utils.aoa_to_sheet(data);
                
                worksheet['!cols'] = [
                    { wch: 15 },
                    { wch: 25 },
                    { wch: 15 },
                    { wch: 15 },
                    { wch: 40 }
                ];
                
                XLSX.utils.book_append_sheet(workbook, worksheet, 'Proveedores');

                XLSX.writeFile(workbook, `Ventas_Completa.xlsx`);

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
    document.getElementById('exportarpdf').addEventListener('click', function(btnElement){
        const originalText = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
        btnElement.disabled = true;
        
        fetch(`../../modelo/administrador/registroventa/exportarventa.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(ventaCompleta => {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('l', 'mm', 'a4');
                
                // Título del documento
                doc.setFontSize(16);
                doc.text(`Ventas Completas`, 14, 15);
                doc.setFontSize(10);
                doc.text(`Fecha de exportación: ${new Date().toLocaleDateString()}`, 14, 22);
                doc.text(`Total de ventas: ${ventaCompleta.length}`, 14, 28);
                
                const headers = [
                    ['Código', 'Fecha Venta', 'Usuario', 'Cantidad', 'Total']
                ];
                
                const body = ventaCompleta.map(ven => [
                    ven.cod_venta,
                    ven.fecha_venta,
                    ven.usuario,
                    ven.cantidad,
                    ven.total
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
                doc.save(`Ventas_Completa.pdf`);
                
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

//################################################################################################################################################################

/*OPCIONES*/
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../modelo/administrador/registroventa/opciven.php')
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
                const select = document.getElementById('filtroVendedor');
                data.forEach(empven => {
                    const option = document.createElement('option');
                    option.value = empven.cod_usuario;
                    option.textContent=empven.usuario;
                    //option.textContent = empven.nombre+" "+empven.apellido;
                    select.appendChild(option);
                });
            })
            .catch(function(error){
            console.error('Error al cargar los datos de los proveedores: ',error);
            })
    });

//###########################################################################################################################################################

/*SELECCIÓN PARA LA TABLA*/
    document.addEventListener("DOMContentLoaded", getData);

    function getData() {
        let input = document.getElementById("campo").value
        let num_registros = document.getElementById("num_registros").value
        let content = document.getElementById("content")
        let pagina = document.getElementById("pagina").value || 1;
        let orderCol = document.getElementById("orderCol").value
        let orderType = document.getElementById("orderType").value

        let formaData = new FormData()
        formaData.append('campo', input)
        formaData.append('registros', num_registros)
        formaData.append('pagina', pagina)
        formaData.append('orderCol', orderCol)
        formaData.append('orderType', orderType)

        fetch("../../modelo/administrador/registroventa/selecregven.php", {
            method: "POST",
            body: formaData
        }).then(response => response.json())
        .then(data => {
            content.innerHTML = data.data;
            document.getElementById("lbl-total").innerHTML = `Mostrando ${data.totalFiltro} de ${data.totalRegistros} registros`;
            document.getElementById("nav-paginacion").innerHTML = data.paginacion

            // Si la página actual no tiene resultados, ajustar la paginación para mostrar la primera página
            if (data.data.includes('Sin resultados') && parseInt(pagina) !== 1) {
                nextPage(1); // Ir a la primera página
            }
        }).catch(err => console.log(err))
    }

    // Función para cambiar de página
    function nextPage(pagina) {
        document.getElementById('pagina').value = pagina
        getData()
    }

    // Función para ordenar columnas
    function ordenar(e) {
        let elemento = e.target;
        let orderType = elemento.classList.contains("asc") ? "desc" : "asc";

        document.getElementById('orderCol').value = elemento.cellIndex;
        document.getElementById("orderType").value = orderType;
        elemento.classList.toggle("asc");
        elemento.classList.toggle("desc");

        getData()
    }

    // Función para cambiar de página
    function resetPagina() {
        document.getElementById('pagina').value = 1
        getData()
    }

    // Event listeners para los eventos de cambio en el campo de entrada y el select
    document.getElementById("campo").addEventListener("keyup", resetPagina);
    document.getElementById("num_registros").addEventListener("change", getData);

    // Event listener para ordenar las columnas
    let columns = document.querySelectorAll(".sort");
    columns.forEach(column => {
        column.addEventListener("click", ordenar);
    });