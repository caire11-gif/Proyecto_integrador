<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class ActualizarProveDao{
    public function actualizar(ProveedorActualizarDto $dto){
        $conexion=Conexion::getConexion();

        $codactprove=$dto->getCodigo();
        $nomactprove=$dto->getRazonsocial();
        $rucactprove=$dto->getRuc();
        $telactprove=$dto->getTelefono();
        $diractprove=$dto->getDireccion();

        $telactprove = str_replace(' ', '', $telactprove);

        echo $codactprove;
        echo $nomactprove;
        echo $rucactprove;
        echo $telactprove;
        echo $diractprove;

        $sql3="UPDATE proveedor SET razon_social=$2, ruc=$3, telefono=$4, direccion=$5 WHERE cod_proveedor=$1";

        $result6=pg_query_params($conexion,$sql3,array($codactprove,$nomactprove,$rucactprove,$telactprove,$diractprove));

        if(!$result6){
            echo "Error al seleccionar proveedor.";
            exit;
        }

        $this->alertSuccess("Se actualizó el proveedor correctamente");
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
                    window.location.href = '../../../../vista/administrador/proveedores.html';
                });
            </script>
        ";
    }
}
?>