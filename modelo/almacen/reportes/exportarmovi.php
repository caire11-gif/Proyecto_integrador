<?php include('../../login/ingresarlogin.php') ?>

<?php
$result=pg_query($conexion,"SELECT m.cod_movimiento, m.fecha_movimiento, p.nombre as producto_nombre, tm.nombre as tipo_movimiento, m.cod_tipomovimiento, 
                            m.observacion, u.usuario, p.stock FROM movimiento m
                            JOIN producto p ON m.cod_producto = p.cod_producto
                            JOIN tipomovimiento tm ON m.cod_tipomovimiento = tm.cod_tipomovimiento
                            JOIN usuario u ON m.cod_usuario = u.cod_usuario");

if(!$result){
    echo "Error al seleccionar los productos para exportar";
}

$expmovi=[];

while($row=pg_fetch_assoc($result)){
    $expmovi[]=$row;
}

header('Content-Type: application/json');
echo json_encode($expmovi);
?>