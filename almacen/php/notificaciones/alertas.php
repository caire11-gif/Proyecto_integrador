<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include '../conexion.php';

$tipo = $_GET['tipo'] ?? 'alertas';

try {
    switch($tipo) {
        case 'proveedores':
            $query_proveedores = "SELECT cod_proveedor, nombre FROM proveedor ORDER BY nombre";
            $result_proveedores = pg_query($conexion, $query_proveedores);
            
            if(!$result_proveedores) {
                throw new Exception("Error al cargar proveedores: " . pg_last_error($conexion));
            }
            
            $proveedores = [];
            while($proveedor = pg_fetch_assoc($result_proveedores)){
                $proveedores[] = $proveedor;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $proveedores
            ]);
            break;
            
        default: // alertas
            // Construir consulta con filtros - SOLO ALERTAS DE STOCK BAJO
            $where_conditions = array("n.cod_tiponotificacion = 'not001'");
            $query_params = array();
            
            // Filtro por estado
            if(isset($_GET['filtroEstado']) && !empty($_GET['filtroEstado'])){
                $where_conditions[] = "n.cod_estadonotificacion = $1";
                $query_params[] = $_GET['filtroEstado'];
            }
            
            // Filtro por proveedor
            if(isset($_GET['filtroProveedor']) && !empty($_GET['filtroProveedor'])){
                $where_conditions[] = "pr.cod_proveedor = $" . (count($query_params) + 1);
                $query_params[] = $_GET['filtroProveedor'];
            }

            // Construir consulta base
            $query_alertas = "
                SELECT n.*, p.nombre as producto_nombre, p.stock, p.unidades_por_caja, 
                       pr.nombre as proveedor_nombre, pr.cod_proveedor,
                       c.nombre as categoria_nombre,
                       tn.nombre as tipo_notificacion,
                       en.nombre as estado_notificacion
                FROM notificacion n
                JOIN producto p ON n.cod_producto = p.cod_producto
                JOIN proveedor pr ON p.cod_proveedor = pr.cod_proveedor
                JOIN categoria c ON p.cod_categoria = c.cod_categoria
                JOIN tiponotificacion tn ON n.cod_tiponotificacion = tn.cod_tiponotificacion
                JOIN estadonotificacion en ON n.cod_estadonotificacion = en.cod_estadonotificacion
                WHERE " . implode(" AND ", $where_conditions);
            
            $query_alertas .= " ORDER BY 
                CASE 
                    WHEN n.mensaje LIKE '🚨 ALTA PRIORIDAD%' THEN 1
                    WHEN n.mensaje LIKE '⚠️%' THEN 2
                    WHEN n.mensaje LIKE 'ℹ️%' THEN 3
                    ELSE 4
                END,
                p.stock ASC";
            
            // Ejecutar consulta con parámetros si existen
            if(!empty($query_params)){
                $result_alertas = pg_query_params($conexion, $query_alertas, $query_params);
            } else {
                $result_alertas = pg_query($conexion, $query_alertas);
            }

            if(!$result_alertas) {
                throw new Exception("Error al cargar alertas: " . pg_last_error($conexion));
            }
            
            $alertas = [];
            while($alerta = pg_fetch_assoc($result_alertas)){
                $alertas[] = $alerta;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $alertas
            ]);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>