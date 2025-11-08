<?php include('../../login/ingresarlogin.php') ?>

<?php
$usuarioencargado=$_SESSION['nombreusuarioencargado'];
$apellidoencargado=$_SESSION['apellidousuarioencargado'];

$inicialNombre = substr($usuarioencargado, 0, 1);
$inicialApellido=substr($apellidoencargado,0,1);

$iniciales=htmlspecialchars($inicialNombre.$inicialApellido);
$nomape=htmlspecialchars($usuarioencargado." ".$apellidoencargado);

header('Content-Type: application/json');
echo json_encode([
    'iniciales' => $iniciales,
    'nombre_apellido' => $nomape
]);
?>

