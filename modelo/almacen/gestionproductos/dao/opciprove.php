<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
$conexion=Conexion::getConexion();

$result1=pg_query($conexion,"SELECT cod_proveedor AS proveedor_codigo, razon_social AS proveedor_nombre FROM proveedor");
if(!$result1){
    echo "Error al seleccionar la categoria";
}

$prove=[];

while($row1=pg_fetch_assoc($result1)){
    $prove[]=$row1;
}

/*-------------------------------------------------------------------------------------------------*/

header('Content-Type: application/json');
echo json_encode($prove);
?>