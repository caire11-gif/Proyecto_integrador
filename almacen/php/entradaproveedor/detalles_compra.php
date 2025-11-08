<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

include '../conexion.php';

try {
    $cod_compra = $_GET['cod_compra'] ?? '';
    
    if (empty($cod_compra)) {
        throw new Exception("Código de compra no especificado");
    }

    // Consulta para obtener detalles de la compra
    $query_detalles = "SELECT 
                        dc.cod_detallecompra,
                        p.nombre AS producto_nombre,
                        dc.cantidad_cajas,
                        p.unidades_por_caja,
                        (dc.cantidad_cajas * p.unidades_por_caja) AS total_unidades,
                        dc.precio_unitario,
                        dc.total,
                        pr.nombre AS proveedor_nombre,
                        c.fecha_compra,
                        u.usuario AS registrado_por
                    FROM detallecompra dc
                    JOIN producto p ON dc.cod_producto = p.cod_producto
                    JOIN compra c ON dc.cod_compra = c.cod_compra
                    JOIN proveedor pr ON c.cod_proveedor = pr.cod_proveedor
                    JOIN usuario u ON c.cod_usuario = u.cod_usuario
                    WHERE dc.cod_compra = '$cod_compra'";

    $result = pg_query($conexion, $query_detalles);
    
    if(!$result){
        throw new Exception("Error al cargar detalles: " . pg_last_error($conexion));
    }

    $detalles = [];
    $total_general = 0;
    
    while($row = pg_fetch_assoc($result)) {
        $detalles[] = $row;
        $total_general += $row['total'];
    }

    if (empty($detalles)) {
        throw new Exception("No se encontraron detalles para esta compra");
    }

    // Información general de la compra (primer registro)
    $compra_info = $detalles[0];

    echo json_encode([
        'success' => true,
        'compra' => [
            'cod_compra' => $cod_compra,
            'proveedor' => $compra_info['proveedor_nombre'],
            'fecha' => $compra_info['fecha_compra'],
            'registrado_por' => $compra_info['registrado_por'],
            'total_general' => $total_general
        ],
        'detalles' => $detalles
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

pg_close($conexion);
?>