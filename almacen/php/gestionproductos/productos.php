<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include '../conexion.php';

$tipo = $_GET['tipo'] ?? 'productos';

try {
    switch($tipo) {
        case 'categorias':
            $result = pg_query($conexion, "SELECT cod_categoria, nombre FROM categoria ORDER BY nombre");
            if(!$result) {
                throw new Exception("Error al cargar categorías: " . pg_last_error($conexion));
            }
            
            $categorias = [];
            while($row = pg_fetch_assoc($result)) {
                $categorias[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $categorias
            ]);
            break;
            
        case 'proveedores':
            $result = pg_query($conexion, "SELECT cod_proveedor, nombre FROM proveedor ORDER BY nombre");
            if(!$result) {
                throw new Exception("Error al cargar proveedores: " . pg_last_error($conexion));
            }
            
            $proveedores = [];
            while($row = pg_fetch_assoc($result)) {
                $proveedores[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $proveedores
            ]);
            break;
            
        default: // productos
            $result = pg_query($conexion, 
                "SELECT 
                    p.cod_producto,
                    p.nombre AS producto_nombre,
                    p.precio_costo,
                    p.precio_venta,
                    p.stock,
                    p.unidades_por_caja,
                    c.nombre AS categoria_nombre,
                    pro.nombre AS proveedor_nombre,
                    c.cod_categoria,
                    pro.cod_proveedor
                FROM producto p
                JOIN categoria c ON p.cod_categoria = c.cod_categoria
                JOIN proveedor pro ON p.cod_proveedor = pro.cod_proveedor
                ORDER BY p.nombre"
            );
            
            if(!$result) {
                throw new Exception("Error al cargar productos: " . pg_last_error($conexion));
            }
            
            $productos = [];
            while($row = pg_fetch_assoc($result)) {
                $productos[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $productos
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