<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result1=pg_query($conexion, "SELECT cod_categoria,nombre FROM categoria");
if(!$result1){
    echo "Error al seleccionar la categoria";
}

$cateactu=[];

while($row1=pg_fetch_assoc($result1)){
    $cateactu[]=[
        'codigo_categoria'=>trim($row1['cod_categoria']),
        'nombre_categoria'=>trim($row1['nombre'])
    ];
}

/*-----------------------------------------------------------------------*/

$result2=pg_query($conexion, "SELECT cod_proveedor,nombre FROM proveedor");
if(!$result2){
    echo "Error al seleccionar el proveedor";
}

$proveactu=[];

while($row2=pg_fetch_assoc($result2)){
    $proveactu[]=[
        'codigo_proveedor'=>trim($row2['cod_proveedor']),
        'nombre_proveedor'=>trim($row2['nombre'])
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'categorias'=>$cateactu,
    'proveedores'=>$proveactu
]);
?>