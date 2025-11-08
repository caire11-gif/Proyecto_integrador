<?php include('../../../login/ingresarlogin.php') ?>

<?php
$query = "SELECT v.cod_venta as id, COUNT(v.cod_venta) AS cantidad_ventas, v.fecha_venta as fecha, SUM(dv.total) as total, v.cod_metodopago as metodo_pago, 
          COUNT(dv.cod_detalleventa) as cantidad_productos, STRING_AGG(p.nombre, ', ') as productos_nombres FROM venta v
          LEFT JOIN detalleventa dv ON v.cod_venta = dv.cod_venta
          LEFT JOIN producto p ON dv.cod_producto = p.cod_producto
          GROUP BY v.cod_venta, v.fecha_venta, v.cod_metodopago
          ORDER BY v.fecha_venta DESC
          LIMIT 5";
    
$result = pg_query($conexion, $query);
$ventas = [];
    
if($result && pg_num_rows($result) > 0) {
    while($row = pg_fetch_assoc($result)) {
        $row['total']==number_format($row['total'], 2);
        $ventas[] = [
            'id' => $row['id'],
            'cantidad_ventas'=>$row['cantidad_ventas'],
            'fecha' => $row['fecha'],
            'total' => $row['total'] ?: 0,
            'metodo_pago' => $row['metodo_pago'],
            'cantidad_productos' => $row['cantidad_productos'],
            'productos' => $row['productos_nombres'] ?: 'Sin productos'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($ventas);
?>