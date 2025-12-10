<?php require_once("../../../../modelo/almacen/gestionproductos/dao/selecprod.php") ?>
<?php
$dao = new SeleccionarProdDao();
$dao->seleccionar();
?>