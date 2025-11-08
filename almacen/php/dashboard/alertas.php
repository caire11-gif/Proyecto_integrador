<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result=pg_query($conexion, "SELECT p.nombre AS nombre_producto, p.stock, c.nombre AS nombre_categoria FROM producto p
                             JOIN categoria c ON p.cod_categoria=c.cod_categoria
                             WHERE p.stock<=5");
if(!$result){
    echo "Error al seleccionar los productos con stock bako";
}

$prodbajo=[];

while($row=pg_fetch_assoc($result)){
    $prodbajo[]=[
        'nombre_producto'=>trim($row['nombre_producto']),
        'stock'=>(int) $row['stock'],
        'nombre_categoria'=>trim($row['nombre_categoria'])
    ];
}

header('Content-Type: application/json');
echo json_encode($prodbajo);
?>