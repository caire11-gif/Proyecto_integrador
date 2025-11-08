<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

include '../conexion.php';

try {
    // Obtener parámetros de filtro
    $filtro = $_GET['filtro'] ?? 'todos';
    $busqueda = $_GET['busqueda'] ?? '';

    // CONSULTA para historial - COMPRAS AGRUPADAS con filtros
    $query_historial = "SELECT 
                        c.cod_compra,
                        c.fecha_compra AS fecha,
                        pr.nombre AS proveedor_nombre,
                        u.usuario AS usuario_registro,
                        COUNT(dc.cod_detallecompra) AS total_productos,
                        SUM(dc.total) AS total_compra,
                        mp.nombre AS metodo_pago
                    FROM compra c
                    JOIN proveedor pr ON c.cod_proveedor = pr.cod_proveedor
                    JOIN usuario u ON c.cod_usuario = u.cod_usuario
                    JOIN metodopago mp ON c.cod_metodopago = mp.cod_metodopago
                    LEFT JOIN detallecompra dc ON c.cod_compra = dc.cod_compra
                    WHERE 1=1";

    // Aplicar filtros
    if ($filtro === 'hoy') {
        $query_historial .= " AND c.fecha_compra = CURRENT_DATE";
    } elseif ($filtro === 'semana') {
        $query_historial .= " AND c.fecha_compra >= DATE_TRUNC('week', CURRENT_DATE)";
    } elseif ($filtro === 'mes') {
        $query_historial .= " AND c.fecha_compra >= DATE_TRUNC('month', CURRENT_DATE)";
    }

    // Aplicar búsqueda
    if (!empty($busqueda)) {
        $busqueda_like = pg_escape_string($busqueda);
        $query_historial .= " AND (c.cod_compra ILIKE '%$busqueda_like%' 
                                 OR pr.nombre ILIKE '%$busqueda_like%'
                                 OR u.usuario ILIKE '%$busqueda_like%')";
    }

    $query_historial .= " GROUP BY c.cod_compra, c.fecha_compra, pr.nombre, u.usuario, mp.nombre
                         ORDER BY c.fecha_compra DESC, c.cod_compra DESC";

    $result = pg_query($conexion, $query_historial);
    
    if(!$result){
        throw new Exception("Error al cargar historial: " . pg_last_error($conexion));
    }

    $compras = [];
    while($row = pg_fetch_assoc($result)) {
        $compras[] = $row;
    }

    echo json_encode([
        'success' => true,
        'compras' => $compras,
        'total' => count($compras),
        'filtro' => $filtro,
        'busqueda' => $busqueda
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

pg_close($conexion);
?>