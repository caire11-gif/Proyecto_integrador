<?php require_once("../../../../modelo/almacen/notificaciones/dao/opciestado.php") ?>
<?php
$dao = new SeleccionarEstadoDao();
$dao->seleccionar();
?>