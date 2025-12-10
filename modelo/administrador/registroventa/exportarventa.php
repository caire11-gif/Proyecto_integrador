<?php include('../../login/ingresarlogin.php') ?>

<?php
$result=pg_query($conexion,"SELECT v.cod_venta, v.fecha_venta, SUM(dv.cantidad_unidades) AS cantidad,SUM(dv.total) AS total,u.usuario FROM venta v
                            JOIN detalleventa dv ON v.cod_venta=dv.cod_venta
                            JOIN usuario u ON v.cod_usuario=u.cod_usuario
                            GROUP by v.cod_venta,u.usuario
                            ORDER BY cod_venta");

if(!$result){
    echo "Error al seleccionar los proveedores para exportar";
}

$expven=[];

while($row=pg_fetch_assoc($result)){
    $expven[]=$row;
}

header('Content-Type: application/json');
echo json_encode($expven);
?>