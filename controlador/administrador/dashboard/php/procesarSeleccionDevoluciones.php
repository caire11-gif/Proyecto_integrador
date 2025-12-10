<?php require_once("../../../../modelo/administrador/dashboard/dao/devoluciones.php") ?>
<?php
$dao = new SeleccionarDevolucionesDao();
$dao->seleccionar();
?>