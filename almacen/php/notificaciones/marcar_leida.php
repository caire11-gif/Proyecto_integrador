<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include '../conexion.php';

// Leer el input JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Datos no válidos']);
    exit;
}

$cod_notificacion = $input['cod_notificacion'] ?? '';

try {
    if(empty($cod_notificacion)) {
        throw new Exception('Código de notificación no especificado');
    }

    $query = "UPDATE notificacion SET cod_estadonotificacion = 'en002' WHERE cod_notificacion = $1";
    $result = pg_query_params($conexion, $query, array($cod_notificacion));
    
    if(!$result) {
        throw new Exception('Error al marcar la alerta como leída: ' . pg_last_error($conexion));
    }

    if(pg_affected_rows($result) === 0) {
        throw new Exception('No se encontró la notificación especificada');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Alerta marcada como leída correctamente'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>