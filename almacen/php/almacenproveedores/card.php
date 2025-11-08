<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result=pg_query($conexion, "SELECT p.nombre AS producto_nombre, c.nombre AS categoria_nombre, pro.nombre AS proveedor_nombre FROM producto p
                             JOIN categoria c ON p.cod_categoria=c.cod_categoria
                             JOIN proveedor pro ON p.cod_proveedor=pro.cod_proveedor");
if(!$result){
    echo "Error al seleccionar los nombres para las cards";
}

$card=[];

while($row=pg_fetch_assoc($result)){
    $card[]=[
        'producto'=>trim($row['producto_nombre']),
        'categoria'=>trim($row['categoria_nombre']),
        'proveedor'=>trim($row['proveedor_nombre'])
    ];
}

header('Content-Type: application/json');
echo json_encode($card);
?>