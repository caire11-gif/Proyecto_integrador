<?php
$codusu=$_POST['codigoUsuario'] ?? '';
$contraactual=$_POST['contraseñaActual'] ?? '';
$cambiarcontra=$_POST['nuevaContraseña'] ?? '';
$confirmarcontra=$_POST['confirmarContraseña'] ?? '';

require_once("../../../../modelo/administrador/controlpersonal/dto/contradto.php");

$ContraseñaDto=new ContraseñaDto(
    $codusu,
    $contraactual,
    $cambiarcontra,
    $confirmarcontra
);

require '../../../../modelo/administrador/controlpersonal/dao/cambiarcontra.php';
require_once ("../../../../modelo/administrador/controlpersonal/dao/cambiarcontra.php");

$dao=new CambiarContraseñaDao();
$dao->actualizar($ContraseñaDto,$conexion);
?>