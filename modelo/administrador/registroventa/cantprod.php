<?php include('../../login/ingresarlogin.php') ?>

<?php
$result=pg_query($conexion,"SELECT SUM(cantidad_unidades) AS cantidad_productos FROM detalleventa");
if(!$result){
    echo "Error al contar las ventas.";
}

$row=pg_fetch_assoc($result);

$sel=(int) $row['cantidad_productos'];

$cantprod=0;

if($sel===0){
    $cantprod=0;
} else { 
    $cantprod=$sel;
}

header('Content-Type: application/json');
echo json_encode([
    'cantidad_productos'=>$cantprod,
]);
?>