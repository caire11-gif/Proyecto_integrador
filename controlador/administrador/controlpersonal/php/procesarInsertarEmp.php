<?php
$codemp=$_POST['codigoEmpleado'] ?? '';
$nomemp=$_POST['nombreEmpleado'] ?? '';
$apeemp=$_POST['apellidoEmpleado'] ?? '';
$dniemp=$_POST['dniEmpleado'] ?? '';
$telemp=$_POST['telefonoEmpleado'] ?? '';
$fecnacemp=$_POST['fechaNacEmpleado'] ?? '';
$rolemp=$_POST['rolEmpleado'] ?? '';

require_once("../../../../modelo/administrador/controlpersonal/dto/empdto.php");

$EmpleadoDto=new EmpleadoDto(
    $codemp,
    $nomemp,
    $apeemp,
    $dniemp,
    $fecnacemp,
    $telemp,
    $rolemp
);

require '../../../../modelo/administrador/controlpersonal/dao/insertemp.php';
require_once ("../../../../modelo/administrador/controlpersonal/dao/insertemp.php");

$dao=new InsertarEmpDao();
$dao->insertar($EmpleadoDto,$conexion);
?>