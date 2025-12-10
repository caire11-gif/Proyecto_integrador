<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class CambiarContraseñaDao{
    public function actualizar(ContraseñaDto $dto){
        $conexion=Conexion::getConexion();

        $codusu=$dto->getCodigo();
        $contraactual=$dto->getContraseña();
        $cambiarcontra=$dto->getNuevacontraseña();
        $confirmarcontra=$dto->getConfirmacontraseña();

        echo $codusu;
        echo $contraactual;
        echo $cambiarcontra;
        echo $confirmarcontra;

        $actualizar=pg_query_params($conexion,"UPDATE usuario SET contraseña=$2 WHERE cod_usuario=$1",array($codusu,$cambiarcontra));
        if(!$actualizar){
            echo "Error al actualizar el empleado";
            exit;
        }

        $this->alertSuccess("Se actualizó la contraseña correctamente");
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