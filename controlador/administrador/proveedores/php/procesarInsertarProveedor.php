<?php
$rucprove=$_POST['rucProveedor'] ?? '';
$nomprove=$_POST['razonSocialProveedor'] ?? '';
$telprove=$_POST['telefonoProveedor'] ?? '';
$dirprove=$_POST['direccionProveedor'] ?? '';

require_once("../../../../modelo/administrador/proveedores/dto/provedto.php");

$proveedorDto=new ProveedorDto(
    $nomprove,
    $rucprove,
    $telprove,
    $dirprove
);

require '../../../../modelo/administrador/proveedores/dao/insertprove.php';
require_once("../../../../modelo/administrador/proveedores/dao/insertprove.php");

$dao=new InsertarProveDao();
$dao->insertar($proveedorDto,$conexion);
?>