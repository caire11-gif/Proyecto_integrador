document.getElementById('formularioProducto').addEventListener("submit", function(event){
    const nomprod=document.getElementById('nombreProducto').value.trim();
    const precostoprod=document.getElementById('precioCosto').value.trim();
    const preventaprod=document.getElementById('precioVenta').value.trim();
    const unicajaprod=document.getElementById('unidadesCaja').value.trim();
    const stockprod=document.getElementById('stockProducto').value.trim();

    const regexnom = /^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

    if(nomprod===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El nombre no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(!regexnom.test(nomprod)){
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

    if(precostoprod===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El precio de costo no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(!regexpre.test(precostoprod)){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "Formato inválido. Por ejemplo: 1234567890.1",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(parseFloat(precostoprod)<0){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El precio de costo no puede ser negativo",
            width: "350px",
        });
        event.preventDefault();
        return;
    }

    if(preventaprod===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El precio de venta no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(!regexpre.test(preventaprod)){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "Formato inválido. Por ejemplo: 1234567890.1",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(parseFloat(preventaprod)<0){
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

    if(unicajaprod===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "Las unidades por caja no pueden estar vacía",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(!regexunistock.test(unicajaprod)){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "Las unidades por caja deben ser solamente numérico",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(parseInt(unicajaprod)<1){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "No pueden haber menos de 1 unidad en una caja",
            width: "350px",
        });
        event.preventDefault();
        return;
    }

    if(stockprod===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El stock no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(!regexunistock.test(stockprod)){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El stock debe ser solamente numérico",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(parseInt(stockprod)<0){
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