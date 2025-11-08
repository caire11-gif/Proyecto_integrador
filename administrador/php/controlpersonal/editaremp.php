<?php include('../../../login/ingresarlogin.php') ?>

<?php
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='actualizar')){
    $codactuemp=$_POST['codigoActualizarEmpleado'] ?? '';
    $nomactuemp=$_POST['nombreActualizarEmpleado'] ?? '';
    $apeactuemp=$_POST['apellidoActualizarEmpleado'] ?? '';
    $dniactuemp=$_POST['dniActualizarEmpleado'] ?? '';
    $fecnacactuemp=$_POST['fechaNacActualizarEmpleado'] ?? '';
    $telactuemp=$_POST['telefonoActualizarEmpleado'] ?? '';
    $rolactuemp=$_POST['rolActualizarEmpleado'] ?? '';

    $telactuemp = str_replace(' ', '', $telactuemp);
    $dniactuemp=str_replace(' ','',$dniactuemp);

    $actualizar=pg_query_params($conexion,"UPDATE empleado SET nombre=$2,apellido=$3,dni=$4,telefono=$5,fecha_nacimiento=$6,cod_rol=$7 WHERE cod_empleado=$1",
                array($codactuemp,$nomactuemp,$apeactuemp,$dniactuemp,$telactuemp,$fecnacactuemp,$rolactuemp));
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
                    title: 'Empleado actualizado',
                    text: 'Se actualizó el empleado correctamente',
                    width: '350px'
                }).then(() => {
                    window.location.href = '../../../administrador/controlpersonal.html';
                });
            });
        </script>
    ";
}
?>