<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class EliminarEmpDao{
    public function eliminar($codemp){
        $conexion=Conexion::getConexion();
        
        $codemp = $_GET['codemp'];
    
        echo $codemp;

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

        $this->alertSuccess("Empleado eliminado correctamente.");
        exit;
    }

    private function alertSuccess($msg){
        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: '$msg'
                }).then(() => {
                    window.location.href = '../../../../vista/administrador/controlpersonal.html';
                });
            </script>
        ";
        exit;
    }
}
?>