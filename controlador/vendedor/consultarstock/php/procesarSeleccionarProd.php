<?php require_once("../../../../modelo/vendedor/consultarstock/dao/selecprod.php") ?>
<?php
$dao = new SeleccionarProductoDao();
$dao->seleccionar();
?>