document.getElementById("formularioCambiarContraseña").addEventListener("submit", function(event){
    const contraseña=document.getElementById('contraseñaActual').value.trim();
    const cambicontra=document.getElementById('nuevaContraseña').value.trim();
    const confircontra=document.getElementById('confirmarContraseña').value.trim();

    if(cambicontra===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "La nueva contraseña  no puede estar vacía",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(cambicontra===contraseña){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "La nueva contraseña  no puede ser igual a la contraseña actual",
            width: "350px",
        });
        event.preventDefault();
        return;
    }

    if(confircontra===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "La confirmación de contraseña no puede estar vacía",
            width: "350px",
        });
        event.preventDefault();
        return;
    } else if(confircontra!==cambicontra){
       Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "La confirmación de contraseña debe ser igual a la nueva contraseña",
            width: "350px",
        });
        event.preventDefault();
        return;
    }
});