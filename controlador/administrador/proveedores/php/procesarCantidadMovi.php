<?php require_once("../../../../modelo/administrador/proveedores/dao/cantmovi.php") ?>
<?php
$dao = new SeleccionarCantidadMoviDao();
$dao->seleccionar();
?>