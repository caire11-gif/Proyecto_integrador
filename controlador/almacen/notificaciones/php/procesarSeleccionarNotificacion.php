<?php require_once("../../../../modelo/almacen/notificaciones/dao/selecnoti.php") ?>
<?php
$dao = new SeleccionarNotificacionDao();
$dao->seleccionar();
?>