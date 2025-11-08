<?php include('../../../login/ingresarlogin.php') ?>


<?php
$result=pg_query($conexion, "SELECT cod_proveedor,nombre,telefono,direccion FROM proveedor ORDER BY cod_proveedor");

if(!$result){
    echo "Error al insertar proveedor.";
    exit;
}

$proveedores=[];

while($row=pg_fetch_assoc($result)){
    $proveedores[]=[
        'codprove'=>trim($row['cod_proveedor']),
        'nombre'=>trim($row['nombre']),
        'telefono'=>trim($row['telefono']),
        'direccion'=>trim($row['direccion']),
    ];
}

header('Content-Type: application/json');
echo json_encode($proveedores);
?>