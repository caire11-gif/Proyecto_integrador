<?php include('../../login/ingresarlogin.php') ?>

<?php
$result = pg_query($conexion, "SELECT cod_tipomovimiento, nombre FROM tipomovimiento");
if(!$result){
    echo "Error al seleccionar el rol";
}

$tipomovi=[];

while($row=pg_fetch_assoc($result)){
    $tipomovi[]=$row;
}

header('Content-Type: application/json');
echo json_encode($tipomovi);
?>