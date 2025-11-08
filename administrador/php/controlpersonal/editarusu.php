<?php include('../../../login/ingresarlogin.php') ?>

<?php
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='actualizar')){
    $codusu=$_POST['codigoActualizarUsuario'] ?? '';
    $estado=$_POST['cambiarEstadoUsuario'] ?? '';

    $actualizar=pg_query_params($conexion,"UPDATE usuario SET cod_estadousuario=$2 WHERE cod_usuario=$1",array($codusu,$estado));

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
                    title: 'Usuario actualizado',
                    text: 'Se actualizó el estado del usuario correctamente',
                    width: '350px'
                }).then(() => {
                    window.location.href = '../../../administrador/controlpersonal.html';
                });
            });
        </script>
    ";
}
?>