<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result1=pg_query($conexion,"SELECT SUM(dv.total) AS total FROM detalleventa dv JOIN venta v ON dv.cod_venta=v.cod_venta
                      WHERE DATE_TRUNC('month', v.fecha_venta) = DATE_TRUNC('month', CURRENT_DATE)");
if(!$result1){
    echo "Error al sumar todas las ventas.";
}

$row1=pg_fetch_assoc($result1);

$sel1=(int) $row1['total'];

header('Content-Type: application/json');
echo json_encode([
    'total'=>$sel1
]);
?>