<?php include('../../../login/ingresarlogin.php') ?>

<?php
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='actualizar')){
    $codactprove=$_POST['codigoActualizarProveedor'] ?? '';
    $nomactprove=$_POST['nombreActualizarProveedor'] ?? '';
    $telactprove=$_POST['telefonoActualizarProveedor'] ?? '';
    $diractprove=$_POST['direccionActualizarProveedor'] ?? '';

    $telactprove = str_replace(' ', '', $telactprove);

    $sql3="UPDATE proveedor SET nombre=$2, telefono=$3, direccion=$4 WHERE cod_proveedor=$1";

    $result6=pg_query_params($conexion,$sql3,array($codactprove,$nomactprove,$telactprove,$diractprove));

    if(!$result6){
        echo "Error al seleccionar proveedor.";
        exit;
    }

    echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                Swal.fire({
                    icon: 'success',
                    title: 'Proveedor actualizado',
                    text: 'Se actualizó el proveedor correctamente',
                    width: '350px'
                }).then(() => {
                    window.location.href = '../../../administrador/proveedores.html';
                });
            });
        </script>
    ";
}
?>