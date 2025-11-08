// Buscar productos en tiempo real
document.getElementById('buscarProducto').addEventListener('input', aplicarFiltros);
document.getElementById('filtroCategoria').addEventListener('change', aplicarFiltros);
document.getElementById('filtroProveedor').addEventListener('change', aplicarFiltros);

function aplicarFiltros() {
    const searchTerm = document.getElementById('buscarProducto').value.toLowerCase();
    const categoriaValue = document.getElementById('filtroCategoria').value;
    const proveedorValue = document.getElementById('filtroProveedor').value;
            
    const categoriaText = categoriaValue ? 
    document.getElementById('filtroCategoria').options[document.getElementById('filtroCategoria').selectedIndex].textContent : '';
    const proveedorText = proveedorValue ? 
    document.getElementById('filtroProveedor').options[document.getElementById('filtroProveedor').selectedIndex].textContent : '';
            
    const rows = document.querySelectorAll('tbody tr');
            
    rows.forEach(row => {
        if (row.cells.length < 8) return;
                
        const nombreProducto = row.cells[1].textContent.toLowerCase();
        const codigoProducto = row.cells[0].textContent.toLowerCase();
        const categoriaProducto = row.cells[6].textContent;
        const proveedorProducto = row.cells[7].textContent;
                
        const matchSearch = !searchTerm || nombreProducto.includes(searchTerm) || codigoProducto.includes(searchTerm);
        const matchCategoria = !categoriaValue || categoriaProducto === categoriaText;
        const matchProveedor = !proveedorValue || proveedorProducto === proveedorText;
                
        row.style.display = (matchSearch && matchCategoria && matchProveedor) ? '' : 'none';
    });
}

document.getElementById('btnLimpiarFiltros').addEventListener('click', function() {
    document.getElementById('buscarProducto').value = '';
    document.getElementById('filtroCategoria').selectedIndex = 0;
    document.getElementById('filtroProveedor').selectedIndex = 0;
            
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.style.display = '';
    });
});