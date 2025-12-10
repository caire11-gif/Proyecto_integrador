//============================================================================================
//                                      VALIDACIONES
//============================================================================================
//============================================================================================
//                             PARA FORMULARIO DE INSERCIÓN
//============================================================================================
    document.getElementById("formProducto")?.addEventListener("submit", function(event) {
        const nomprod = document.getElementById("nombreProducto").value.trim();
        const unicajaprod = document.getElementById("unidadesCaja").value.trim();
        const precosprod = document.getElementById("precioCosto").value.trim();
        const prevenprod = document.getElementById("precioVenta").value.trim();
        const precioCosto = parseFloat(precosprod) || 0;
        const unidadesCaja = parseInt(unicajaprod) || 1;
        const precioVenta = parseFloat(prevenprod) || 0;
            
        // Calcular costo unitario
        const costoUnitario = unidadesCaja > 0 ? precioCosto / unidadesCaja : 0;
            
        if(codprod.length > 10) {
            Swal.fire({
                icon: "warning",
                title: "Código muy largo",
                text: "El código no puede tener más de 10 caracteres",
            });
            event.preventDefault();
            return false;
        }
            
        // Validar nombre
        if(nomprod === "") {
            Swal.fire({
                icon: "warning",
                title: "Nombre requerido",
                text: "El nombre del producto es obligatorio",
            });
            event.preventDefault();
            return false;
        }

        // Validar precio costo
        if(precosprod === "" || precioCosto <= 0) {
            Swal.fire({
                icon: "warning",
                title: "Precio costo inválido",
                text: "El precio de costo debe ser mayor a 0",
            });
            event.preventDefault();
            return false;
        }            
        
        // Validar precio venta
        if(prevenprod === "" || precioVenta <= 0) {
            Swal.fire({
                icon: "warning",
                title: "Precio venta inválido",
                text: "El precio de venta debe ser mayor a 0",
            });
            event.preventDefault();
            return false;
        }
            
        // CORRECCIÓN: Validar que precio venta sea mayor al costo unitario
        if(precioVenta <= costoUnitario) {
            Swal.fire({
                icon: "warning",
                title: "Precio insuficiente",
                html: `El precio de venta (S/ ${precioVenta.toFixed(2)}) debe ser mayor al costo unitario (S/ ${costoUnitario.toFixed(2)})<br>
                        <small>Actualmente no hay margen de ganancia</small>`,
            });
            event.preventDefault();
            return false;
        }
            
        // Validar unidades por caja
        if(unicajaprod === "" || unidadesCaja <= 0) {
            Swal.fire({
                icon: "warning",
                title: "Unidades inválidas",
                text: "Las unidades por caja deben ser mayores a 0",
            });
            event.preventDefault();
            return false;
        }

        return true;
    });

