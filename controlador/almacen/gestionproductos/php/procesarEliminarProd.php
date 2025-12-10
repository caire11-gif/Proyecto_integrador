<?php
$cod_producto = $_GET['cod_producto'];
echo $cod_producto;
require '../../../../modelo/almacen/gestionproductos/dao/eliminarprod.php';
require_once("../../../../modelo/almacen/gestionproductos/dao/eliminarprod.php");

$dao = new EliminarEmpDao();
$dao->eliminar($cod_producto, $conexion);
?>