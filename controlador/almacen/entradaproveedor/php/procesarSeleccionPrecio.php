<?php require_once("../../../../modelo/almacen/entradaproveedor/dao/preprod.php") ?>
<?php
$dao = new SeleccionarPrecioProductoDao();
$dao->seleccionar();
?>