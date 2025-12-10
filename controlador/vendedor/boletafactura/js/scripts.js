//============================================================================================
//                                         SELECCIÓN 
//============================================================================================
//============================================================================================
//                                      PARA HISTORIAL
//============================================================================================
    document.addEventListener("DOMContentLoaded", getData);

    function activarBotonesVerDetalles(){
        document.querySelectorAll('.btnVerDetalles').forEach(boton =>{
            boton.addEventListener('click', () => {
                
                document.getElementById('documentoVenta').innerText=boton.dataset.tipodocu;
                document.getElementById('serieVenta').innerText=boton.dataset.serie;
                document.getElementById('numeroVenta').innerText=boton.dataset.numero;
                document.getElementById('fechaVenta').innerText=boton.dataset.fecha;
                document.getElementById('horaVenta').innerText=boton.dataset.hora;
                document.getElementById('tipoClienteVenta').innerText=boton.dataset.tipocliente;
                document.getElementById('clienteVenta').innerText=boton.dataset.cliente;
                document.getElementById('tipoRucVenta').innerText=boton.dataset.tiporuc;
                document.getElementById('dniVenta').innerText=boton.dataset.dni;

                const tabla=document.getElementById('tablaDetalles');

                const data = JSON.parse(boton.dataset.productos);
                const productos=data.productos;

                console.log("Productos:", productos);
                productos.forEach(p => {
                    const fila = `
                            <tr>
                                <td>${p.cantidad_unidades}</td>
                                <td>${p.producto_nombre || 'Producto'}</td>
                                <td>S/ ${Number(p.precio_unitario).toFixed(2)}</td>
                                <td>S/ ${Number(p.total).toFixed(2)}</td>
                            </tr>
                    `;

                    tabla.insertAdjacentHTML('beforeend', fila);
                });

                document.getElementById("baseImponible").textContent = "S/ " + Number(data.base).toFixed(2);

                document.getElementById("igvTotal").textContent = "S/ " + Number(data.igv).toFixed(2);

                document.getElementById('metodoPagoVenta').innerText=boton.dataset.metpago;
                document.getElementById('usuarioVenta').innerText=boton.dataset.usuario;
                document.getElementById('finalTipoDocumento').innerHTML=boton.dataset.tipodocu;

                const modal = new bootstrap.Modal(document.getElementById('modalDetalles'));
                modal.show();              
                

                document.getElementById('documentoVentaPdf').innerText=boton.dataset.tipodocu;
                document.getElementById('serieVentaPdf').innerText=boton.dataset.serie;
                document.getElementById('numeroVentaPdf').innerText=boton.dataset.numero;
                document.getElementById('fechaVentaPdf').innerText=boton.dataset.fecha;
                document.getElementById('horaVentaPdf').innerText=boton.dataset.hora;
                document.getElementById('tipoClienteVentaPdf').innerText=boton.dataset.tipocliente;
                document.getElementById('clienteVentaPdf').innerText=boton.dataset.cliente;
                document.getElementById('tipoRucVentaPdf').innerText=boton.dataset.tiporuc;
                document.getElementById('dniVentaPdf').innerText=boton.dataset.dni;
            });
        })
    }

    function getData(){
        let num_registros = document.getElementById("num_registros").value
        let content = document.getElementById("tbodyDocumentos")
        let pagina = document.getElementById("pagina").value || 1;
        let orderCol = document.getElementById("orderCol").value
        let orderType = document.getElementById("orderType").value

        let formaData=new FormData();
        formaData.append('registros', num_registros)
        formaData.append('pagina', pagina)
        formaData.append('orderCol', orderCol)
        formaData.append('orderType', orderType)

        fetch('../../controlador/vendedor/boletafactura/php/procesarSeleccionarHist.php',{
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
                content.innerHTML=data.data;

                document.getElementById("lbl-total").innerHTML = `Mostrando ${data.totalFiltro} de ${data.totalRegistros} registros`;
                document.getElementById("nav-paginacion").innerHTML = data.paginacion
                
                activarBotonesVerDetalles();

                // Si la página actual no tiene resultados, ajustar la paginación para mostrar la primera página
                if (data.data.includes('Sin resultados') && parseInt(pagina) !== 1) {
                    nextPage(1); // Ir a la primera página
                }
            })
            .catch(function(error){
                console.error('Error al cargar los datos del historial de ventas: ',error);
            })
    }

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