document.getElementById("formularioProveedor").addEventListener("submit", function(event) {
    const nomprove=document.getElementById("razonSocialProveedor").value.trim();
    const ruceprove=document.getElementById('rucProveedor').value.trim();
    const teleprove=document.getElementById("telefonoProveedor").value.trim();
    const dirprove=document.getElementById("direccionProveedor").value.trim();

    const rucprove=ruceprove.replace(/\s+/g, '');
    const telprove = teleprove.replace(/\s+/g, '');

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

    if(rucprove===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El ruc no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(rucprove.length!==11 || isNaN(rucprove)){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El ruc debe tener 11 números",
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

    if(dirprove===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "la dirección no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    }
});