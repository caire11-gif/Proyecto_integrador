<?php include('../../../login/ingresarlogin.php') ?>

<?php
$codemp = $_GET['cod_empleado'];

if($_SERVER['REQUEST_METHOD']==='GET'){
    $cod=pg_query_params($conexion,"SELECT cod_usuario FROM usuario WHERE cod_empleado=$1",array($codemp));

    while($code=pg_fetch_assoc($cod)){
        $con=trim($code['cod_usuario']);

        pg_query_params($conexion,"DELETE FROM compra WHERE cod_usuario=$1",array($con));
        pg_query_params($conexion,"DELETE FROM venta WHERE cod_usuario=$1",array($con));
        pg_query_params($conexion,"DELETE FROM login WHERE cod_usuario=$1",array($con));
        pg_query_params($conexion,"DELETE FROM movimiento WHERE cod_usuario=$1",array($con));
        pg_query_params($conexion,"DELETE FROM registroinventario WHERE cod_usuario=$1",array($con));
        pg_query_params($conexion,"DELETE FROM notificacion WHERE cod_usuario=$1",array($con));
        pg_query_params($conexion,"DELETE FROM reporte WHERE cod_usuario=$1",array($con));
        pg_query_params($conexion,"DELETE FROM historialproductos WHERE cod_usuario=$1",array($con));
        
        $borrarusuario=pg_query_params($conexion,"DELETE FROM usuario WHERE cod_empleado=$1",array($codemp));
        if(!$borrarusuario){
            echo "Error al borrar el usuario";
        }
    }

    $borrarempleado=pg_query_params($conexion,"DELETE FROM empleado WHERE cod_empleado=$1",array($codemp));
    if(!$borrarempleado){
        echo "Error al borrar el empleado";
        exit;
    }

    echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                Swal.fire({
                    icon: 'success',
                    title: 'Empleado eliminado',
                    text: 'Se eliminó el empleado correctamente',
                    width: '350px'
                }).then(() => {
                    window.location.href = '../../../administrador/controlpersonal.html';
                });
            });
        </script>
    ";
}
?>