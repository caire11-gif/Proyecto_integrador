<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result = pg_query($conexion, "SELECT e.cod_empleado,e.nombre,e.apellido FROM empleado e JOIN rol r ON e.cod_rol=r.cod_rol WHERE r.cod_rol='rol3'");
if(!$result){
    echo "Error al seleccionar al vendedor";
}

$empven=[];

while($row=pg_fetch_assoc($result)){
    $empven[]=$row;
}

header('Content-Type: application/json');
echo json_encode($empven);
?>