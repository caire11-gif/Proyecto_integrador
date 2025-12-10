<?php require_once("../../../../modelo/administrador/dashboard/dao/salidas.php") ?>
<?php
$dao = new SeleccionarSalidasDao();
$dao->seleccionar();
?>