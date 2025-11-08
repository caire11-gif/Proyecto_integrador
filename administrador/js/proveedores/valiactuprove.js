document.getElementById("formularioActualizarProveedor").addEventListener("submit", function(event) {
    const codprove=document.getElementById("codigoActualizarProveedor").value.trim();
    const nomprove=document.getElementById("nombreActualizarProveedor").value.trim();
    const teleprove=document.getElementById("telefonoActualizarProveedor").value.trim();
    const dirprove=document.getElementById("direccionActualizarProveedor").value.trim();

    const telprove = teleprove.replace(/\s+/g, '');

    if (codprove === "") {
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El código no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    }  

    const regexnom = /^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

    if(nomprove===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El nombre no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if (!regexnom.test(nomprove)) {
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El nombre debe empezar con mayúscula y contener solo letras",
            width: "350px",
        });
        event.preventDefault();
        return;
    }

    if(telprove===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El teléfono no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(telprove[0]!=='9' || telprove.length!==9 || isNaN(telprove)){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El teléfono debe empezar con 9 y tener 9 números",
            width: "350px",
        });
        event.preventDefault();
        return;
    }

    if(dirprove===" "){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "La dirección no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    }
});