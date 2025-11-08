document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/registroventa/opciven.php')
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
                option.value = empven.cod_emp;
                option.textContent = empven.nombre+" "+empven.apellido;
                select.appendChild(option);
            });
        })
        .catch(function(error){
        console.error('Error al cargar los datos de los proveedores: ',error);
        })
})