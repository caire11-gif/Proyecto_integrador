<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class ActualizarEstadoDao{
    public function actualizar(estadoDto $dto){
        $conexion=Conexion::getConexion();

        $codusu=$dto->getCodigo();
        $estado=$dto->getEstado();

        echo $codusu;
        echo $estado;

        $actualizar=pg_query_params($conexion,"UPDATE usuario SET cod_estadousuario=$2 WHERE cod_usuario=$1",array($codusu,$estado));

        if(!$actualizar){
            echo "Error al actualizar el empleado";
            exit;
        }

        $this->alertSuccess("Se actualizó el estado del usuario correctamente");
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
    }
}
?>