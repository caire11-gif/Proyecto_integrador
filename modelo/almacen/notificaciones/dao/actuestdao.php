<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class ActualizarEstadoDao{
    public function actualizar($codestnoti,$codnoti){
        $codestnoti = $_GET['codestnoti'];
        $codnoti=$_GET['codnoti'];

        echo $codestnoti;
        echo $codnoti;

        $conexion=Conexion::getConexion();

        $actualizar=pg_query_params($conexion, "UPDATE notificacion SET cod_estadonotificacion='en002' WHERE cod_estadonotificacion='en001' AND cod_notificacion=$1",array($codnoti));
        if(!$actualizar){
            echo "Error al actualizar el estado de la notificacion";
        }

        $this->alertSuccess("Estado actualizado correctamente");
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
                    window.location.href = '../../../../vista/almacen/notificaciones.html';
                });
            </script>
        ";
        exit;
    }
}
?>