<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result=pg_query($conexion, "SELECT m.fecha_movimiento,p.nombre,m.cod_tipomovimiento, dv.cod_venta, dc.cod_compra FROM producto p
                             JOIN detalleventa dv ON p.cod_producto=dv.cod_producto
                             JOIN detallecompra dc ON p.cod_producto=dc.cod_producto
                             JOIN movimiento m ON p.cod_producto=m.cod_producto
                             ORDER BY m.fecha_movimiento desc");
if(!$result){
    echo "Error al seleccionar los movimientos salientes";
}

$movirecien=[];

while($row=pg_fetch_assoc($result)){
    $movirecien[]=[
        'codigo_venta'=>trim($row['cod_venta']),
        'codigo_compra'=>trim($row['cod_compra']),
        'nombre_producto'=>trim($row['nombre']),
        'codigo_tipomovimiento'=>trim($row['cod_tipomovimiento']),
        'fecha_movimiento'=>trim($row['fecha_movimiento'])
    ];
}

header('Content-Type: application/json');
echo json_encode($movirecien);
?>