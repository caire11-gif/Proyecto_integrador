<?php include('../../../login/ingresarlogin.php') ?>

<?php
$estadisticas = [
    'ventas_hoy' => 0,
    'total_vendido' => 0,
    'productos_vendidos' => 0
];
    
// Ventas de hoy - CORREGIDO: calcular total desde detalleventa
$queryVentasHoy = "SELECT COUNT(DISTINCT v.cod_venta) as total_ventas, 
                            COALESCE(SUM(dv.total), 0) as total_vendido 
                    FROM venta v
                    LEFT JOIN detalleventa dv ON v.cod_venta = dv.cod_venta
                    WHERE DATE(v.fecha_venta) = CURRENT_DATE";
$resultVentasHoy = pg_query($conexion, $queryVentasHoy);
if($resultVentasHoy && pg_num_rows($resultVentasHoy) > 0) {
    $row = pg_fetch_assoc($resultVentasHoy);
    $estadisticas['ventas_hoy'] = $row['total_ventas'];
    $estadisticas['total_vendido'] = $row['total_vendido'];
    $estadisticas['total_vendido']=number_format($estadisticas['total_vendido'], 2);
}
    
// Productos vendidos hoy
$queryProductosVendidos = "SELECT COALESCE(SUM(dv.cantidad_unidades), 0) as total_productos
                            FROM detalleventa dv
                            JOIN venta v ON dv.cod_venta = v.cod_venta
                            WHERE DATE(v.fecha_venta) = CURRENT_DATE";
$resultProductos = pg_query($conexion, $queryProductosVendidos);
if($resultProductos && pg_num_rows($resultProductos) > 0) {
    $row = pg_fetch_assoc($resultProductos);
    $estadisticas['productos_vendidos'] = $row['total_productos'];
}

header('Content-Type: application/json');
echo json_encode($estadisticas);
?>