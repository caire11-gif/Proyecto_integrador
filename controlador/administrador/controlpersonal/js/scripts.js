//============================================================================================
//                                        CARDS
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/administrador/controlpersonal/php/procesarSeleccionCards.php')
            .then(function(response){
                if(!response.ok){
                    throw new Error("Error con la solicitud");
                }
                
                return response.json();
            })
            .then(function(data){
                document.getElementById('cantEmp').innerHTML=data.cantidad_empleado;
                document.getElementById('cantUsu').innerHTML=data.cantidad_usuario;
                document.getElementById('usuActi').innerHTML=data.usuario_activo;
                document.getElementById('usuInacti').innerHTML=data.usuario_inactivo;
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });

//============================================================================================
//                                      OPCIONES
//============================================================================================
//============================================================================================
//                              PARA EL ROL DEL EMPLEADO
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/administrador/controlpersonal/php/procesarRol.php')
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
                const select = document.getElementById('rolEmpleado');
                data.forEach(rol => {
                    const option = document.createElement('option');
                    option.value = rol.cod_rol;
                    option.textContent = rol.nombre;
                    select.appendChild(option);
                });
            })
            .catch(function(error){
            console.error('Error al cargar los datos de los roles: ',error);
            })
    });

//============================================================================================
//                         PARA ACTUALIZAR EL ROL DEL EMPLEADO
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/administrador/controlpersonal/php/procesarRol.php')
            .then(function(response){
                const contentType = response.headers.get('content-type');
                
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new TypeError("Response wasn't JSON: " + text);
                    });
                }

                return response.json();
            })
            .then(data1=>{
                const select1=document.getElementById('rolActualizarEmpleado');
                data1.forEach(rol=>{
                    const option1=document.createElement('option');
                    option1.value=rol.cod_rol;
                    option1.textContent=rol.nombre;
                    select1.appendChild(option1);
                });
            })
            .catch(function(error){
                console.error('Error al cargar los datos de los proveedores: ',error);
            })
    });
//============================================================================================
//                         PARA ACTUALIZAR EL ESTADO DEL USUARIO
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function(){
        fetch('../../controlador/administrador/controlpersonal/php/procesarEstado.php')
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
                const select = document.getElementById('cambiarEstadoUsuario');
                data.forEach(rol => {
                    const option = document.createElement('option');
                    option.value = rol.cod_estadousuario;
                    option.textContent = rol.nombre;
                    select.appendChild(option);
                });
            })
            .catch(function(error){
            console.error('Error al cargar los datos del estado del usuario: ',error);
            })
    });

