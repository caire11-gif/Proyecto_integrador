document.addEventListener('DOMContentLoaded', async function(){
    fetch('php/almacenproveedores/card.php')
  .then(response => response.json())
  .then(data => {
    const contenedor = document.getElementById('cardProductos');
    contenedor.innerHTML = '';

    // Agrupar por proveedor y categoría
    const agrupado = {};

    data.forEach(item => {
      const proveedor = item.proveedor;
      const categoria = item.categoria;
      const producto = item.producto;

      if (!agrupado[proveedor]) agrupado[proveedor] = {};
      if (!agrupado[proveedor][categoria]) agrupado[proveedor][categoria] = [];

      agrupado[proveedor][categoria].push(producto);
    });

    // Crear tarjetas visuales
    for (const proveedor in agrupado) {
      const cardProveedor = document.createElement('div');
      cardProveedor.classList.add('card-proveedor');

      const nombreProveedor = document.createElement('h3');
      nombreProveedor.textContent = proveedor;
      cardProveedor.appendChild(nombreProveedor);

      for (const categoria in agrupado[proveedor]) {
        const bloqueCategoria = document.createElement('div');
        bloqueCategoria.classList.add('categoria-bloque');

        const tituloCategoria = document.createElement('h5');
        tituloCategoria.textContent = categoria;
        bloqueCategoria.appendChild(tituloCategoria);

        // Contenedor horizontal de productos
        const contenedorProductos = document.createElement('div');
        contenedorProductos.classList.add('productos-grid');

        agrupado[proveedor][categoria].forEach(nombreProd => {
          const prodCard = document.createElement('div');
          prodCard.classList.add('producto-card');
          prodCard.textContent = nombreProd;
          contenedorProductos.appendChild(prodCard);
        });

        bloqueCategoria.appendChild(contenedorProductos);
        cardProveedor.appendChild(bloqueCategoria);
      }

      contenedor.appendChild(cardProveedor);
    }
  })
  .catch(error => console.error('Error al cargar los productos:', error));

});