//============================================================================================
//                                        USUARIOS
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function() {
        // Realizar la llamada AJAX al archivo PHP
        fetch('../../modelo/vendedor/usuarioinfo.php')
            .then(function(response){
                const contentType = response.headers.get('content-type');
                
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new TypeError("Response wasn't JSON: " + text);
                    });
                }

                return response.json();
            })
            .then(function(data1){
                document.getElementById('usuarioAvatar').innerHTML = data1.iniciales;
                document.getElementById('userName').innerHTML=data1.nombre_apellido;
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });

//============================================================================================
//                                      DROPDOWN
//============================================================================================
    const dropdownBtn = document.getElementById("dropdownBtn");
    const dropdownList = document.getElementById("dropdownList");
    const arrow = document.getElementById("arrow");

    dropdownBtn.addEventListener("click", () => {
        const isVisible = dropdownList.style.display === "block";
        dropdownList.style.display = isVisible ? "none" : "block";
        arrow.style.transform = isVisible ? "rotate(0deg)" : "rotate(180deg)";
    });
                            
    document.addEventListener("click", (e) => {
        if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
            dropdownList.style.display = "none";
            arrow.style.transform = "rotate(0deg)";
        }
    });

//============================================================================================
//                                      BARRA LATERAL
//============================================================================================
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const barraLateral = document.getElementById('barra-lateral');
        
    if(mobileMenuBtn && barraLateral) {
        mobileMenuBtn.addEventListener('click', () => {
            barraLateral.classList.toggle('mobile-open');
        });
    }

//============================================================================================
//                                        FILTROS
//============================================================================================
    document.getElementById('btnLimpiarFiltros').addEventListener('click', function() {
        document.getElementById('buscarProducto').value = '';
        document.getElementById('filtroCategoria').selectedIndex = 0;
        document.getElementById('filtroStock').selectedIndex = 0;
        aplicarFiltros();
    });

    document.getElementById('buscarProducto').addEventListener('input', aplicarFiltros);
    document.getElementById('filtroCategoria').addEventListener('change', aplicarFiltros);
    document.getElementById('filtroStock').addEventListener('change', aplicarFiltros);

    function aplicarFiltros() {
        const searchTerm = document.getElementById('buscarProducto').value.toLowerCase();
        const categoriaValue = document.getElementById('filtroCategoria').value;
        const stockValue = document.getElementById('filtroStock').value;
            
        
        const categoriaText = categoriaValue ? 
            document.getElementById('filtroCategoria').options[document.getElementById('filtroCategoria').selectedIndex].textContent : '';
            
        const rows = document.querySelectorAll('tbody tr');
        let visibleCount = 0;
            
        rows.forEach(row => {
            if (row.cells.length < 9) return;

             // "14 unidades"
            const nombreProducto = row.cells[1].textContent.toLowerCase();
            const codigoProducto = row.cells[0].textContent.toLowerCase();
            const categoriaProducto = row.cells[2].textContent; // Cambié de 6 a 7 porque agregué columna

            const stockText = row.cells[7].textContent.trim();
            const stockProducto = parseInt(stockText.replace(/\D/g, ''), 10); // Cambié de 7 a 8
                
            const matchSearch = !searchTerm || nombreProducto.includes(searchTerm) || codigoProducto.includes(searchTerm);
            const matchCategoria = !categoriaValue || categoriaProducto === categoriaText;
            
            // Filtro de stock según el valor seleccionado
            let matchStock = false;

            if (stockValue === 'Agotado' && stockProducto === 0) {
                matchStock = true;
            } else if (stockValue === 'Bajo' && stockProducto > 0 && stockProducto <= 10) {
                matchStock = true;
            } else if (stockValue === 'Normal' && stockProducto > 10) {
                matchStock = true;
            } else if (!stockValue) {
                matchStock = true; // Si no se seleccionó ningún filtro de stock, mostrar todos
            }
                
            if (matchSearch && matchCategoria && matchStock) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
    }