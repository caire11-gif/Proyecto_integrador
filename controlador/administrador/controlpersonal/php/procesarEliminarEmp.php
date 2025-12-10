<?php
$codemp = $_GET['codemp'];

require '../../../../modelo/administrador/controlpersonal/dao/eliminaremp.php';
require_once("../../../../modelo/administrador/controlpersonal/dao/eliminaremp.php");

$dao = new EliminarEmpDao();
$dao->eliminar($codemp, $conexion);
?>