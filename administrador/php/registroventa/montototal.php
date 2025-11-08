<?php include('../../../login/ingresarlogin.php') ?>

<?php
$sum_check=pg_query($conexion,"SELECT 1 FROM detalleventa LIMIT 1");
if(!$sum_check){
    echo "Error en verificar la cantidad de filas.";
}

if(pg_num_rows($sum_check)==0){
    $sumven=0;
} else {
    $sumaven=pg_query($conexion,"SELECT SUM(total) AS suma_ventas FROM detalleventa");
    if(!$sumaven){
        echo "Error al sumar las ventas";
    }

    $sumven=pg_fetch_assoc($sumaven);
    if(!$sumven){
        echo "Error en la suma";
    }

    $sumven=(float)$sumven['suma_ventas'];
}

$sumven=number_format($sumven,2);

header('Content-Type: application/json');
echo json_encode([
    'suma_ventas'=>$sumven,
]);
?>