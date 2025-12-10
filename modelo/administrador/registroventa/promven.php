<?php include('../../login/ingresarlogin.php') ?>

<?php
$prom_check = pg_query($conexion, "SELECT 1 FROM venta LIMIT 1");
if(!$prom_check){
    echo "Error en verificar la cantidad de filas: ".pg_last_error($conexion);
}

if(pg_num_rows($prom_check)==0){
    $promsumacanti=0;
} else {
    $prom=pg_query($conexion,"SELECT (SUM(total)/COUNT(cod_venta)) AS promedio_ventas FROM detalleventa");
    if(!$prom){
        echo "Error al promediar las ventas.";
    }

    $promsumacanti=pg_fetch_assoc($prom);
    if(!$promsumacanti){
        echo "Error con el promedio";
    }

    $promsumacanti=(float)$promsumacanti['promedio_ventas'];
}

$promsumacanti=number_format($promsumacanti,2);

header('Content-Type: application/json');
echo json_encode([
    'promedio_ventas'=>$promsumacanti,
]);
?>