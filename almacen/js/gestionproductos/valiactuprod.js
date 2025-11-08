document.getElementById('formularioActualizarProducto').addEventListener("submit", function(event){
    const nomactuprod=document.getElementById('nombreActualizarProducto').value.trim();
    const precostoactuprod=document.getElementById('precioCostoActualizarProducto').value.trim();
    const preventaactprod=document.getElementById('precioVentaActualizarProducto').value.trim();
    const unicajaactuprod=document.getElementById('unidadesCajaActualizarProducto').value.trim();
    const stockactuprod=document.getElementById('stockActualizarProducto').value.trim();

    const regexnom = /^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

    if(nomactuprod===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El nombre no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(!regexnom.test(nomactuprod)){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El nombre debe empezar con mayúscula y contener solo letras",
            width: "350px",
        });
        event.preventDefault();
        return;
    }

    const regexpre=/^\d{1,10}(\.\d{1,2})?$/

    if(precostoactuprod===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El precio de costo no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(!regexpre.test(precostoactuprod)){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "Formato inválido. Por ejemplo: 1234567890.1",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(parseFloat(precostoactuprod)<0){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El precio de costo no puede ser negativo",
            width: "350px",
        });
        event.preventDefault();
        return;
    }

    if(preventaactprod===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El precio de venta no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(!regexpre.test(preventaactprod)){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "Formato inválido. Por ejemplo: 1234567890.1",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(parseFloat(preventaactprod)<0){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El precio de venta no puede ser negativo",
            width: "350px",
        });
        event.preventDefault();
        return;
    }

    const regexunistock=/^\d+$/;

    if(unicajaactuprod===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "Las unidades por caja no pueden estar vacía",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(!regexunistock.test(unicajaactuprod)){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "Las unidades por caja deben ser solamente numérico",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(parseInt(unicajaactuprod)<1){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "No pueden haber menos de 1 unidad en una caja",
            width: "350px",
        });
        event.preventDefault();
        return;
    }

    if(stockactuprod===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El stock no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(!regexunistock.test(stockactuprod)){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El stock debe ser solamente numérico",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(parseInt(stockactuprod)<0){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El stock no puede ser negativo",
            width: "350px",
        });
        event.preventDefault();
        return;
    }
});