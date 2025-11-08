<?php include('../../../login/ingresarlogin.php') ?>

<?php
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='actualizar')){
    $codusu=$_POST['codigoUsuario'] ?? '';
    $contraactual=$_POST['contraseñaActual'] ?? '';
    $cambiarcontra=$_POST['nuevaContraseña'] ?? '';
    $confirmarcontra=$_POST['confirmarContraseña'] ?? '';

    $actualizar=pg_query_params($conexion,"UPDATE usuario SET contraseña=$2 WHERE cod_usuario=$1",array($codusu,$cambiarcontra));
    if(!$actualizar){
        echo "Error al actualizar el empleado";
        exit;
    }

    echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                Swal.fire({
                    icon: 'success',
                    title: 'Cambio de Contraseña',
                    text: 'Se actualizó la contraseña correctamente',
                    width: '350px'
                }).then(() => {
                    window.location.href = '../../../administrador/controlpersonal.html';
                });
            });
        </script>
    ";
}
?>