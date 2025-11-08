<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result=pg_query($conexion,"SELECT v.cod_venta AS venta_codigo,v.fecha_venta AS fecha_venta,u.usuario AS usuario_nombre,r.nombre AS rol_nombre,
                                      SUM(dv.cantidad_unidades) AS venta_cantidad,SUM(dv.total) AS venta_total FROM detalleventa dv
                                      JOIN venta v ON dv.cod_venta=v.cod_venta
                                      JOIN usuario u ON v.cod_usuario=u.cod_usuario
                                      JOIN empleado e ON u.cod_empleado=e.cod_empleado
                                      JOIN rol r ON e.cod_rol=r.cod_rol
                                      GROUP by venta_codigo,fecha_venta,usuario_nombre,rol_nombre");
if(!$result){
    echo "Error al consultar el historial de las ventas.";
}

$regven=[];

while($row=pg_fetch_assoc($result)){
    $regven[]=[
        'codigo_venta'=>trim($row['venta_codigo']),
        'fecha_venta'=>trim($row['fecha_venta']),
        'usuario_nombre'=>trim($row['usuario_nombre']),
        'cantidad_ventas'=>trim($row['venta_cantidad']),
        'total_ventas'=>trim($row['venta_total'])
    ];
};



header('Content-Type: application/json');
echo json_encode($regven);
?>