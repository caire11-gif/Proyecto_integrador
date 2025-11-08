<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result1 = pg_query($conexion, "SELECT COUNT(cod_empleado) AS cantidad_empleado FROM empleado");
if(!$result1){
    echo "Error al contar los empleados";
    exit;
}

$row1=pg_fetch_assoc($result1);

$sel1=(int) $row1['cantidad_empleado'];

$result2 = pg_query($conexion, "SELECT COUNT(cod_usuario) AS cantidad_usuario FROM usuario");
if(!$result2){
    echo "Error al contar los usuarios";
    exit;
}

$cantemp=0;

if($sel1===0){
    $cantemp=0;
} else {
    $cantemp=$sel1;
}

$row2=pg_fetch_assoc($result2);

$sel2=(int) $row2['cantidad_usuario'];

$cantusu=0;

if($sel2===0){
    $cantusu=0;
} else {
    $cantusu=$sel2;
}

header('Content-Type: application/json');
echo json_encode([
    'cantidad_empleado'=>$cantemp,
    'cantidad_usuario'=>$cantusu
]);
?>