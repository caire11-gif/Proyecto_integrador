<?php
$codestnoti = $_GET['codestnoti'];
$codnoti=$_GET['codnoti'];

require '../../../../modelo/almacen/notificaciones/dao/actuestdao.php';
require_once("../../../../modelo/almacen/notificaciones/dao/actuestdao.php");

$dao = new ActualizarEstadoDao();
$dao->actualizar($codestnoti,$codnoti, $conexion);
?>