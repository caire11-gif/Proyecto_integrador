<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include '../conexion.php';

try {
    // Obtener parámetros de filtros
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

    // Consulta para productos más vendidos con filtro de fecha
    $query_ventas = "
        SELECT 
            p.nombre as producto_nombre,
            c.nombre as categoria_nombre,
            SUM(dv.cantidad_unidades) as unidades_vendidas,
            SUM(dv.total) as ingresos_totales,
            p.stock
        FROM detalleventa dv
        JOIN producto p ON dv.cod_producto = p.cod_producto
        JOIN categoria c ON p.cod_categoria = c.cod_categoria
        JOIN venta v ON dv.cod_venta = v.cod_venta
        WHERE v.fecha_venta BETWEEN $1 AND $2
        GROUP BY p.cod_producto, p.nombre, c.nombre, p.stock
        ORDER BY unidades_vendidas DESC
        LIMIT 10
    ";
    
    $result_ventas = pg_query_params($conexion, $query_ventas, array($fecha_inicio, $fecha_fin));

    if(!$result_ventas) {
        throw new Exception("Error al cargar productos vendidos: " . pg_last_error($conexion));
    }
    
    $productos_vendidos = [];
    while($venta = pg_fetch_assoc($result_ventas)){
        $productos_vendidos[] = $venta;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $productos_vendidos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>