//============================================================================================
//                                           EXPORTAR 
//============================================================================================
//============================================================================================
//                                        PARA EMPLEADOS   
//============================================================================================
//============================================================================================
//                                          PARA EXCEL
//============================================================================================
    document.getElementById('exportarexcelemp').addEventListener('click', function(btnElement){
        const originalText = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
        btnElement.disabled = true;

        fetch(`../../controlador/administrador/controlpersonal/php/procesarEstado.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(empleadoCompleto => {
                const workbook = XLSX.utils.book_new();            
                const data = [];
                const headers = ['Código', 'Empleado', 'Dni', 'Fecha Nacimiento', 'Teléfono', 'Rol'];

                data.push(headers);

                empleadoCompleto.forEach(emp => {
                    const rowData = [
                        emp.cod_empleado,
                        emp.nombre+emp.apellido,
                        emp.dni,
                        emp.fecha_nacimiento,
                        emp.telefono,
                        emp.rolnombre
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
                    { wch: 20 }
                ];

                XLSX.utils.book_append_sheet(workbook, worksheet, 'Empleados');

                XLSX.writeFile(workbook, `Empleados_Completo.xlsx`);

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
    document.getElementById('exportarpdfemp').addEventListener('click', function(btnElement){
        const originalText = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
        btnElement.disabled = true;
    
        fetch(`../../controlador/administrador/controlpersonal/php/procesarEstado.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(empleadoCompleto => {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('l', 'mm', 'a4');

                // Título del documento
                doc.setFontSize(16);
                doc.text(`Empleados Completos`, 14, 15);
                doc.setFontSize(10);
                doc.text(`Fecha de exportación: ${new Date().toLocaleDateString()}`, 14, 22);
                doc.text(`Total de empleado: ${empleadoCompleto.length}`, 14, 28);

                const headers = [
                    ['Código', 'Empleado', 'Dni', 'Fecha Nacimiento', 'Teléfono', 'Rol']
                ];

                const body = empleadoCompleto.map(emp => [
                    emp.cod_empleado,
                    emp.nombre+emp.apellido,
                    emp.dni,
                    emp.fecha_nacimiento,
                    emp.telefono,
                    emp.rolnombre
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
                doc.save(`Empleados_Completo.pdf`);

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
//                                       PARA USUARIOS   
//============================================================================================
//============================================================================================
//                                         PARA EXCEL
//============================================================================================
    document.getElementById('exportarexcelusu').addEventListener('click', function(btnElement){
        const originalText = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
        btnElement.disabled = true;

        fetch(`../../controlador/administrador/controlpersonal/php/procesarExportarUsu.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(usuarioCompleto => {
                const workbook = XLSX.utils.book_new();

                const data = [];

                const headers = ['Código Usuario', 'Código Empleado', 'Usuario', 'Contraseña', 'Estado'];

                data.push(headers);

                usuarioCompleto.forEach(usu => {
                    const rowData = [
                        usu.cod_usuario,
                        usu.cod_empleado,
                        usu.usuario,
                        usu.contraseña,
                        usu.estadousuarionombre
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

                XLSX.utils.book_append_sheet(workbook, worksheet, 'Usuarios');

                XLSX.writeFile(workbook, `Usuarios_Completo.xlsx`);

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
//                                         PARA PDF
//============================================================================================
    document.getElementById('exportarpdfusu').addEventListener('click', function(btnElement){
        const originalText = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
        btnElement.disabled = true;

        fetch(`../../controlador/administrador/controlpersonal/php/procesarExportarUsu.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(usuarioCompleto => {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('l', 'mm', 'a4');
                    
                // Título del documento
                doc.setFontSize(16);
                doc.text(`Usuarios Completos`, 14, 15);
                doc.setFontSize(10);
                doc.text(`Fecha de exportación: ${new Date().toLocaleDateString()}`, 14, 22);
                doc.text(`Total de usuarios: ${usuarioCompleto.length}`, 14, 28);

                const headers = [
                    ['Código Usuario', 'Código Empleado', 'Usuario', 'Contraseña', 'Estado']
                ];

                const body = usuarioCompleto.map(usu => [
                    usu.cod_usuario,
                    usu.cod_empleado,
                    usu.usuario,
                    usu.contraseña,
                    usu.estadousuarionombre
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
                doc.save(`Usuarios_Completo.pdf`);

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
//                                SELECCIÓN PARA LAS TABLAS
//============================================================================================
//============================================================================================
//                                  PARA LOS EMPLEADOS
//============================================================================================
    document.addEventListener("DOMContentLoaded", getDataEmpleado);

    function activarBotonesEliminar() {
        document.querySelectorAll('.btnEliminar').forEach(boton => {
            boton.addEventListener('click', () => {
                const codemp = boton.dataset.codemp;

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
                                    `../../controlador/administrador/controlpersonal/php/procesarEliminarEmp.php?codemp=${encodeURIComponent(codemp)}`;
                            }
                        });
                    }
                });
            });
        });
    }

    function activarBotonActualizar(){
        document.querySelectorAll('.btnActualizarEmpleado').forEach(boton =>{
            boton.addEventListener('click', () => {
                document.getElementById('codigoActualizarEmpleado').value = boton.dataset.codemp;
                document.getElementById('nombreActualizarEmpleado').value = boton.dataset.nombre;
                document.getElementById('apellidoActualizarEmpleado').value=boton.dataset.apellido;
                document.getElementById('fechaNacActualizarEmpleado').value=boton.dataset.fechanac;
                document.getElementById('dniActualizarEmpleado').value=boton.dataset.dni;
                document.getElementById('telefonoActualizarEmpleado').value = boton.dataset.telefono;
                document.getElementById('rolActualizarEmpleado').value = boton.dataset.codrol;
                
                const modal = new bootstrap.Modal(document.getElementById('modalActualizarEmpleado'));
                modal.show();
            });
        })
    }

    // Función para obtener datos con AJAX
    function getDataEmpleado() {
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

        fetch("../../controlador/administrador/controlpersonal/php/procesarSeleccionarEmpleado.php", {
                method: "POST",
                body: formaData
            })
            .then(response => response.json())
            .then(data => {
                content.innerHTML = data.data;
                document.getElementById("lbl-total").innerHTML = `Mostrando ${data.totalFiltro} de ${data.totalRegistros} registros`;
                document.getElementById("nav-paginacion").innerHTML = data.paginacion

                activarBotonActualizar();
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
        getDataEmpleado()
    }

    // Función para ordenar columnas
    function ordenar(e) {
        let elemento = e.target;
        let orderType = elemento.classList.contains("asc") ? "desc" : "asc";

        document.getElementById('orderCol').value = elemento.cellIndex;
        document.getElementById("orderType").value = orderType;
        elemento.classList.toggle("asc");
        elemento.classList.toggle("desc");

        getDataEmpleado()
    }

    // Función para cambiar de página
    function resetPagina() {
        document.getElementById('pagina').value = 1
        getDataEmpleado()
    }

    // Event listeners para los eventos de cambio en el campo de entrada y el select
    document.getElementById("campo").addEventListener("keyup", resetPagina);
    document.getElementById("num_registros").addEventListener("change", getDataEmpleado);

    // Event listener para ordenar las columnas
    let columns = document.querySelectorAll(".sort");
    columns.forEach(column => {
        column.addEventListener("click", ordenar);
    });

//============================================================================================
//                                  PARA LOS USUARIOS
//============================================================================================
    document.addEventListener("DOMContentLoaded", getDataUsuario);

    function activarCambiarContraseña(){
        document.querySelectorAll('.btnCambiarContraseña').forEach(boton=>{
            boton.addEventListener('click',()=>{
                document.getElementById('codigoUsuario').value=boton.dataset.codusucont;
                document.getElementById('contraseñaActual').value=boton.dataset.contraseña;

                const modalcontra=new bootstrap.Modal(document.getElementById('modalCambiarContraseña'));
                modalcontra.show();
            })
        })
    }

    function activarActualizarEstado(){
        document.querySelectorAll('.btnActualizar').forEach(boton =>{
            boton.addEventListener('click', () => {
                document.getElementById('codigoActualizarUsuario').value = boton.dataset.codusu;
                document.getElementById('cambiarEstadoUsuario').value = boton.dataset.codestado;

                const modal = new bootstrap.Modal(document.getElementById('modalActualizarUsuario'));
                modal.show();
            });
        })
    }

    // Función para obtener datos con AJAX
    function getDataUsuario() {
        let input = document.getElementById("campo_usu").value
        let num_registros = document.getElementById("num_registros_usu").value
        let contentusu = document.getElementById("contentusu")
        let pagina = document.getElementById("pagina_usu").value || 1;
        let orderCol = document.getElementById("orderCol_usu").value
        let orderType = document.getElementById("orderType_usu").value
        
        let formaData = new FormData()
        formaData.append('campo_usu', input)
        formaData.append('registros_usu', num_registros)
        formaData.append('pagina_usu', pagina)
        formaData.append('orderCol_usu', orderCol)
        formaData.append('orderType_usu', orderType)

        fetch("../../controlador/administrador/controlpersonal/php/procesarSeleccionarUsuario.php", {
                method: "POST",
                body: formaData
            })
            .then(response => response.json())
            .then(data => {
                contentusu.innerHTML = data.data;
                document.getElementById("lbl-total_usu").innerHTML = `Mostrando ${data.totalFiltro} de ${data.totalRegistros} registros`;
                document.getElementById("nav-paginacion_usu").innerHTML = data.paginacion

                activarActualizarEstado();
                activarCambiarContraseña();

                // Si la página actual no tiene resultados, ajustar la paginación para mostrar la primera página
                if (data.data.includes('Sin resultados') && parseInt(pagina) !== 1) {
                    nextPage(1); // Ir a la primera página
                }
            })
            .catch(err => console.log(err))
    }

    // Función para cambiar de página
    function nextPage(pagina) {
        document.getElementById('pagina_usu').value = pagina
        getDataUsuario()
    }

    // Función para ordenar columnas
    function ordenar(e) {
        let elemento = e.target;
        let orderType = elemento.classList.contains("asc") ? "desc" : "asc";

        document.getElementById('orderCol_usu').value = elemento.cellIndex;
        document.getElementById("orderType_usu").value = orderType;
        elemento.classList.toggle("asc");
        elemento.classList.toggle("desc");

        getDataUsuario()
    }

    // Función para cambiar de página
    function resetPagina() {
        document.getElementById('pagina_usu').value = 1
        getDataUsuario()
    }

    // Event listeners para los eventos de cambio en el campo de entrada y el select
    document.getElementById("campo_usu").addEventListener("keyup", resetPagina);
    document.getElementById("num_registros_usu").addEventListener("change", getDataUsuario);

//============================================================================================
//                                     VALIDACIONES
//============================================================================================
//============================================================================================
//                                  PARA LOS EMPLEADOS
//============================================================================================
    document.getElementById("formularioEmpleado").addEventListener("submit", function(event) {
        const nomemp=document.getElementById('nombreEmpleado').value.trim();
        const apeemp=document.getElementById('apellidoEmpleado').value.trim();
        const dniempl=document.getElementById('dniEmpleado').value.trim();
        const telempl=document.getElementById('telefonoEmpleado').value.trim();
        const fecnacemp=document.getElementById('fechaNacEmpleado').value.trim();

        const telemp = telempl.replace(/\s+/g, '');
        const dniemp=dniempl.replace(/\s+/g, '');

        const regexnomape = /^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

        if(nomemp===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El nombre no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if (!regexnomape.test(nomemp)) {
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El nombre debe empezar con mayúscula y contener solo letras",
                width: "350px",
            });
            event.preventDefault();
            return;
        }

        if(apeemp===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El apellido no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if (!regexnomape.test(apeemp)) {
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El apellido debe empezar con mayúscula y contener solo letras",
                width: "350px",
            });
            event.preventDefault();
            return;
        }

        if(telemp===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El teléfono no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if(telemp[0]!=='9' || telemp.length!==9 || isNaN(telemp)){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El teléfono debe empezar con 9 y tener 9 números",
                width: "350px",
            });
            event.preventDefault();
            return;
        }

        if(dniemp===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El dni no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if(dniemp.length!==8 || isNaN(dniemp)){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El dni debe tener 8 números",
                width: "350px",
            });
            event.preventDefault();
            return;
        }

        const hoy = new Date();
        const fecha = new Date(fecnacemp);

        if (fecha > hoy) {
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "La fecha no puede ser superior a la de hoy",
                width: "350px",
            });
            event.preventDefault();
            return;
        }
    });

//============================================================================================
//                                PARA CAMBIO DE CONTRASEÑA
//============================================================================================
    document.getElementById("formularioCambiarContraseña").addEventListener("submit", function(event){
        const contraseña=document.getElementById('contraseñaActual').value.trim();
        const cambicontra=document.getElementById('nuevaContraseña').value.trim();
        const confircontra=document.getElementById('confirmarContraseña').value.trim();

        if(cambicontra===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "La nueva contraseña  no puede estar vacía",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if(cambicontra===contraseña){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "La nueva contraseña  no puede ser igual a la contraseña actual",
                width: "350px",
            });
            event.preventDefault();
            return;
        }

        if(confircontra===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "La confirmación de contraseña no puede estar vacía",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if(confircontra!==cambicontra){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "La confirmación de contraseña debe ser igual a la nueva contraseña",
                width: "350px",
            });
            event.preventDefault();
            return;
        }
    });

//============================================================================================
//                                 PARA ACTUALIZAR LOS EMPLEADOS
//============================================================================================
    document.getElementById("formularioActualizarEmpleado").addEventListener("submit", function(event) {
        const nomactuemp=document.getElementById('nombreActualizarEmpleado').value.trim();
        const apeactuemp=document.getElementById('apellidoActualizarEmpleado').value.trim();
        const dniactuemp=document.getElementById('dniActualizarEmpleado').value.trim();
        const telactuemp=document.getElementById('telefonoActualizarEmpleado').value.trim();
        const fecnacactuemp=document.getElementById('fechaNacActualizarEmpleado').value.trim();
        
        const telemp = telactuemp.replace(/\s+/g, '');
        const dniemp=dniactuemp.replace(/\s+/g, '');

        const regexnomape = /^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

        if(nomactuemp===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El nombre no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if (!regexnomape.test(nomactuemp)) {
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El nombre debe empezar con mayúscula y contener solo letras",
                width: "350px",
            });
            event.preventDefault();
            return;
        }

        if(apeactuemp===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El apellido no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if (!regexnomape.test(apeactuemp)) {
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El apellido debe empezar con mayúscula y contener solo letras",
                width: "350px",
            });
            event.preventDefault();
            return;
        }

        if(telemp===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El teléfono no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if(telemp[0]!=='9' || telemp.length!==9 || isNaN(telemp)){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El teléfono debe empezar con 9 y tener 9 números",
                width: "350px",
            });
            event.preventDefault();
            return;
        }

        if(dniemp===""){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El dni no puede estar vacío",
                width: "350px",
            });
            event.preventDefault();
            return;
        } else if(dniemp.length!==8 || isNaN(dniemp)){
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "El dni debe tener 8 números",
                width: "350px",
            });
            event.preventDefault();
            return;
        }

        const hoy = new Date();
        const fecha = new Date(fecnacactuemp);

        if (fecha > hoy) {
            Swal.fire({
                icon: "warning",
                title: "Oops...",
                text: "La fecha no puede ser superior a la de hoy",
                width: "350px",
            });
            event.preventDefault();
            return;
        }
    });