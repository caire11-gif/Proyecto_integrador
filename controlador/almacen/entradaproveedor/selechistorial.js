// Llamando a la función getData() al cargar la página
document.addEventListener("DOMContentLoaded", getData);

function activarBotonesVerDetalles() {
    document.querySelectorAll('.btnVerDetalles').forEach(boton => {

        boton.addEventListener('click', () => {

            const codCompra = boton.dataset.codcompra;

            // Mostrar loading
            document.getElementById('detallesCompraContent').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2">Cargando detalles de la compra...</p>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('modalDetallesCompra'));
            modal.show();
            
            fetch('../../modelo/almacen/entradaproveedor/obtener_detalles_compra.php?cod_compra=' + codCompra)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor: ' + response.status);
                    }
                    return response.text();
                })
                .then(data => {
                    document.getElementById('detallesCompraContent').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('detallesCompraContent').innerHTML = 
                        '<div class="alert alert-danger text-center">' +
                        '<i class="fas fa-exclamation-triangle fa-2x mb-3"></i>' +
                        '<h5>Error al cargar los detalles</h5>' +
                        '<p class="mb-0">' + error.message + '</p>' +
                        '</div>';
                });

        });
    });
}

// Función para obtener datos con AJAX
function getData() {
    let input = document.getElementById("buscarHistorial").value
    let num_registros = document.getElementById("num_registros").value
    let content = document.getElementById("content")
    let pagina = document.getElementById("pagina").value || 1;
    let orderCol = document.getElementById("orderCol").value
    let orderType = document.getElementById("orderType").value

    let formaData = new FormData()
    formaData.append('buscarHistorial', input)
    formaData.append('registros', num_registros)
    formaData.append('pagina', pagina)
    formaData.append('orderCol', orderCol)
    formaData.append('orderType', orderType)

    fetch("../../modelo/almacen/entradaproveedor/selechistorial.php", {
            method: "POST",
            body: formaData
        })
        .then(response => response.json())
        .then(data => {
            content.innerHTML = data.data
            document.getElementById("lbl-total").innerHTML = `Mostrando ${data.totalFiltro} de ${data.totalRegistros} registros`;
            document.getElementById("nav-paginacion").innerHTML = data.paginacion

            activarBotonesVerDetalles()

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
document.getElementById("buscarHistorial").addEventListener("keyup", resetPagina);
document.getElementById("num_registros").addEventListener("change", getData);

// Event listener para ordenar las columnas
let columns = document.querySelectorAll(".sort");
columns.forEach(column => {
    column.addEventListener("click", ordenar);
});