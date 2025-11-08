<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result1=pg_query($conexion, "SELECT cod_proveedor,nombre AS proveedor_nombre FROM proveedor");
if(!$result1){
    echo "Error al seleccionar el proveedor";
}

$proveedor=[];

while($row1=pg_fetch_assoc($result1)){
    $proveedor[]=[
        'codigo_proveedor'=>trim($row1['cod_proveedor']),
        'nombre_proveedor'=>trim($row1['proveedor_nombre'])
    ];
}

/*-----------------------------------------------------*/

$productos=[];

foreach($proveedor as $prov){
    $codprove = $prov['codigo_proveedor'];
    $result2 = pg_query_params($conexion, "SELECT cod_producto, nombre, cod_proveedor FROM producto WHERE cod_proveedor = $1",array($codprove));

    if(!$result2){
        echo "Error al seleccionar los productos por proveedor";
    }

    while($row2=pg_fetch_assoc($result2)){
        $productos[]=[
            'codigo_producto'=>trim($row2['cod_producto']),
            'nombre_producto'=>trim($row2['nombre']),
            'codigo_proveedor'=>trim($row2['cod_proveedor'])
        ];
    }
}

header('Content-Type: application/json');
echo json_encode([
    'proveedores'=>$proveedor,
    'productos'=>$productos
]);
?>