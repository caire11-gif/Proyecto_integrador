<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result = pg_query($conexion, "SELECT cod_estadousuario,nombre FROM estadousuario");
if(!$result){
    echo "Error al seleccionar el rol";
}

$estado=[];

while($row=pg_fetch_assoc($result)){
    $estado[]=$row;
}

header('Content-Type: application/json');
echo json_encode($estado);
?>