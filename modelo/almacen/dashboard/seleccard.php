<?php include('../../login/ingresarlogin.php') ?>

<?php
$result1 = pg_query($conexion, "SELECT COUNT(cod_producto) AS cantidad_producto FROM producto");
if(!$result1){
    echo "Error al contar los productos.";
}

$row1=pg_fetch_assoc($result1);

$sel1=(int) $row1['cantidad_producto'];

$cantprod=0;

if($sel1===0){
    $cantprod=0;
} else {
    $cantprod=$sel1;
}

/*---------------------------------------------------------------------------------------------*/

$result2=pg_query($conexion, "SELECT COUNT(cod_categoria) AS cantidad_categoria FROM categoria");
if(!$result2){
    echo "Error al contar las categorías";
}

$row2=pg_fetch_assoc($result2);

$sel2=(int) $row2['cantidad_categoria'];

$cantcate=0;

if($sel2===0){
    $cantcate=0;
} else {
    $cantcate=$sel2;
}

/*--------------------------------------------------------------------------------------------*/

$result3=pg_query($conexion, "SELECT COUNT(m.cod_movimiento) AS cantidad_movimiento FROM movimiento m 
                              JOIN tipomovimiento tm ON m.cod_tipomovimiento=tm.cod_tipomovimiento
                              WHERE m.cod_tipomovimiento='TM001'");
if(!$result3){
    echo "Error al contar los movimientos";
}

$row3=pg_fetch_assoc($result3);

$sel3=(int) $row3['cantidad_movimiento'];

$cantmovi=0;

if($sel3===0){
    $cantmovi=0;
} else {
    $cantmovi=$sel3;
}

/*--------------------------------------------------------------------------------------------*/

$result4=pg_query($conexion, "SELECT COUNT(cod_producto) AS cantidad_producto_bajo FROM producto WHERE stock<=5");
if(!$result4){
    echo "Error al contar los productos con stock bajo";
}

$row4=pg_fetch_assoc($result4);

$sel4=(int) $row4['cantidad_producto_bajo'];

if($sel4===0){
    $cantprodbajo=0;
} else {
    $cantprodbajo=$sel4;
}

header('Content-Type: application/json');
echo json_encode([
    'cantidad_producto'=>$cantprod,
    'cantidad_categoria'=>$cantcate,
    'cantidad_movimiento'=>$cantmovi,
    'cantidad_producto_bajo'=>$cantprodbajo
]);
?>