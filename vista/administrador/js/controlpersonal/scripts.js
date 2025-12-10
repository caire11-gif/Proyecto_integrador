//============================================================================================
//                                        USUARIOS
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function() {
        // Realizar la llamada AJAX al archivo PHP
        fetch('../../modelo/administrador/usuarioinfo.php')
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
//                                     BARRA LATERAL
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
//============================================================================================
//                                    PARA EMPLEADOS
//============================================================================================
    const filtroEmpleados=document.getElementById('campo');
    const tablaEmpleados=document.getElementById('content');
    const filaEmpleados=tablaEmpleados.getElementsByTagName('tr');

    filtroEmpleados.addEventListener('input', function(){
        const filtro = this.value.toLowerCase();

        for (let i = 0; i < filaEmpleados.length; i++) {
            const fila = filaEmpleados[i];
            const celdas = fila.getElementsByTagName('td');
            let mostrarFila = false;

            if (celdas.length >= 2) {
                const codigo = celdas[0].textContent.toLowerCase();
                const nombre = celdas[1].textContent.toLowerCase();
                const dni = celdas[2].textContent.toLowerCase();        

                if (codigo.includes(filtro) || nombre.includes(filtro) || dni.includes(filtro)) {
                    mostrarFila = true;
                }
            }
            fila.style.display = mostrarFila ? '' : 'none';
        }
    });

//============================================================================================
//                                     PARA USUARIOS
//============================================================================================
    const filtroUsuario=document.getElementById('campo_usu');
    const tablaUsuario=document.getElementById('contentusu');
    const filaUsuario=tablaUsuario.getElementsByTagName('tr');

    filtroUsuario.addEventListener('input', function(){
        const filtro = this.value.toLowerCase();

        for (let i = 0; i < filaUsuario.length; i++) {
            const fila = filaUsuario[i];
            const celdas = fila.getElementsByTagName('td');
            let mostrarFila = false;

            if (celdas.length >= 2) {
                const codigousu = celdas[0].textContent.toLowerCase();
                const codigoemp = celdas[1].textContent.toLowerCase();     

                if (codigousu.includes(filtro) || codigoemp.includes(filtro)) {
                    mostrarFila = true;
                }
            }                
            fila.style.display = mostrarFila ? '' : 'none';
        }
    });