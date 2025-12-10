<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result=pg_query($conexion, "SELECT cod_producto, nombre, precio_venta, stock FROM producto WHERE stock > 0 ORDER BY nombre");
if(!$result){
    echo "Error al seleccionar los productos";
}

$productos=[];

while($row=pg_fetch_assoc($result)){
    $productos[]=[
        'codigo_producto'=>trim($row['cod_producto']),
        'nombre_producto'=>trim($row['nombre']),
        'precio_venta'=>(float) $row['precio_venta'],
        'stock'=>(int) $row['stock']
    ];
}

header('Content-Type: application/json');
echo json_encode($productos);
?>