<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

include '../conexion.php';

try {
    // Obtener datos para los select
    $result1 = pg_query($conexion, "SELECT cod_proveedor, nombre FROM proveedor");
    if(!$result1){
        throw new Exception("Error al cargar proveedores: " . pg_last_error($conexion));
    }

    $result2 = pg_query($conexion, "SELECT cod_tipodocumento, nombre FROM tipodocumento");
    if(!$result2){
        throw new Exception("Error al cargar tipos de documento: " . pg_last_error($conexion));
    }

    // Obtener productos CON PRECIO DE COSTO (precio por caja)
    $result3 = pg_query($conexion, "SELECT cod_producto, nombre, precio_costo, unidades_por_caja FROM producto");
    if(!$result3){
        throw new Exception("Error al cargar productos: " . pg_last_error($conexion));
    }

    // OBTENER CÓDIGOS DE TABLAS DE REFERENCIA
    $cod_metodopago = 'mp001';
    $cod_tiporeporte = 'rep001';
    $cod_tipomovimiento = 'mov001';
    $cod_tipoaccion = 'acc001';
    $cod_usuario = 'user005';

    // Recopilar todos los datos
    $data = [
        'success' => true,
        'usuario' => [
            'nombre' => 'Usuario',
            'apellido' => 'Demo', 
            'iniciales' => 'UD'
        ],
        'proveedores' => [],
        'tipos_documento' => [],
        'productos' => [],
        'codigos_referencia' => [
            'metodo_pago' => $cod_metodopago,
            'tipo_reporte' => $cod_tiporeporte,
            'tipo_movimiento' => $cod_tipomovimiento,
            'tipo_accion' => $cod_tipoaccion,
            'usuario' => $cod_usuario
        ]
    ];

    // Proveedores
    while($row = pg_fetch_assoc($result1)) {
        $data['proveedores'][] = $row;
    }

    // Tipos de documento
    while($row = pg_fetch_assoc($result2)) {
        $data['tipos_documento'][] = $row;
    }

    // Productos con precios
    while($row = pg_fetch_assoc($result3)) {
        $data['productos'][] = $row;
    }

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

pg_close($conexion);
?>