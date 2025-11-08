<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include '../conexion.php';

try {
    $cod_usuario = 'user001';
    $alertasGeneradas = 0;
    
    // Verificar productos con stock bajo que no tengan alertas pendientes
    $query_stock_bajo = "
        SELECT p.cod_producto, p.nombre, p.stock, p.unidades_por_caja, pr.nombre as proveedor_nombre
        FROM producto p
        JOIN proveedor pr ON p.cod_proveedor = pr.cod_proveedor
        WHERE p.stock < 20 
        AND p.cod_producto NOT IN (
            SELECT cod_producto FROM notificacion 
            WHERE cod_estadonotificacion IN ('en001', 'en002') 
            AND cod_tiponotificacion = 'not001'
        )
    ";
    
    $result = pg_query($conexion, $query_stock_bajo);
    if(!$result) {
        throw new Exception("Error al verificar stock bajo: " . pg_last_error($conexion));
    }
    
    while($producto = pg_fetch_assoc($result)){
        $cod_notificacion = 'N' . substr(uniqid(), -8);
        
        // Determinar prioridad según el stock
        if($producto['stock'] < 5) {
            $prioridad = 'Alta';
            $mensaje = "🚨 ALTA PRIORIDAD - Stock crítico para {$producto['nombre']}. Actual: {$producto['stock']} unidades. ¡Reposición urgente requerida!";
        } elseif($producto['stock'] < 10) {
            $prioridad = 'Media';
            $mensaje = "⚠️ Stock bajo para {$producto['nombre']}. Actual: {$producto['stock']} unidades. Se recomienda reposición.";
        } else {
            $prioridad = 'Baja';
            $mensaje = "ℹ️ Stock moderado para {$producto['nombre']}. Actual: {$producto['stock']} unidades. Monitorear.";
        }
        
        $query_insert = "INSERT INTO notificacion (cod_notificacion, cod_usuario, cod_producto, cod_tiponotificacion, cod_estadonotificacion, mensaje) 
                        VALUES ($1, $2, $3, 'not001', 'en001', $4)";
        $insert_result = pg_query_params($conexion, $query_insert, array($cod_notificacion, $cod_usuario, $producto['cod_producto'], $mensaje));
        
        if($insert_result) {
            $alertasGeneradas++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Se generaron {$alertasGeneradas} nuevas alertas automáticas",
        'alertas_generadas' => $alertasGeneradas
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>