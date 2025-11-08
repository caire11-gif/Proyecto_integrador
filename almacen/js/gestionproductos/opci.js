document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/gestionproductos/opciactu.php')
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
            const select1=document.getElementById('categoriaProducto');
            const categorias = data1.categorias;

            categorias.forEach(categoria=>{
                const option1=document.createElement('option');
                option1.value=categoria.codigo_categoria;
                option1.textContent=categoria.nombre_categoria;
                select1.appendChild(option1);
            });

            const select2=document.getElementById('proveedorProducto');
            const proveedores=data1.proveedores;

            proveedores.forEach(proveedor=>{
                const option2=document.createElement('option');
                option2.value=proveedor.codigo_proveedor;
                option2.textContent=proveedor.nombre_proveedor;
                select2.appendChild(option2);
            });
        })
        .catch(function(error){
            console.error('Error al cargar el contenido PHP: ',error);
        })
});