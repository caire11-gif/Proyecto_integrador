document.getElementById("formularioActualizarEmpleado").addEventListener("submit", function(event) {
    const nomactuemp=document.getElementById('nombreActualizarEmpleado').value.trim();
    const apeactuemp=document.getElementById('apellidoActualizarEmpleado').value.trim();
    const dniactuemp=document.getElementById('dniActualizarEmpleado').value.trim();
    const telactuemp=document.getElementById('telefonoActualizarEmpleado').value.trim();
    const fecnacactuemp=document.getElementById('fechaNacActualizarEmpleado').value.trim();
    
    const telemp = telactuemp.replace(/\s+/g, '');
    const dniemp=dniactuemp.replace(/\s+/g, '');

    const regexnomape = /^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

    if(nomactuemp===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El nombre no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if (!regexnomape.test(nomactuemp)) {
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El nombre debe empezar con mayúscula y contener solo letras",
            width: "350px",
        });
        event.preventDefault();
        return;
    }

    if(apeactuemp===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El apellido no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if (!regexnomape.test(apeactuemp)) {
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
    const fecha = new Date(fecnacactuemp);

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