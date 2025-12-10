<?php include('../../login/ingresarlogin.php') ?>

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $query_productos = "SELECT cod_producto, nombre FROM producto ORDER BY nombre";
    $result_productos = pg_query($conexion, $query_productos);

    if(!$result_productos) {
        throw new Exception("Error al cargar productos: " . pg_last_error($conexion));
    }
    
    $productos = [];
    while($producto = pg_fetch_assoc($result_productos)){
        $productos[] = $producto;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $productos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>