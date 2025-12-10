<?php require_once('../../login/ingresarlogin.php') ?>

<?php
$result=pg_query($conexion,"SELECT c.cod_compra, c.fecha_compra AS fecha, pr.razon_social AS proveedor_nombre, u.usuario AS usuario_registro,
                            SUM(dc.cantidad_unidades) AS total_productos, SUM(dc.total) AS total_compra, mp.nombre AS metodo_pago FROM compra c
                            JOIN proveedor pr ON c.cod_proveedor = pr.cod_proveedor
                            JOIN usuario u ON c.cod_usuario = u.cod_usuario
                            JOIN metodopago mp ON c.cod_metodopago = mp.cod_metodopago
                            JOIN detallecompra dc ON c.cod_compra = dc.cod_compra
                            GROUP BY c.cod_compra,pr.razon_social,u.usuario,mp.nombre
                            ORDER BY c.cod_compra");

if(!$result){
    echo "Error al seleccionar los productos para exportar";
}

$exphist=[];

while($row=pg_fetch_assoc($result)){
    $exphist[]=$row;
}

header('Content-Type: application/json');
echo json_encode($exphist);
?>