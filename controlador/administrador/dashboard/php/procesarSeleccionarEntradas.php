<?php require_once("../../../../modelo/administrador/dashboard/dao/entradas.php") ?>
<?php
$dao = new SeleccionarEntradasDao();
$dao->seleccionar();
?>