<?php require_once("../../../../modelo/vendedor/dashboard/dao/ventas.php") ?>
<?php
$dao = new SeleccionarVentasDao();
$dao->seleccionar();
?>