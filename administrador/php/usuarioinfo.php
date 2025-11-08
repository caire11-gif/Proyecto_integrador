<?php include('../../login/ingresarlogin.php') ?>

<?php
$usuarioadmin=$_SESSION['nombreusuarioadmin'];
$apellidoadmin=$_SESSION['apellidousuarioadmin'];

$inicialNombre = substr($usuarioadmin, 0, 1);
$inicialApellido=substr($apellidoadmin,0,1);

$iniciales=htmlspecialchars($inicialNombre.$inicialApellido);
$nomape=htmlspecialchars($usuarioadmin." ".$apellidoadmin);

header('Content-Type: application/json');
echo json_encode([
    'iniciales' => $iniciales,
    'nombre_apellido' => $nomape
]);
?>

