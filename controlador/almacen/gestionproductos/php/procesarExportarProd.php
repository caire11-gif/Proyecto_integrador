<?php require_once("../../../../modelo/almacen/gestionproductos/dao/exportarprod.php") ?>
<?php
$dao = new ExportarProdDao();
$dao->exportar();
?>