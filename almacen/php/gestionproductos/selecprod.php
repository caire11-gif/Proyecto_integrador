<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result=pg_query($conexion, "SELECT p.cod_producto, p.nombre AS producto_nombre, p.precio_caja, p.precio_venta, p.stock, p.unidades_por_caja, c.nombre AS categoria_nombre,
                             pro.razon_social AS proveedor_nombre FROM producto p
                             JOIN categoria c ON p.cod_categoria=c.cod_categoria
                             JOIN proveedor pro ON p.cod_proveedor=pro.cod_proveedor
                             ORDER BY p.cod_producto asc");
if(!$result){
    echo "Error al seleccionar los productos";
}

$producto=[];

while($row=pg_fetch_assoc($result)){
    $producto[]=[
        'codigo_producto'=>trim($row['cod_producto']),
        'producto_nombre'=>trim($row['producto_nombre']),
        'precio_costo'=>(float) $row['precio_costo'],
        'precio_venta'=>(float) $row['precio_venta'],
        'stock'=>(int) $row['stock'],
        'unidades_caja'=>(int) $row['unidades_por_caja'],
        'categoria_nombre'=>trim($row['categoria_nombre']),
        'proveedor_nombre'=>trim($row['proveedor_nombre'])
    ];
}

header('Content-Type: application/json');
echo json_encode($producto);
?>