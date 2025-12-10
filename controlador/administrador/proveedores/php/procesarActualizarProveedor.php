<?php
$codactprove=$_POST['codigoActualizarProveedor'] ?? '';
$nomactprove=$_POST['nombreActualizarProveedor'] ?? '';
$rucactprove=$_POST['rucActualizarProveedor'] ?? '';
$telactprove=$_POST['telefonoActualizarProveedor'] ?? '';
$diractprove=$_POST['direccionActualizarProveedor'] ?? '';

require_once("../../../../modelo/administrador/proveedores/dto/proveactudto.php");

$proveedorActualizarDto=new ProveedorActualizarDto(
    $codactprove,
    $nomactprove,
    $rucactprove,
    $telactprove,
    $diractprove
);

require '../../../../modelo/administrador/proveedores/dao/editarprove.php';
require_once ("../../../../modelo/administrador/proveedores/dao/editarprove.php");

$dao=new ActualizarProveDao();
$dao->actualizar($proveedorActualizarDto,$conexion);
?>