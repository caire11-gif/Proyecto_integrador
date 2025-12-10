<?php include('../../login/ingresarlogin.php') ?>

<?php
$result=pg_query($conexion, "SELECT p.nombre as producto_nombre, c.nombre as categoria_nombre, SUM(dv.cantidad_unidades) as unidades_vendidas, 
                             SUM(dv.total) as ingresos_totales, p.stock FROM detalleventa dv
                             JOIN producto p ON dv.cod_producto = p.cod_producto
                             JOIN categoria c ON p.cod_categoria = c.cod_categoria
                             JOIN venta v ON dv.cod_venta = v.cod_venta
                             GROUP BY p.cod_producto, p.nombre, c.nombre, p.stock
                             ORDER BY unidades_vendidas DESC
                             LIMIT 10");
if(!$result){
    echo "Error al cargar los productos más vendidos";
}

$masven=[];

while($row=pg_fetch_assoc($result)){
    $masven[]=$row;
}

header('Content-Type: application/json');
echo json_encode($masven);
?>