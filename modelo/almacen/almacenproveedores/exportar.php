<?php include('../../login/ingresarlogin.php') ?>

<?php
$result=pg_query($conexion,"SELECT * FROM proveedor ORDER BY cod_proveedor");

if(!$result){
    echo "Error al seleccionar los proveedores para exportar";
}

$expprove=[];

while($row=pg_fetch_assoc($result)){
    $expprove[]=$row;
}

header('Content-Type: application/json');
echo json_encode($expprove);
?>