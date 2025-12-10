//============================================================================================
//                                          CARDS
//============================================================================================    
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/vendedor/consultarstock/php/procesarSeleccionarCards.php')
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
                const select1=document.getElementById('totalProductos');
                const select2=document.getElementById('stockBajo');
                const select3=document.getElementById('stockAgotado');

                select1.innerHTML=data.cantidad_productos;
                select2.innerHTML=data.producto_bajo;
                select3.innerHTML=data.producto_agotado;
            })
            .catch(function(error){
            console.error('Error al cargar los datos de las categorías: ',error);
            })
    });

//============================================================================================
//                                          OPCIONES
//============================================================================================
//============================================================================================
//                                       PARA CATEGORÍA
//============================================================================================    
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/vendedor/consultarstock/php/procesarSeleccionarCate.php')
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
                const select1=document.getElementById('filtroCategoria');

                data.forEach(cate=>{
                    const option1 = document.createElement('option');
                    option1.value = cate.categoria_codigo;
                    option1.textContent = cate.categoria_nombre;
                    select1.appendChild(option1);
                });
            })
            .catch(function(error){
            console.error('Error al cargar los datos de las categorías: ',error);
            })
    });

//============================================================================================
//                                  SELECCIÓN PARA PRODUCTOS
//============================================================================================
    document.addEventListener("DOMContentLoaded", getDataProductos);

    function getDataProductos(){
        let formaData=new FormData();
        fetch('../../controlador/vendedor/consultarstock/php/procesarSeleccionarProd.php',{
                method: "POST",
                body: formaData
            })
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
                let content = document.getElementById('tablaProductos');

                content.innerHTML=data.data;
            })
            .catch(function(error){
                console.error('Error al cargar los datos de los productos: ',error);
            })
    }

//============================================================================================
//                                  SELECCIÓN PARA ALERTAS
//============================================================================================
    document.addEventListener("DOMContentLoaded", getData);

    function getData(){
        let formaData=new FormData();
        fetch('../../controlador/vendedor/consultarstock/php/procesarSeleccionarAlertas.php',{
                method: "POST",
                body: formaData
            })
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
                let content = document.getElementById('alertasStock');

                content.innerHTML=data.data;
            })
            .catch(function(error){
                console.error('Error al cargar los datos de alertas: ',error);
            })
    }

//============================================================================================
//                                          EXPORTAR
//============================================================================================
//============================================================================================
//                                          PARA EXCEL
//============================================================================================
    document.getElementById('exportarexcel').addEventListener('click', function(btnElement){
        const originalText = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
        btnElement.disabled = true;

        fetch(`../../controlador/vendedor/consultarstock/php/procesarExportarProd.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(productoCompleto => {
                const workbook = XLSX.utils.book_new();
                
                const data = [];

                const headers = ['Código', 'Nombre', 'Categoría', 'Precio Costo', 'Precio Venta', 'Margen', 'Unid. x Caja', 'Stock'];

                data.push(headers);

                productoCompleto.forEach(prod => {
                    const rowData = [
                        prod.cod_producto,
                        prod.nombre,
                        prod.categoria_nombre,
                        prod.precio_compra_unidad,
                        prod.precio_venta,
                        prod.precio_venta-prod.precio_compra_unidad,
                        prod.unidades_por_caja,
                        prod.stock,
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
                    { wch: 15 },
                    { wch: 25 },
                    { wch: 25 }
                ];

                XLSX.utils.book_append_sheet(workbook, worksheet, 'Proveedores');

                XLSX.writeFile(workbook, `Productos_Completo.xlsx`);
                
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

//============================================================================================
//                                          PARA PDF
//============================================================================================
    document.getElementById('exportarpdf').addEventListener('click', function(btnElement){
        const originalText = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
        btnElement.disabled = true;
        
        fetch(`../../controlador/vendedor/consultarstock/php/procesarExportarProd.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(productoCompleto => {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('l', 'mm', 'a4');
                
                // Título del documento
                doc.setFontSize(16);
                doc.text(`Productos Completos`, 14, 15);
                doc.setFontSize(10);
                doc.text(`Fecha de exportación: ${new Date().toLocaleDateString()}`, 14, 22);
                doc.text(`Total de productos: ${productoCompleto.length}`, 14, 28);
                
                const headers = [
                    ['Código', 'Nombre', 'Categoría', 'Precio Costo', 'Precio Venta', 'Margen', 'Unid. x Caja', 'Stock']
                ];

                const body = productoCompleto.map(prod => [
                    prod.cod_producto,
                    prod.nombre,
                    prod.categoria_nombre,
                    prod.precio_compra_unidad,
                    prod.precio_venta,
                    prod.precio_venta-prod.precio_compra_unidad,
                    prod.unidades_por_caja,
                    prod.stock,
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
                doc.save(`Productos_Completo.pdf`);
                
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