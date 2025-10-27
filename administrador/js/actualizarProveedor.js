document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalActualizarProveedor');

    modal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        // Obtiene los datos del botón
        const cod = button.getAttribute('data-cod');
        const nombre = button.getAttribute('data-nombre');
        const telefono = button.getAttribute('data-telefono');
        const direccion = button.getAttribute('data-direccion');

        // Llena los campos del modal
        document.getElementById('cod_proveedor_edit').value = cod;
        document.getElementById('nombre_edit').value = nombre;
        document.getElementById('telefono_edit').value = telefono;
        document.getElementById('direccion_edit').value = direccion;
    });
});