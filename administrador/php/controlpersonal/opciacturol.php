<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result = pg_query($conexion, "SELECT cod_rol,nombre FROM rol");
if(!$result){
    echo "Error al seleccionar el rol";
}

$rol=[];

while($row=pg_fetch_assoc($result)){
    $rol[]=$row;
}

header('Content-Type: application/json');
echo json_encode($rol);
?>