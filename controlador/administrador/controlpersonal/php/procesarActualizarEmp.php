<?php
$codactuemp=$_POST['codigoActualizarEmpleado'] ?? '';
$nomactuemp=$_POST['nombreActualizarEmpleado'] ?? '';
$apeactuemp=$_POST['apellidoActualizarEmpleado'] ?? '';
$dniactuemp=$_POST['dniActualizarEmpleado'] ?? '';
$fecnacactuemp=$_POST['fechaNacActualizarEmpleado'] ?? '';
$telactuemp=$_POST['telefonoActualizarEmpleado'] ?? '';
$rolactuemp=$_POST['rolActualizarEmpleado'] ?? '';

require_once("../../../../modelo/administrador/controlpersonal/dto/empactudto.php");

$EmpleadoActualizarDto=new EmpleadoActualizarDto(
    $codactuemp,
    $nomactuemp,
    $apeactuemp,
    $dniactuemp,
    $fecnacactuemp,
    $telactuemp,
    $rolactuemp
);

require '../../../../modelo/administrador/controlpersonal/dao/editaremp.php';
require_once ("../../../../modelo/administrador/controlpersonal/dao/editaremp.php");

$dao=new ActualizarEmpDao();
$dao->actualizar($EmpleadoActualizarDto,$conexion);
?>