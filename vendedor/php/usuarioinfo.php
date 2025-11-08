<?php include('../../login/ingresarlogin.php') ?>

<?php
$usuariovendedor=$_SESSION['nombreusuariovendedor'];
$apellidovendedor=$_SESSION['apellidousuariovendedor'];

$inicialNombre = substr($usuariovendedor, 0, 1);
$inicialApellido=substr($apellidovendedor,0,1);

$iniciales=htmlspecialchars($inicialNombre.$inicialApellido);
$nomape=htmlspecialchars($usuariovendedor." ".$apellidovendedor);

header('Content-Type: application/json');
echo json_encode([
    'iniciales' => $iniciales,
    'nombre_apellido' => $nomape
]);
?>

