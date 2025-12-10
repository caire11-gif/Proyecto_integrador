//============================================================================================
//                                          CARDS
//============================================================================================
//============================================================================================
//                                   CANTIDAD DE MOVIMIENTOS
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/administrador/proveedores/php/procesarCantidadMovi.php')
            .then(function(response){
                if(!response.ok){
                    throw new Error("Error con la solicitud");
                }
            
                return response.json();
            })
            .then(function(data){
                document.getElementById('cantMovi').innerHTML=data.cantidad_movimiento;
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });

//============================================================================================
//                                    CANTIDAD DE PROVEEDORES
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/administrador/proveedores/php/procesarCantidadProve.php')
            .then(function(response){
                if(!response.ok){
                    throw new Error("Error con la solicitud");
                }
                
                return response.json();
            })
            .then(function(data1){
                document.getElementById('cantProve').innerHTML=data1.cantidad_proveedor;
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });

//============================================================================================
//                                  SELECCIÓN PARA LA TABLA
//============================================================================================
    document.addEventListener("DOMContentLoaded", getData);

    function activarBotonesEliminar() {
        document.querySelectorAll('.btnEliminar').forEach(boton => {    
            boton.addEventListener('click', () => {
                const codprove = boton.dataset.codprove;

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
                                    `../../modelo/administrador/proveedores/dao/eliminarprove.php?codprove=${encodeURIComponent(codprove)}`;
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
                document.getElementById('codigoActualizarProveedor').value = boton.dataset.codprove;
                document.getElementById('nombreActualizarProveedor').value = boton.dataset.razonsocial;
                document.getElementById('rucActualizarProveedor').value=boton.dataset.ruc;
                document.getElementById('telefonoActualizarProveedor').value = boton.dataset.telefono;
                document.getElementById('direccionActualizarProveedor').value = boton.dataset.direccion;

                const modal = new bootstrap.Modal(document.getElementById('modalActualizarProveedor'));
                modal.show();
            });
        })
    }

    // Función para obtener datos con AJAX
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

        fetch("../../controlador/administrador/proveedores/php/procesarSeleccionarProveedor.php", {
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
    document.getElementById("campo").addEventListener("keyup", resetPagina);
    document.getElementById("num_registros").addEventListener("change", getData);

    // Event listener para ordenar las columnas
    let columns = document.querySelectorAll(".sort");
    columns.forEach(column => {
        column.addEventListener("click", ordenar);
    });

//============================================================================================
//                                      VALIDACIONES
//============================================================================================
//============================================================================================
//                                 PARA INSERTAR PROVEEDOR
//============================================================================================
    document.getElementById("formularioProveedor").addEventListener("submit", function(event) {
        const nomprove=document.getElementById("razonSocialProveedor").value.trim();
        const ruceprove=document.getElementById('rucProveedor').value.trim();
        const teleprove=document.getElementById("telefonoProveedor").value.trim();
        const dirprove=document.getElementById("direccionProveedor").value.trim();

        const rucprove=ruceprove.replace(/\s+/g, '');
        const telprove = teleprove.replace(/\s+/g, '');

        const regexnom = /^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

        if(nomprove===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El nombre no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if (!regexnom.test(nomprove)) {
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El nombre debe empezar con mayúscula y contener solo letras",
                width: "350px",
            });
            event.preventDefault();
            return;
        }

        if(rucprove===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El ruc no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if(!rucprove.startsWith("20")){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El ruc debe empezar con 20",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if(rucprove.length!==11 || isNaN(rucprove)){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El ruc debe tener 11 números",
                width: "350px",
            });
            event.preventDefault();
            return;
        }

        if(telprove===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El teléfono no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if(telprove.length!==9 || isNaN(telprove)){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El teléfono debe tener 9 números",
                width: "350px",
            });
            event.preventDefault();
            return;
        }

        if(dirprove===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "la dirección no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        }
    });

//============================================================================================
//                                PARA ACTUALIZAR PROVEEDORES
//============================================================================================
    document.getElementById("formularioActualizarProveedor").addEventListener("submit", function(event) {
        const codprove=document.getElementById("codigoActualizarProveedor").value.trim();
        const nomprove=document.getElementById("nombreActualizarProveedor").value.trim();
        const teleprove=document.getElementById("telefonoActualizarProveedor").value.trim();
        const dirprove=document.getElementById("direccionActualizarProveedor").value.trim();

        const telprove = teleprove.replace(/\s+/g, '');

        if (codprove === "") {
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El código no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        }  

        const regexnom = /^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

        if(nomprove===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El nombre no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if (!regexnom.test(nomprove)) {
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El nombre debe empezar con mayúscula y contener solo letras",
                width: "350px",
            });
            event.preventDefault();
            return;
        }

        if(telprove===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El teléfono no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if(telprove.length!==9 || isNaN(telprove)){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El teléfono debe tener 9 números",
                width: "350px",
            });
            event.preventDefault();
            return;
        }

        if(dirprove===" "){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "La dirección no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        }
    });

//============================================================================================
//                                           EXPORTAR 
//============================================================================================
//============================================================================================
//                                          PARA EXCEL   
//============================================================================================
    document.getElementById('exportarexcel').addEventListener('click', function(btnElement){
        const originalText = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
        btnElement.disabled = true;

        fetch(`../../controlador/administrador/proveedores/php/procesarExportarProve.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(proveedorCompleto => {
                const workbook = XLSX.utils.book_new();
                
                const data = [];

                const headers = ['Código', 'Proveedor', 'Ruc', 'Teléfono', 'Dirección'];

                data.push(headers);

                proveedorCompleto.forEach(prove => {
                    const rowData = [
                        prove.cod_proveedor,
                        prove.razon_social,
                        prove.ruc,
                        prove.telefono,
                        prove.direccion
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

                XLSX.writeFile(workbook, `Proveedores_Completo.xlsx`);

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
        
        fetch(`../../controlador/administrador/proveedores/php/procesarExportarProve.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(proveedorCompleto => {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('l', 'mm', 'a4');
                
                // Título del documento
                doc.setFontSize(16);
                doc.text(`Proveedores Completos`, 14, 15);
                doc.setFontSize(10);
                doc.text(`Fecha de exportación: ${new Date().toLocaleDateString()}`, 14, 22);
                doc.text(`Total de proveedores: ${proveedorCompleto.length}`, 14, 28);
                
                const headers = [
                    ['Código', 'Proveedor', 'Ruc', 'Teléfono', 'Dirección']
                ];
                
                const body = proveedorCompleto.map(prove => [
                    prove.cod_proveedor,
                    prove.razon_social,
                    prove.ruc,
                    prove.telefono,
                    prove.direccion
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
                doc.save(`Proveedores_Completo.pdf`);
                
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