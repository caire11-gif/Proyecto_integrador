document.getElementById("formularioEmpleado").addEventListener("submit", function(event) {
    const nomemp=document.getElementById('nombreEmpleado').value.trim();
    const apeemp=document.getElementById('apellidoEmpleado').value.trim();
    const dniempl=document.getElementById('dniEmpleado').value.trim();
    const telempl=document.getElementById('telefonoEmpleado').value.trim();
    const fecnacemp=document.getElementById('fechaNacEmpleado').value.trim();

    const telemp = telempl.replace(/\s+/g, '');
    const dniemp=dniempl.replace(/\s+/g, '');

    const regexnomape = /^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

    if(nomemp===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El nombre no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if (!regexnomape.test(nomemp)) {
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El nombre debe empezar con mayúscula y contener solo letras",
            width: "350px",
        });
        event.preventDefault();
        return;
    }

    if(apeemp===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El apellido no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if (!regexnomape.test(apeemp)) {
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El apellido debe empezar con mayúscula y contener solo letras",
            width: "350px",
        });
        event.preventDefault();
        return;
    }

    if(telemp===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El teléfono no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(telemp[0]!=='9' || telemp.length!==9 || isNaN(telemp)){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El teléfono debe empezar con 9 y tener 9 números",
            width: "350px",
        });
        event.preventDefault();
        return;
    }

    if(dniemp===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El dni no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(dniemp.length!==8 || isNaN(dniemp)){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El dni debe tener 8 números",
            width: "350px",
        });
        event.preventDefault();
        return;
    }

    const hoy = new Date();
    const fecha = new Date(fecnacemp);

    if (fecha > hoy) {
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "La fecha no puede ser superior a la de hoy",
            width: "350px",
        });
        event.preventDefault();
        return;
    }
});