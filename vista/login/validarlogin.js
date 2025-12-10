document.getElementById('loginForm').addEventListener('submit', function(event){
    const username = document.getElementById('usuario').value.trim();
    const password = document.getElementById('contraseña').value.trim();

    if(username===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "El usuario no puede estar vacío",
            width: "350px",
        });
        event.preventDefault();
        return;
    }

    if(password===""){
        Swal.fire({
            icon: "warning",
            title: "Oops...",
            text: "La contraseña no puede estar vacía",
            width: "350px",
        });
        event.preventDefault();
        return;
    }
});