//============================================================================================
//                              PARA FORMULARIO DE EDICIÓN
//============================================================================================
    document.getElementById("formEditarProducto")?.addEventListener("submit", function(event) {
        const precosprod = document.getElementById("precioCostoEdit").value.trim();
        const prevenprod = document.getElementById("precioVentaEdit").value.trim();
        const unicajaprod = document.getElementById("unidadesCajaEdit").value.trim();
        const precioCosto = parseFloat(precosprod) || 0;
        const unidadesCaja = parseInt(unicajaprod) || 1;
        const precioVenta = parseFloat(prevenprod) || 0;            

        // Calcular costo unitario
        const costoUnitario = unidadesCaja > 0 ? precioCosto / unidadesCaja : 0;
            
        // Validar precio costo
        if(precosprod === "" || precioCosto <= 0) {
            Swal.fire({
                icon: "warning",
                title: "Precio costo inválido",
                text: "El precio de costo debe ser mayor a 0",
            });
            event.preventDefault();
            return false;
        }
            
        // Validar precio venta
        if(prevenprod === "" || precioVenta <= 0) {
            Swal.fire({
                icon: "warning",
                title: "Precio venta inválido",
                text: "El precio de venta debe ser mayor a 0",
            });
            event.preventDefault();
            return false;
        }
            
        // CORRECCIÓN: Validar que precio venta sea mayor al costo unitario
        if(precioVenta <= costoUnitario) {
            Swal.fire({
                icon: "warning",
                title: "Precio insuficiente",
                html: `El precio de venta (S/ ${precioVenta.toFixed(2)}) debe ser mayor al costo unitario (S/ ${costoUnitario.toFixed(2)})<br>
                        <small>Actualmente no hay margen de ganancia</small>`,
            });
            event.preventDefault();
            return false;
        }
            
        // Validar unidades por caja
        if(unicajaprod === "" || unidadesCaja <= 0) {
            Swal.fire({
                icon: "warning",
                title: "Unidades inválidas",
                text: "Las unidades por caja deben ser mayores a 0",
            });
            event.preventDefault();
            return false;
        }
            
        return true;
    });

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

        fetch(`../../controlador/almacen/gestionproductos/php/procesarExportarProd.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(productoCompleto => {
                const workbook = XLSX.utils.book_new();
                
                const data = [];

                const headers = ['Código', 'Nombre', 'Precio Costo', 'Precio Venta', 'Stock', 'Unidades/Caja', 'Categoría', 'Proveedor'];

                data.push(headers);

                productoCompleto.forEach(prod => {
                    const rowData = [
                        prod.cod_producto,
                        prod.producto_nombre,
                        prod.precio_costo,
                        prod.precio_venta,
                        prod.stock,
                        prod.unidades_por_caja,
                        prod.categoria_nombre,
                        prod.proveedor_nombre
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
        
        fetch(`../../controlador/almacen/gestionproductos/php/procesarExportarProd.php`)
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
                doc.text(`Total de proveedores: ${productoCompleto.length}`, 14, 28);
                
                const headers = [
                    ['Código', 'Nombre', 'Precio Costo', 'Precio Venta', 'Stock', 'Unidades/Caja', 'Categoría', 'Proveedor']
                ];
                
                const body = productoCompleto.map(prod => [
                    prod.cod_producto,
                    prod.producto_nombre,
                    prod.precio_costo,
                    prod.precio_venta,
                    prod.stock,
                    prod.unidades_por_caja,
                    prod.categoria_nombre,
                    prod.proveedor_nombre
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

//============================================================================================
//                                   SELECCIÓN PARA LA TABLA
//============================================================================================
    document.addEventListener("DOMContentLoaded", getData);

    function activarBotonesEliminar() {
        document.querySelectorAll('.btnEliminar').forEach(boton => {
            boton.addEventListener('click', () => {
                const codprod = boton.dataset.codprod;

                Swal.fire({
                    title: "¿Estás seguro?",
                    text: "Esta acción no se puede deshacer",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Sí, estoy seguro"
                }).then((result1) => {
                    if (result1.isConfirmed) {
                        Swal.fire({
                            title: "¿En serio?",
                            text: "¿Realmente está seguro?",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonColor: "#3085d6",
                            cancelButtonColor: "#d33",
                            confirmButtonText: "Realmente estoy seguro"
                        }).then((result2) => {                        
                            if (result2.isConfirmed) {
                                window.location.href =
                                    `../../controlador/almacen/gestionproductos/php/procesarEliminarProd.php?cod_producto=${encodeURIComponent(codprod)}`;
                            }
                        });
                    }
                });
            });
        });
    }

    function activarBotonesActualizar(){
        document.querySelectorAll('.btnActualizar').forEach(boton =>{
            boton.addEventListener('click', () => {
                document.getElementById('codigoProductoEdit').value = boton.dataset.codprod;
                document.getElementById('nombreProductoEdit').value = boton.dataset.nombre;
                document.getElementById('precioCostoEdit').value=boton.dataset.precosto;
                document.getElementById('precioVentaEdit').value = boton.dataset.preventa;
                document.getElementById('stockProductoEdit').value = boton.dataset.stock;
                document.getElementById('unidadesCajaEdit').value = boton.dataset.unidades;
                document.getElementById('costoUnitarioEdit').value=boton.dataset.preciounidad;
                document.getElementById('categoriaProductoEdit').value = boton.dataset.codcate;
                document.getElementById('proveedorProductoEdit').value = boton.dataset.codprove;

                const modal = new bootstrap.Modal(document.getElementById('modalActualizarProducto'));
                modal.show();
            });
        })
    }

    // Función para obtener datos con AJAX
    function getData() {
        let input = document.getElementById("buscarProducto").value
        let num_registros = document.getElementById("num_registros").value
        let content = document.getElementById("content")
        let pagina = document.getElementById("pagina").value || 1;
        let orderCol = document.getElementById("orderCol").value
        let orderType = document.getElementById("orderType").value

        let formaData = new FormData()
        formaData.append('buscarProducto', input)
        formaData.append('registros', num_registros)
        formaData.append('pagina', pagina)
        formaData.append('orderCol', orderCol)
        formaData.append('orderType', orderType)

        fetch("../../controlador/almacen/gestionproductos/php/procesarSeleccionarProd.php", {
                method: "POST",
                body: formaData
            })
            .then(response => response.json())
            .then(data => {
                content.innerHTML = data.data
                document.getElementById("lbl-total").innerHTML = `Mostrando ${data.totalFiltro} de ${data.totalRegistros} registros`;
                document.getElementById("nav-paginacion").innerHTML = data.paginacion

                activarBotonesActualizar();
                activarBotonesEliminar();

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
    document.getElementById("buscarProducto").addEventListener("keyup", resetPagina);
    document.getElementById("num_registros").addEventListener("change", getData);

    // Event listener para ordenar las columnas
    let columns = document.querySelectorAll(".sort");
    columns.forEach(column => {
        column.addEventListener("click", ordenar);
    });

//============================================================================================
//                                          OPCIONES
//============================================================================================
//============================================================================================
//                                       PARA CATEGORÍA
//============================================================================================    
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../modelo/almacen/gestionproductos/dao/opcicate.php')
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
                const select1 = document.getElementById('categoriaProducto');
                const select2=document.getElementById('filtroCategoria');
                const select3=document.getElementById('categoriaProductoEdit');

                data.forEach(cate=>{
                    const option1 = document.createElement('option');
                    option1.value = cate.categoria_codigo;
                    option1.textContent = cate.categoria_nombre;
                    select1.appendChild(option1);

                    const option2=document.createElement('option');
                    option2.value=cate.categoria_codigo;
                    option2.textContent=cate.categoria_nombre;
                    select2.appendChild(option2);

                    const option3=document.createElement('option');
                    option3.value=cate.categoria_codigo;
                    option3.textContent=cate.categoria_nombre;
                    select3.appendChild(option3);
                });
            })
            .catch(function(error){
            console.error('Error al cargar los datos de las opciones: ',error);
            })
    });

//============================================================================================
//                                      PARA PROVEEDOR
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../modelo/almacen/gestionproductos/dao/opciprove.php')
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
                const select1 = document.getElementById('proveedorProducto');
                const select2=document.getElementById('filtroProveedor');
                const select3=document.getElementById('proveedorProductoEdit');

                data.forEach(prove=>{
                    const option1=document.createElement('option');
                    option1.value=prove.proveedor_codigo;
                    option1.textContent=prove.proveedor_nombre;
                    select1.appendChild(option1);

                    const option2=document.createElement('option');
                    option2.value=prove.proveedor_codigo;
                    option2.textContent=prove.proveedor_nombre;
                    select2.appendChild(option2);

                    const option3=document.createElement('option');
                    option3.value=prove.proveedor_codigo;
                    option3.textContent=prove.proveedor_nombre;
                    select3.appendChild(option3);
                });
            })
            .catch(function(error){
            console.error('Error al cargar los datos de las opciones: ',error);
            })
    });