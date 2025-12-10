<?php
$codusu=$_POST['codigoActualizarUsuario'] ?? '';
$estado=$_POST['cambiarEstadoUsuario'] ?? '';

require_once("../../../../modelo/administrador/controlpersonal/dto/estadodto.php");

$estadoDto=new EstadoDto(
    $codusu,
    $estado
);

require '../../../../modelo/administrador/controlpersonal/dao/editarusu.php';
require_once ("../../../../modelo/administrador/controlpersonal/dao/editarusu.php");

$dao=new ActualizarEstadoDao();
$dao->actualizar($estadoDto,$conexion);
?>