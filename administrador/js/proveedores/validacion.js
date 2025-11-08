document.getElementById("formularioProveedor").addEventListener("submit", function(event) {
            document.querySelectorAll(".text-danger").forEach(el => el.textContent = "");

            const codprove=document.getElementById("codigoProveedor").value.trim();
            const nomprove=document.getElementById("nombreProveedor").value.trim();
            const telprove=document.getElementById("telefonoProveedor").value.trim();
            const dirprove=document.getElementById("direccionProveedor").value.trim();

            if (codprove === " ") {
                alert("El código no puede estar vacío.");
                event.preventDefault();
                return;
            }  

            const regexnom = /^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

            if(nomprove===" "){
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: "El nombre no puede estar vacío",
                });
                event.preventDefault();
                return;
            } else if (!regexnom.test(nomprove)) {
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El nombre debe empezar con mayúscula y contener solo letras.",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(telprove===" "){
                alert("El teléfono no puede estar vacío.");
                event.preventDefault();
                return;
            } else if(telprove[0]!=='9' || telprove.length!==9 || isNan(telprove)){
                alert("El teléfono debe empezar con 9 y tener 9 números.");
                event.preventDefault();
                return;
            }

            if(dirprove===" "){
                alert("La dirección no puede estar vacía.");
                event.preventDefault();
                return;
            }
        });