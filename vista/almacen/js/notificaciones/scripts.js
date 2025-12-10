//============================================================================================
//                                        USUARIOS
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function() {
        // Realizar la llamada AJAX al archivo PHP
        fetch('../../modelo/almacen/usuarioinfo.php')
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
//                                   BARRA LATERAL
//============================================================================================
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const barraLateral = document.getElementById('barra-lateral');
        
    if(mobileMenuBtn && barraLateral) {
        mobileMenuBtn.addEventListener('click', () => {
            barraLateral.classList.toggle('mobile-open');
        });
    }

//============================================================================================
//                                 LIMPIAR FILTROS
//============================================================================================
    document.getElementById('btnLimpiarFiltros').addEventListener('click', function(event) {
        document.getElementById('filtroEstado').selectedIndex = 0;
        document.getElementById('filtroProveedor').selectedIndex = 0;

        const rows = document.querySelectorAll('.list-group-item');
        rows.forEach(row => {
            row.style.display = '';
        });
        event.preventDefault();
    });

//============================================================================================
//                                     FILTROS
//============================================================================================
//============================================================================================
//                                   PARA ESTADO
//============================================================================================
    const filtro=document.getElementById('filtroEstado').addEventListener('change', aplicarFiltros);

    function aplicarFiltros() {
        const estadoValue = document.getElementById('filtroEstado').value;
            
        const estadoText = estadoValue ? 
            document.getElementById('filtroEstado').options[document.getElementById('filtroEstado').selectedIndex].textContent.trim() : '';
        
        const rows = document.querySelectorAll('.list-group-item');

        rows.forEach(row => {
            // Encontramos las badges dentro del div, ya que son las que contienen el nombre del estado
            const badges = row.querySelector('span.badge .estado');

            // Verificamos si el div tiene el número adecuado de badges (2 en este caso)
            if (!badges) return;

            // Extraemos el texto del segundo badge, que es el estado
            const estadoNotificacion = badges.textContent.trim();

            // Comparamos el estado del badge con el estado seleccionado en el filtro
            const matchEstado = !estadoValue || estadoNotificacion.toLowerCase() === estadoText.toLowerCase();

            // Mostramos u ocultamos el div según el resultado de la comparación
            row.style.display = (matchEstado) ? '' : 'none';
        });
    }

//============================================================================================
//                                 PARA PROVEEDOR
//============================================================================================
    const filtroProveedor=document.getElementById('filtroProveedor').addEventListener('change', aplicarFiltrosProveedor);

    function aplicarFiltrosProveedor() {
        const proveedorValue = document.getElementById('filtroProveedor').value;
            
        const proveedorText = proveedorValue ? 
            document.getElementById('filtroProveedor').options[document.getElementById('filtroProveedor').selectedIndex].textContent : '';
        
        const rows = document.querySelectorAll('.list-group-item');

        rows.forEach(row => {
            // Encontramos las badges dentro del div, ya que son las que contienen el nombre del estado
            const span = row.querySelector('span.proveedor');

            // Verificamos si el div tiene el número adecuado de badges (2 en este caso)
           if (!span) return;

            // Extraemos el texto del segundo badge, que es el estado
            const proveedorNotificacion = span.textContent.trim();

            // Comparamos el estado del badge con el estado seleccionado en el filtro
            const matchProveedor = !proveedorValue || proveedorNotificacion === proveedorText;

            // Mostramos u ocultamos el div según el resultado de la comparación
            row.style.display = (matchProveedor) ? '' : 'none';
        });
    }