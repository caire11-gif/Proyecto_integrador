<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result=pg_query($conexion,"SELECT COUNT(cod_venta) AS cantidad_ventas FROM venta");
if(!$result){
    echo "Error al contar las ventas.";
}

$row=pg_fetch_assoc($result);

$sel=(int) $row['cantidad_ventas'];

$cantven=0;

if($sel===0){
    $cantven=0;
} else {
    $cantven=$sel;
}

header('Content-Type: application/json');
echo json_encode([
    'cantidad_ventas'=>$cantven,
]);
?>