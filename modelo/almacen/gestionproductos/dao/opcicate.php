<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
$conexion=Conexion::getConexion();

$result1=pg_query($conexion,"SELECT cod_categoria AS categoria_codigo, nombre AS categoria_nombre FROM categoria");
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