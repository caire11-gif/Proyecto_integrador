<?php include('../../login/ingresarlogin.php') ?>

<?php
$query_fechas = "SELECT 
    MIN(fecha_inventario) as fecha_minima,
    MAX(fecha_inventario) as fecha_maxima
FROM registroinventario";

$result_fechas = pg_query($conexion, $query_fechas);
$fecha_minima = date('Y-m-d');
$fecha_maxima = date('Y-m-d');

if($result_fechas && pg_num_rows($result_fechas) > 0) {
    $row = pg_fetch_assoc($result_fechas);
    $fecha_minima = date('Y-m-d', strtotime($row['fecha_minima']));
    $fecha_maxima = date('Y-m-d', strtotime($row['fecha_maxima']));
}

$valuemin=date('Y-m-d', strtotime('-1 month'));
$valuemax=date('Y-m-d');

header('Content-Type: application/json');
echo json_encode([
    'fecha_minima'=>$fecha_minima,
    'fecha_maxima'=>$fecha_maxima,
    'value_minima'=>$valuemin,
    'value_maxima'=>$valuemax
]);
?>