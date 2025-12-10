<?php include('../../login/ingresarlogin.php') ?>

<?php 
// Consultar los 6 movimientos más recientes ordenados correctamente
$result = pg_query($conexion, "SELECT 
    m.fecha_movimiento,
    m.observacion,
    p.nombre as producto_nombre,
    m.cod_movimiento
    FROM movimiento m
    JOIN producto p ON m.cod_producto = p.cod_producto
    ORDER BY m.fecha_movimiento DESC, 
             CAST(SUBSTRING(m.cod_movimiento FROM 4) AS INTEGER) DESC
    LIMIT 6");

if(!$result){
    echo json_encode(['error' => 'Error al seleccionar los movimientos: ' . pg_last_error($conexion)]);
    exit;
}

$movirecien = [];

while($row = pg_fetch_assoc($result)){
    // Formatear fecha de forma más legible
    $fecha = date('d/m/Y H:i', strtotime($row['fecha_movimiento']));
    
    // Limpiar la observación si es necesario
    $observacion = trim($row['observacion']);
    
    // Si es una venta, hacer el texto más descriptivo
    if (strpos($observacion, 'Venta') !== false) {
        // Extraer el código de venta si existe
        if (preg_match('/Venta\s*-\s*(\S+)/', $observacion, $matches)) {
            $codigoVenta = $matches[1];
            $observacion = "Venta registrada - $codigoVenta";
        }
    }
    
    $movirecien[] = [
        'fecha_movimiento' => $fecha,
        'producto_nombre' => trim($row['producto_nombre']),
        'observacion' => $observacion,
        'tipo' => (strpos($observacion, 'Entrada') !== false) ? 'entrada' : 'venta'
    ];
}

header('Content-Type: application/json');
echo json_encode($movirecien);
?>