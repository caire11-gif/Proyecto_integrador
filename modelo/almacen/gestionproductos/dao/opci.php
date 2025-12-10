<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
$conexion=Conexion::getConexion();

$result1=pg_query($conexion,"SELECT c.cod_categoria AS categoria_codigo, c.nombre AS categoria_nombre, pro.cod_proveedor AS proveedor_codigo, 
                             pro.razon_social AS proveedor_nombre FROM categoria c
                             JOIN producto p ON c.cod_categoria=p.cod_categoria
                             JOIN proveedor pro ON p.cod_proveedor=pro.cod_proveedor");
if(!$result1){
    echo "Error al seleccionar la categoria";
}

$cate=[];

while($row1=pg_fetch_assoc($result1)){
    $cate[]=$row1;
}

/*-------------------------------------------------------------------------------------------------*/

header('Content-Type: application/json');
echo json_encode($cate);
?>