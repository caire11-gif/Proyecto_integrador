<?php require_once("../../../../modelo/vendedor/consultarstock/dao/exportarprod.php") ?>
<?php
$dao = new ExportarProdDao();
$dao->exportar();
?>