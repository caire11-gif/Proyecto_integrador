<?php include('../../login/ingresarlogin.php') ?>

<?php 
// Consultar productos con stock bajo y determinar prioridad
$result = pg_query($conexion, "SELECT 
    p.nombre AS nombre_producto, 
    p.stock, 
    c.nombre AS nombre_categoria,
    pr.razon_social as proveedor_nombre,
    CASE 
        WHEN p.stock <= 0 THEN 'Crítico'
        WHEN p.stock <= 2 THEN 'Alta'
        WHEN p.stock <= 5 THEN 'Media'
        ELSE 'Baja'
    END as prioridad
    FROM producto p
    JOIN categoria c ON p.cod_categoria = c.cod_categoria
    JOIN proveedor pr ON p.cod_proveedor = pr.cod_proveedor
    WHERE p.stock <= 20
    ORDER BY 
        CASE 
            WHEN p.stock <= 0 THEN 1
            WHEN p.stock <= 2 THEN 2
            WHEN p.stock <= 5 THEN 3
            ELSE 4
        END,
        p.stock ASC");

if(!$result){
    echo "Error al seleccionar los productos con stock bajo";
    exit;
}

$prodbajo = [];
$total_alertas = 0;
$alertas_alta = 0;
$alertas_media = 0;
$alertas_baja = 0;

while($row = pg_fetch_assoc($result)){
    $prioridad = trim($row['prioridad']);
    $total_alertas++;
    
    // Contar por prioridad
    if($prioridad === 'Crítico' || $prioridad === 'Alta') {
        $alertas_alta++;
    } elseif($prioridad === 'Media') {
        $alertas_media++;
    } else {
        $alertas_baja++;
    }
    
    $prodbajo[] = [
        'nombre_producto' => trim($row['nombre_producto']),
        'stock' => (int) $row['stock'],
        'nombre_categoria' => trim($row['nombre_categoria']),
        'proveedor_nombre' => trim($row['proveedor_nombre']),
        'prioridad' => $prioridad
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'alertas' => $prodbajo,
    'estadisticas' => [
        'total' => $total_alertas,
        'alta' => $alertas_alta,
        'media' => $alertas_media,
        'baja' => $alertas_baja
    ]
]);
?>