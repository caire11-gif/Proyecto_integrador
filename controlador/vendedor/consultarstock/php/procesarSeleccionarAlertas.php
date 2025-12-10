<?php require_once("../../../../modelo/vendedor/consultarstock/dao/alertas.php") ?>
<?php
$dao = new SeleccionarAlertasDao();
$dao->seleccionar();
?>