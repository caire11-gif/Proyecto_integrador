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
    $producto_filtro = $_GET['producto'] ?? '';
    $movimiento_filtro = $_GET['movimiento'] ?? '';

    // Construir consulta para movimientos con filtros
    $where_conditions = array();
    $query_params = array();

    // Filtro por fecha
    if(!empty($fecha_inicio) && !empty($fecha_fin)){
        $where_conditions[] = "m.fecha_movimiento BETWEEN $1 AND $2";
        $query_params[] = $fecha_inicio;
        $query_params[] = $fecha_fin;
    }

    // Filtro por producto
    if(!empty($producto_filtro)){
        $where_conditions[] = "m.cod_producto = $" . (count($query_params) + 1);
        $query_params[] = $producto_filtro;
    }

    // Filtro por tipo de movimiento
    if(!empty($movimiento_filtro)){
        if($movimiento_filtro == 'entrada') {
            $where_conditions[] = "m.cod_tipomovimiento = 'mov001'";
        } elseif($movimiento_filtro == 'salida') {
            $where_conditions[] = "m.cod_tipomovimiento = 'mov002'";
        } elseif($movimiento_filtro == 'ajuste') {
            $where_conditions[] = "m.cod_tipomovimiento = 'mov003'";
        }
    }

    // Consulta base para movimientos
    $query_movimientos = "
        SELECT 
            m.cod_movimiento,
            m.fecha_movimiento,
            p.nombre as producto_nombre,
            tm.nombre as tipo_movimiento,
            m.cod_tipomovimiento,
            m.observacion,
            u.usuario,
            p.stock
        FROM movimiento m
        JOIN producto p ON m.cod_producto = p.cod_producto
        JOIN tipomovimiento tm ON m.cod_tipomovimiento = tm.cod_tipomovimiento
        JOIN usuario u ON m.cod_usuario = u.cod_usuario
    ";

    // Agregar condiciones WHERE si existen
    if(!empty($where_conditions)){
        $query_movimientos .= " WHERE " . implode(" AND ", $where_conditions);
    }

    $query_movimientos .= " ORDER BY m.fecha_movimiento DESC LIMIT 100";

    // Ejecutar consulta con parámetros si existen
    if(!empty($query_params)){
        $result_movimientos = pg_query_params($conexion, $query_movimientos, $query_params);
    } else {
        $result_movimientos = pg_query($conexion, $query_movimientos);
    }

    if(!$result_movimientos) {
        throw new Exception("Error al cargar movimientos: " . pg_last_error($conexion));
    }
    
    $movimientos = [];
    while($movimiento = pg_fetch_assoc($result_movimientos)){
        $movimientos[] = $movimiento;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $movimientos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>