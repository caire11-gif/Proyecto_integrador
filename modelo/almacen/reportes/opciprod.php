<?php include('../../login/ingresarlogin.php') ?>

<?php
$result = pg_query($conexion, "SELECT cod_producto, nombre FROM producto");
if(!$result){
    echo "Error al seleccionar el rol";
}

$prod=[];

while($row=pg_fetch_assoc($result)){
    $prod[]=$row;
}

header('Content-Type: application/json');
echo json_encode($prod);
?>