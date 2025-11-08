document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/entradaproveedor/opci.php')
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
            const select1=document.getElementById('proveedorSelect');
            const contenedorProductos=document.getElementById('productosSelect');

            const proveedores = data1.proveedores;

            proveedores.forEach(proveedor=>{
                const option1=document.createElement('option');
                option1.value=proveedor.codigo_proveedor;
                option1.textContent=proveedor.nombre_proveedor;
                select1.appendChild(option1);
            });

            select1.addEventListener('change', e => {
                const codProv = e.target.value;
                contenedorProductos.innerHTML = ''; // limpiar

                // Filtrar productos de ese proveedor
                const productosDelProveedor = data1.productos.filter(
                    prod => prod.codigo_proveedor === codProv // solo si tu JSON tiene esa clave
                );

                // Mostrar los productos (uno al lado del otro)
                productosDelProveedor.forEach(prod => {
                    const item = document.createElement('div');
                    item.textContent = prod.nombre_producto;
                    item.style.display = 'inline-block';
                    item.style.margin = '5px';
                    item.style.padding = '8px 12px';
                    item.style.backgroundColor = '#f5f5f5';
                    item.style.borderRadius = '6px';
                    contenedorProductos.appendChild(item);
                });
            });
        })
        .catch(function(error){
            console.error('Error al cargar el contenido PHP: ',error);
        })
});