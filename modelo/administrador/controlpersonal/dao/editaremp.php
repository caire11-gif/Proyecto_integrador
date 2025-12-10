<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class ActualizarEmpDao{
    public function actualizar(EmpleadoActualizarDto $dto){
        $conexion=Conexion::getConexion();

        $codactuemp=$dto->getCodigo();
        $nomactuemp=$dto->getNombre();
        $apeactuemp=$dto->getApellido();        
        $dniactuemp=$dto->getDni();
        $fecnacactuemp=$dto->getFecha();
        $telactuemp=$dto->getTelefono();
        $rolactuemp=$dto->getRol();

        echo $codactuemp;

        $telactuemp = str_replace(' ', '', $telactuemp);
        $dniactuemp=str_replace(' ','',$dniactuemp);

        $actualizar=pg_query_params($conexion,"UPDATE empleado SET nombre=$2,apellido=$3,dni=$4,telefono=$5,fecha_nacimiento=$6,cod_rol=$7 WHERE cod_empleado=$1",
                array($codactuemp,$nomactuemp,$apeactuemp,$dniactuemp,$telactuemp,$fecnacactuemp,$rolactuemp));
        if(!$actualizar){
            echo "Error al actualizar el empleado";
            exit;
        }

        $this->alertSuccess("Se actualizó el empleado correctamente");
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