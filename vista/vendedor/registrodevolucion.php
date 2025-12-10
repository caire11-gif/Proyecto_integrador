<?php
// CONFIGURACIÓN DE ERRORES
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// INICIAR SESIÓN PRIMERO
session_start();

// HEADERS PARA JSON - AL INICIO
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// LIMPIAR BUFFER DE SALIDA
if (ob_get_length()) ob_clean();

$conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");

if(!$conexion){
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

$usuariovendedor = $_SESSION['nombreusuariovendedor'] ?? '';
$apellidovendedor = $_SESSION['apellidousuariovendedor'] ?? '';

$inicialNombre = substr($usuariovendedor, 0, 1);
$inicialApellido = substr($apellidovendedor, 0, 1);

// FUNCIÓN PARA VERIFICAR ESTRUCTURA DE TABLAS
function verificarEstructuraTablas($conexion) {
    error_log("=== VERIFICANDO ESTRUCTURA DE TABLAS ===");
    
    // Verificar tabla notacredito
    $query = "SELECT column_name, data_type FROM information_schema.columns 
              WHERE table_name = 'notacredito' ORDER BY ordinal_position";
    $result = pg_query($conexion, $query);
    $columnas = pg_fetch_all($result) ?: [];
    error_log("COLUMNAS NOTACREDITO: " . json_encode($columnas));
    
    return $columnas;
}

// FUNCIÓN PARA OBTENER DETALLES DE VENTA POR CÓDIGO DE VENTA
function obtenerDetallesVenta($conexion, $cod_venta) {
    error_log("Buscando detalles para venta: " . $cod_venta);
    
    // Primero obtener la venta específica
    $queryVenta = "SELECT v.*, td.nombre as documento_nombre, td.serie, td.numero, mp.nombre as metodo_pago
                   FROM venta v
                   LEFT JOIN tipodocumento td ON v.cod_tipodocumento = td.cod_tipodocumento
                   LEFT JOIN metodopago mp ON v.cod_metodopago = mp.cod_metodopago
                   WHERE v.cod_venta = '$cod_venta'";
    
    $resultVenta = pg_query($conexion, $queryVenta);
    
    if(!$resultVenta || pg_num_rows($resultVenta) === 0) {
        error_log("No se encontró venta con código: " . $cod_venta);
        return ['venta' => null, 'detalles' => []];
    }
    
    $venta = pg_fetch_assoc($resultVenta);
    error_log("Venta encontrada: " . json_encode($venta));
    
    // Obtener detalles de la venta específica
    $queryDetalles = "SELECT dv.*, p.nombre as producto_nombre, p.stock, p.precio_venta
                      FROM detalleventa dv
                      LEFT JOIN producto p ON dv.cod_producto = p.cod_producto
                      WHERE dv.cod_venta = '$cod_venta'
                      ORDER BY dv.cod_detalleventa";
    
    $resultDetalles = pg_query($conexion, $queryDetalles);
    $detalles = [];
    
    if($resultDetalles && pg_num_rows($resultDetalles) > 0) {
        $detalles = pg_fetch_all($resultDetalles);
        error_log("Detalles encontrados: " . count($detalles));
    } else {
        error_log("No se encontraron detalles para venta: " . $cod_venta);
    }
    
    return ['venta' => $venta, 'detalles' => $detalles];
}

// FUNCIÓN PARA VERIFICAR SI YA EXISTE NOTA DE CRÉDITO PARA DETALLE DE VENTA
function existeNotaCreditoVenta($conexion, $cod_detalleventa) {
    $query = "SELECT COUNT(*) as count 
              FROM notacredito 
              WHERE cod_detalleventa = '$cod_detalleventa'";
    
    $result = pg_query($conexion, $query);
    if($result) {
        $row = pg_fetch_assoc($result);
        $count = $row ? $row['count'] : 0;
        return $count > 0;
    }
    return false;
}

// FUNCIÓN PARA OBTENER NOTAS DE CRÉDITO EXISTENTES PARA UNA VENTA
function obtenerNotasCreditoVenta($conexion, $cod_venta) {
    $query = "SELECT nc.*, dv.cod_producto, p.nombre as producto_nombre
              FROM notacredito nc
              JOIN detalleventa dv ON nc.cod_detalleventa = dv.cod_detalleventa
              JOIN producto p ON dv.cod_producto = p.cod_producto
              WHERE dv.cod_venta = '$cod_venta'
              ORDER BY nc.fecha_notacredito DESC";
    
    $result = pg_query($conexion, $query);
    if($result && pg_num_rows($result) > 0) {
        return pg_fetch_all($result);
    }
    return [];
}

// FUNCIÓN PARA OBTENER EL PRÓXIMO NÚMERO SECUENCIAL DE NOTA DE CRÉDITO
function obtenerProximoNumeroNotaCredito($conexion) {
    // Buscar el máximo número actual en la base de datos
    $query = "SELECT MAX(CAST(SUBSTRING(cod_notacredito FROM 3) AS INTEGER)) as max_num 
              FROM notacredito 
              WHERE cod_notacredito ~ '^NC[0-9]+$'";
    
    $result = pg_query($conexion, $query);
    if($result) {
        $row = pg_fetch_assoc($result);
        $max_num = $row ? intval($row['max_num']) : 0;
        error_log("Número máximo encontrado: $max_num");
        return $max_num + 1;
    }
    error_log("No se encontraron notas de crédito existentes, empezando desde 1");
    return 1; // Si hay error o no hay registros, empezar desde 1
}

// FUNCIÓN PARA GENERAR CÓDIGO DE INVENTARIO ALEATORIO (INV + 7 dígitos)
function generarCodigoInventario($conexion) {
    $intentos = 0;
    $max_intentos = 10;
    
    while ($intentos < $max_intentos) {
        // Generar código aleatorio INV + 7 dígitos
        $numero = mt_rand(1000000, 9999999);
        $cod_inventario = 'INV' . $numero;
        
        // Verificar si ya existe
        $query = "SELECT COUNT(*) as count FROM registroinventario WHERE cod_inventario = '$cod_inventario'";
        $result = pg_query($conexion, $query);
        
        if($result) {
            $row = pg_fetch_assoc($result);
            if($row['count'] == 0) {
                error_log("Código de inventario generado: $cod_inventario");
                return $cod_inventario;
            }
        }
        
        $intentos++;
    }
    
    // Si no se pudo generar uno único después de varios intentos, usar timestamp
    $timestamp = substr(time(), -7);
    $cod_inventario = 'INV' . $timestamp;
    error_log("Usando código de inventario basado en timestamp: $cod_inventario");
    return $cod_inventario;
}

// FUNCIÓN PARA OBTENER EL CÓDIGO DE TIPO DE MOVIMIENTO PARA DEVOLUCIONES
function obtenerCodigoTipoMovimientoDevolucion($conexion) {
    $query = "SELECT cod_tipomovimiento FROM tipomovimiento WHERE nombre ILIKE '%devolución%' OR nombre ILIKE '%devolucion%' LIMIT 1";
    $result = pg_query($conexion, $query);
    
    if($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        return $row['cod_tipomovimiento'];
    }
    
    // Si no existe, crear uno por defecto
    $cod_tipomovimiento = 'TMDEV';
    $queryInsert = "INSERT INTO tipomovimiento (cod_tipomovimiento, nombre) VALUES ('$cod_tipomovimiento', 'Devolución Venta')";
    pg_query($conexion, $queryInsert);
    
    return $cod_tipomovimiento;
}

// PROCESAR CREACIÓN DE NOTA DE CRÉDITO - VERSIÓN MEJORADA CON SECUENCIA NC01, NC02, etc. Y REGISTRO EN INVENTARIO
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_nota_credito') {
    
    // LIMPIAR BUFFER ANTES DE PROCESAR
    if (ob_get_length()) ob_clean();
    
    $cod_detalleventa = pg_escape_string($conexion, $_POST['cod_detalleventa']);
    $cantidad = intval($_POST['cantidad']);
    $motivo = pg_escape_string($conexion, $_POST['motivo']);
    $cod_venta = pg_escape_string($conexion, $_POST['cod_venta']);
    
    error_log("=== INICIANDO CREACIÓN NOTA CRÉDITO ===");
    error_log("Datos recibidos: cod_detalleventa=$cod_detalleventa, cantidad=$cantidad, motivo=$motivo, cod_venta=$cod_venta");
    
    // Iniciar transacción
    pg_query($conexion, "BEGIN");
    
    try {
        // 1. Verificar si ya existe nota de crédito para este detalle de venta
        if(existeNotaCreditoVenta($conexion, $cod_detalleventa)) {
            throw new Exception("Ya existe una nota de crédito para este producto en esta venta.");
        }
        
        // 2. Obtener información del detalle de venta
        $queryDetalle = "SELECT dv.*, p.cod_producto, p.nombre as producto_nombre, v.cod_venta, p.precio_venta
                         FROM detalleventa dv 
                         JOIN producto p ON dv.cod_producto = p.cod_producto 
                         JOIN venta v ON dv.cod_venta = v.cod_venta
                         WHERE dv.cod_detalleventa = '$cod_detalleventa'";
        $resultDetalle = pg_query($conexion, $queryDetalle);
        
        if(!$resultDetalle) {
            throw new Exception("Error en consulta de detalle: " . pg_last_error($conexion));
        }
        
        $detalleData = pg_fetch_assoc($resultDetalle);
        
        if(!$detalleData) {
            throw new Exception("No se encontró el detalle de venta con ID: $cod_detalleventa");
        }
        
        error_log("Detalle encontrado: " . json_encode($detalleData));
        
        $precio_unitario = floatval($detalleData['precio_unitario']);
        $cod_producto = $detalleData['cod_producto'];
        $producto_nombre = $detalleData['producto_nombre'];
        $cod_venta_real = $detalleData['cod_venta'];
        $cantidad_vendida = intval($detalleData['cantidad_unidades']);
        $precio_venta_actual = floatval($detalleData['precio_venta']);
        
        // 3. Validar cantidad
        error_log("Validando cantidad: $cantidad vs $cantidad_vendida");
        if($cantidad > $cantidad_vendida) {
            throw new Exception("La cantidad a devolver ($cantidad) no puede ser mayor a la cantidad vendida ($cantidad_vendida).");
        }
        
        if($cantidad < 1) {
            throw new Exception("La cantidad debe ser al menos 1.");
        }
        
        // 4. Calcular monto de devolución
        $monto_devolucion = $precio_unitario * $cantidad;
        error_log("Monto calculado: $monto_devolucion");
        
        // 5. Obtener el próximo número secuencial para nota de crédito
        $proximoNumero = obtenerProximoNumeroNotaCredito($conexion);
        
        // Formatear el código: NC01, NC02, ..., NC10, NC11, etc.
        if($proximoNumero < 10) {
            $cod_notacredito = 'NC0' . $proximoNumero;
        } else {
            $cod_notacredito = 'NC' . $proximoNumero;
        }
        
        error_log("Código nota crédito generado: $cod_notacredito (secuencia: $proximoNumero)");
        
        // 6. Obtener código de usuario (de la sesión) - usar uno por defecto si no existe
        $cod_usuario = 'USU001'; // Usar un código de usuario por defecto
        
        // 7. INSERTAR nota de crédito en la tabla notacredito (solo para ventas, cod_detallecompra será NULL)
        $queryNotaCredito = "INSERT INTO notacredito (
                            cod_notacredito, 
                            cod_detalleventa, 
                            cod_detallecompra, 
                            cod_usuario, 
                            fecha_notacredito, 
                            motivo, 
                            cantidad_unidades, 
                            monto_devolucion
                        ) VALUES (
                            '$cod_notacredito',
                            '$cod_detalleventa',
                            NULL,  -- cod_detallecompra es NULL para devoluciones de ventas
                            '$cod_usuario',
                            CURRENT_DATE,
                            '$motivo',
                            $cantidad,
                            $monto_devolucion
                        )";
        
        error_log("Ejecutando query: $queryNotaCredito");
        $resultNota = pg_query($conexion, $queryNotaCredito);
        if(!$resultNota) {
            $error = pg_last_error($conexion);
            error_log("Error en INSERT notacredito: $error");
            throw new Exception("Error al crear la nota de crédito: $error");
        }
        error_log("Nota de crédito insertada correctamente en tabla notacredito");
        
        // 8. AUMENTAR stock del producto
        $queryUpdateStock = "UPDATE producto SET stock = stock + $cantidad WHERE cod_producto = '$cod_producto'";
        error_log("Ejecutando query: $queryUpdateStock");
        $resultUpdate = pg_query($conexion, $queryUpdateStock);
        if(!$resultUpdate) {
            $error = pg_last_error($conexion);
            error_log("Error en UPDATE stock: $error");
            throw new Exception("Error al actualizar stock del producto.");
        }
        error_log("Stock actualizado correctamente");
        
        // 9. REGISTRAR EN INVENTARIO - NUEVA FUNCIONALIDAD CON TIMESTAMP
        $cod_inventario = generarCodigoInventario($conexion);
        
        $cod_tipomovimiento = obtenerCodigoTipoMovimientoDevolucion($conexion);
        $total_inventario = $precio_venta_actual * $cantidad;
        
        // Usar CURRENT_TIMESTAMP para incluir hora actual
        $queryInventario = "INSERT INTO registroinventario (
                            cod_inventario,
                            cod_usuario,
                            fecha_inventario,
                            cod_producto,
                            cod_tipomovimiento,
                            cantidad,
                            precio_unitario,
                            total,
                            cod_notacredito
                        ) VALUES (
                            '$cod_inventario',
                            '$cod_usuario',
                            CURRENT_TIMESTAMP,  -- ¡IMPORTANTE! Usar TIMESTAMP en lugar de DATE
                            '$cod_producto',
                            '$cod_tipomovimiento',
                            $cantidad,
                            $precio_venta_actual,
                            $total_inventario,
                            '$cod_notacredito'
                        )";
        
        error_log("Ejecutando query registro inventario: $queryInventario");
        $resultInventario = pg_query($conexion, $queryInventario);
        if(!$resultInventario) {
            $error = pg_last_error($conexion);
            error_log("Error en INSERT registroinventario: $error");
            throw new Exception("Error al registrar en inventario: $error");
        }
        error_log("Registro de inventario creado correctamente con timestamp");
        
        // Confirmar transacción
        pg_query($conexion, "COMMIT");
        error_log("Transacción completada exitosamente");
        
        echo json_encode([
            'success' => true, 
            'message' => "✅ NOTA DE CRÉDITO CREADA EXITOSAMENTE\n\n📋 Código: $cod_notacredito\n📦 Producto: $producto_nombre\n🔢 Cantidad: $cantidad unidades\n💰 Monto: S/ " . number_format($monto_devolucion, 2) . "\n📈 Stock actualizado: +$cantidad unidades\n📊 Registro en inventario: $cod_inventario",
            'cod_notacredito' => $cod_notacredito,
            'monto' => $monto_devolucion,
            'cod_inventario' => $cod_inventario
        ]);
        exit;
        
    } catch (Exception $e) {
        pg_query($conexion, "ROLLBACK");
        error_log("ERROR EN TRANSACCIÓN: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => '❌ Error: ' . $e->getMessage()]);
        exit;
    }
}

// API para obtener detalles de venta
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'obtener_detalles_venta') {
    
    // LIMPIAR BUFFER ANTES DE PROCESAR
    if (ob_get_length()) ob_clean();
    
    $cod_venta = pg_escape_string($conexion, $_POST['cod_venta']);
    $detalles = obtenerDetallesVenta($conexion, $cod_venta);
    
    // Obtener notas de crédito existentes si hay venta
    if($detalles['venta']) {
        $notas_credito = obtenerNotasCreditoVenta($conexion, $detalles['venta']['cod_venta']);
        $detalles['notas_credito'] = $notas_credito;
    }
    
    echo json_encode($detalles);
    exit;
}

// SI LLEGAMOS AQUÍ, ES PORQUE ES UNA PETICIÓN NORMAL (NO API)
// CAMBIAMOS EL CONTENT TYPE A HTML PARA LA PÁGINA WEB
header('Content-Type: text/html; charset=utf-8');

// Ejecutar verificación
$columnasNotaCredito = verificarEstructuraTablas($conexion);

// OBTENER SOLO LAS VENTAS REALES QUE EXISTEN EN LA BSD
$queryComprobantes = "SELECT 
                        v.cod_venta,
                        v.cod_tipodocumento,
                        v.fecha_venta,
                        v.dni,
                        v.nombre as cliente_nombre,
                        v.email,
                        v.cod_metodopago,
                        td.nombre as documento_nombre,
                        td.serie,
                        td.numero,
                        mp.nombre as metodo_pago,
                        (SELECT SUM(dv.precio_unitario * dv.cantidad_unidades) 
                         FROM detalleventa dv 
                         WHERE dv.cod_venta = v.cod_venta) as total_venta,
                        (SELECT COUNT(*) 
                         FROM detalleventa dv 
                         WHERE dv.cod_venta = v.cod_venta) as total_productos
                      FROM venta v
                      LEFT JOIN tipodocumento td ON v.cod_tipodocumento = td.cod_tipodocumento
                      LEFT JOIN metodopago mp ON v.cod_metodopago = mp.cod_metodopago
                      WHERE EXISTS (
                          SELECT 1 FROM detalleventa dv WHERE dv.cod_venta = v.cod_venta
                      )
                      ORDER BY v.fecha_venta DESC, v.cod_venta DESC";

$resultComprobantes = pg_query($conexion, $queryComprobantes);
$comprobantes = [];
if($resultComprobantes) {
    $comprobantes = pg_fetch_all($resultComprobantes) ?: [];
}

error_log("VENTAS ENCONTRADAS: " . count($comprobantes));

// PROCESAR BÚSQUEDA MEJORADA
$resultadosBusqueda = [];
if(isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $termino = pg_escape_string($conexion, $_GET['buscar']);
    $queryBusqueda = "SELECT 
                        v.cod_venta,
                        v.cod_tipodocumento,
                        v.fecha_venta,
                        v.dni,
                        v.nombre as cliente_nombre,
                        v.email,
                        v.cod_metodopago,
                        td.nombre as documento_nombre,
                        td.serie,
                        td.numero,
                        mp.nombre as metodo_pago,
                        (SELECT SUM(dv.precio_unitario * dv.cantidad_unidades) 
                         FROM detalleventa dv 
                         WHERE dv.cod_venta = v.cod_venta) as total_venta,
                        (SELECT COUNT(*) 
                         FROM detalleventa dv 
                         WHERE dv.cod_venta = v.cod_venta) as total_productos
                      FROM venta v
                      LEFT JOIN tipodocumento td ON v.cod_tipodocumento = td.cod_tipodocumento
                      LEFT JOIN metodopago mp ON v.cod_metodopago = mp.cod_metodopago
                      WHERE EXISTS (
                          SELECT 1 FROM detalleventa dv WHERE dv.cod_venta = v.cod_venta
                      )
                      AND (v.cod_venta ILIKE '%$termino%' 
                         OR v.dni ILIKE '%$termino%'
                         OR v.nombre ILIKE '%$termino%'
                         OR td.nombre ILIKE '%$termino%'
                         OR td.serie ILIKE '%$termino%'
                         OR CAST(td.numero AS TEXT) ILIKE '%$termino%'
                         OR v.cod_tipodocumento ILIKE '%$termino%')
                      ORDER BY v.fecha_venta DESC, v.cod_venta DESC";
    
    $resultBusqueda = pg_query($conexion, $queryBusqueda);
    if($resultBusqueda) {
        $resultadosBusqueda = pg_fetch_all($resultBusqueda) ?: [];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Registro de Devoluciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/vendedor-estilo.css">
    <link rel="stylesheet" href="css/vendedor-boton/boton.css">
    <style>
        /* ELIMINAR EL FONDO OSCURO DEL MODAL */
        .modal-backdrop {
            display: none !important;
            opacity: 0 !important;
        }

        /* EVITAR QUE EL BODY SE VUELVA OPACO */
        body.modal-open {
            overflow: auto !important;
            padding-right: 0 !important;
        }

        /* ASEGURAR QUE EL MODAL SEA COMPLETAMENTE VISIBLE */
        .modal {
            background-color: transparent !important;
        }

        .modal-content {
            background: white !important;
            opacity: 1 !important;
            box-shadow: 0 5px 25px rgba(0,0,0,0.3) !important;
        }

        .devoluciones-main {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .buscar-venta, .info-venta {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }

        .busqueda-venta {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .busqueda-input {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .busqueda-input input {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }

        .resultados-busqueda {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 15px;
            background: white;
        }

        .resultado-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .resultado-item:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateX(5px);
        }

        .resultado-item:last-child {
            border-bottom: none;
        }

        .documento-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .documento-datos {
            flex: 1;
        }

        .documento-numero {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .total-badge {
            background: #27ae60;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            margin-left: 10px;
        }

        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .producto-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            border: 1px solid #e8e8e8;
            transition: all 0.3s ease;
        }

        .producto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .producto-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }

        .producto-title {
            flex: 1;
        }

        .producto-codigo {
            background: #34495e;
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-family: monospace;
        }

        .producto-info {
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
        }

        .info-label {
            font-weight: 600;
            color: #555;
        }

        .info-value {
            font-weight: 500;
            color: #2c3e50;
        }

        .btn-devolucion {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-devolucion:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
            color: white;
        }

        .btn-devolucion:disabled {
            background: #95a5a6;
            transform: none;
            box-shadow: none;
        }

        .resumen-venta {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .cliente-info {
            background: #e8f4fd;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 5px solid #3498db;
        }

        .notas-credito {
            background: #fff3cd;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            border-left: 5px solid #ffc107;
        }

        .nota-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #28a745;
        }

        .modal-devolucion .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-devolucion .modal-header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .form-control:focus {
            border-color: #e74c3c;
            box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
        }

        .badge-success {
            background: #27ae60;
        }

        .badge-warning {
            background: #f39c12;
        }

        .badge-info {
            background: #3498db;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    
    <div class="grid">
        <!-- BARRA LATERAL -->
        <main class="principal">
            <button class="boton-menu" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>

            <div class="barra-lateral" id="barra-lateral">
                <div class="logo">
                    <h4><i class="fas fa-store"></i> MAD MARKET</h4>
                    <small id="userRole">Vendedor</small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.html" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="nuevaventa.php" class="nav-link"><ul><i class="fas fa-cash-register"></i>Nueva Venta</ul></a>
                    <a href="registrodevolucion.php" class="nav-link active"><ul><i class="fas fa-undo-alt"></i>Registrar Devolución</ul></a>
                    <a href="boletafactura.html" class="nav-link"><ul><i class="fas fa-receipt"></i>Boletas/Facturas</ul></a>
                    <a href="consultarstock.html" class="nav-link"><ul><i class="fas fa-boxes"></i>Consulta Stock</ul></a>
                </div>
            </div>
        </main>

        <div class="secundario">
            <div class="header">
                <div class="usuario-info">
                    <div class="usuario-avatar" id="usuarioAvatar"><?php echo htmlspecialchars($inicialNombre.$inicialApellido)?></div>
                    <div>
                        <div class="fw-bold fs-5" id="userName"><?php echo htmlspecialchars($usuariovendedor." ".$apellidovendedor) ?></div>
                        <small class="text-muted" id="userPosition">Vendedor</small>
                    </div>
                    <div class="dropdown-container">
                        <div class="dropdown">
                            <button class="dropdown-btn" id="dropdownBtn">
                                <span class="arrow" id="arrow">▲</span>
                            </button>
                            <ul class="dropdown-list" id="dropdownList">
                                <a href="../login.php" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <main class="devoluciones-main">
                
                <section class="buscar-venta">
                    <h3 class="mb-4"><i class="fas fa-undo-alt"></i> Registro de Devoluciones</h3>
                    <p class="text-muted mb-4">Busca y selecciona una venta real para procesar devoluciones de productos específicos</p>
                    
                    <!-- Debug info -->
                    <div class="debug-info">
                        <strong>DEBUG:</strong> Mostrando <?php echo count($comprobantes); ?> ventas encontradas en la base de datos
                    </div>
                    
                    <div class="busqueda-venta">
                        <form method="GET" action="">
                            <div class="busqueda-input">
                                <input type="text" name="buscar" id="inputBusquedaVenta" 
                                       placeholder="🔍 Buscar por código de venta, DNI cliente, nombre, serie o número..." 
                                       value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>" 
                                       autofocus>
                                <button type="submit" id="btnBuscarVenta" class="btn btn-primary btn-lg">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </form>

                        <div class="resultados-busqueda" id="resultadosBusqueda">
                            <?php 
                            $documentosMostrar = isset($_GET['buscar']) && !empty($_GET['buscar']) ? $resultadosBusqueda : $comprobantes;
                            
                            if(!empty($documentosMostrar)): ?>
                                <div class="p-3 border-bottom bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-list"></i>
                                        <?php echo isset($_GET['buscar']) ? 'Resultados de búsqueda' : 'Ventas Registradas'; ?>
                                        <span class="badge bg-primary ms-2"><?php echo count($documentosMostrar); ?></span>
                                    </h6>
                                </div>
                                <?php foreach($documentosMostrar as $documento): ?>
                                    <div class="resultado-item" onclick="mostrarDetallesVenta('<?php echo $documento['cod_venta']; ?>')">
                                        <div class="documento-info">
                                            <div class="documento-datos">
                                                <strong class="fs-5">
                                                    <?php echo $documento['documento_nombre'] ?: 'Sin documento'; ?>
                                                    <?php if($documento['total_venta']): ?>
                                                        <span class="total-badge">S/ <?php echo number_format($documento['total_venta'], 2); ?></span>
                                                    <?php endif; ?>
                                                </strong>
                                                <div class="mt-2">
                                                    <span class="badge bg-secondary">Venta: <?php echo $documento['cod_venta']; ?></span>
                                                    <?php if($documento['serie']): ?>
                                                        <span class="badge bg-info">Serie: <?php echo $documento['serie']; ?>-<?php echo $documento['numero']; ?></span>
                                                    <?php endif; ?>
                                                    <span class="badge bg-warning">Productos: <?php echo $documento['total_productos']; ?></span>
                                                </div>
                                                <div class="mt-1">
                                                    <small><strong>Cliente:</strong> <?php echo $documento['cliente_nombre']; ?> | <strong>DNI:</strong> <?php echo $documento['dni']; ?></small>
                                                </div>
                                                <div class="mt-1">
                                                    <small><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($documento['fecha_venta'])); ?> 
                                                    <?php if($documento['metodo_pago']): ?>
                                                        | <strong>Método:</strong> <?php echo $documento['metodo_pago']; ?>
                                                    <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="documento-numero">
                                                <i class="fas fa-receipt fa-lg"></i>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-receipt"></i>
                                    <h5>No hay ventas registradas</h5>
                                    <p class="mb-3">No se encontraron ventas con productos en el sistema.</p>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Información:</strong> Las ventas aparecerán aquí después de realizar ventas en el sistema.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="info-venta" id="seccionInfoVenta" style="display: none;">
                    <h3 class="mb-4"><i class="fas fa-file-invoice"></i> Detalles de la Venta</h3>
                    <div class="venta-detalle" id="detalleVenta">
                        <!-- Información aparecerá aquí via JavaScript -->
                    </div>
                </section>

            </main>
        </div>
    </div>

    <!-- Modal para registrar devolución -->
    <div class="modal fade" id="modalDevolucion" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-devolucion">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-undo-alt"></i> Registrar Devolución</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formDevolucion">
                        <input type="hidden" id="cod_detalleventa_devolucion" name="cod_detalleventa">
                        <input type="hidden" id="cod_venta_devolucion" name="cod_venta">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Producto:</label>
                                    <input type="text" id="producto_nombre_devolucion" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Código Producto:</label>
                                    <input type="text" id="cod_producto_devolucion" class="form-control" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Cantidad máxima disponible:</label>
                                    <input type="text" id="cantidad_maxima" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Precio unitario:</label>
                                    <input type="text" id="precio_unitario_devolucion" class="form-control" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Cantidad a devolver: <span id="monto_calculado" class="text-success"></span></label>
                            <input type="number" id="cantidad_devolucion" name="cantidad" class="form-control" min="1" value="1" required onchange="calcularMonto()">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Motivo de la devolución:</label>
                            <textarea id="motivo_devolucion" name="motivo" class="form-control" rows="3" placeholder="Describe el motivo de la devolución (ej: producto defectuoso, cambio de talla, etc.)..." required></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Información:</strong> Al confirmar, se creará una nota de crédito, se aumentará el stock del producto y se registrará en el inventario automáticamente.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" onclick="confirmarDevolucion()">
                        <i class="fas fa-check-circle"></i> Crear Nota de Crédito
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../controlador/vendedor/dropdown.js"></script>
    <script src="../../controlador/vendedor/barralateral.js"></script>
    <script>
        // FUNCIÓN PARA MOSTRAR DETALLES DE VENTA
        async function mostrarDetallesVenta(cod_venta) {
            console.log('Cargando detalles para venta:', cod_venta);
            
            document.getElementById('seccionInfoVenta').style.display = 'block';
            document.getElementById('detalleVenta').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2">Cargando detalles de la venta...</p>
                    <small class="text-muted">Venta: ${cod_venta}</small>
                </div>
            `;

            try {
                const formData = new FormData();
                formData.append('accion', 'obtener_detalles_venta');
                formData.append('cod_venta', cod_venta);
                
                const response = await fetch('registrodevolucion.php', {
                    method: 'POST',
                    body: formData
                });
                
                // OBTENER LA RESPUESTA COMO TEXTO PRIMERO PARA DEBUGGING
                const responseText = await response.text();
                console.log('Respuesta cruda:', responseText);
                
                // VERIFICAR SI ES HTML (ERROR)
                if (responseText.trim().startsWith('<') || responseText.includes('<br />') || responseText.includes('<b>')) {
                    throw new Error('El servidor devolvió HTML en lugar de JSON. Posible error PHP:\n' + responseText);
                }
                
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status} - ${responseText}`);
                }
                
                // INTENTAR PARSEAR COMO JSON
                const data = JSON.parse(responseText);
                console.log('Datos recibidos:', data);
                
                if (!data.venta) {
                    document.getElementById('detalleVenta').innerHTML = `
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <h5>No se encontró la venta</h5>
                            <p class="mb-0">La venta ${cod_venta} no existe o no tiene productos.</p>
                        </div>
                    `;
                    return;
                }

                const venta = data.venta;
                const detalles = data.detalles;
                const notasCredito = data.notas_credito || [];
                
                let productosHTML = '';
                let totalVenta = 0;
                
                if (detalles.length === 0) {
                    productosHTML = `
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-box-open fa-2x mb-3"></i>
                            <h5>No hay productos en esta venta</h5>
                            <p class="mb-0">La venta no contiene productos para devolver.</p>
                        </div>
                    `;
                } else {
                    // GENERAR TARJETAS DE PRODUCTOS
                    detalles.forEach((detalle, index) => {
                        const tieneNotaCredito = notasCredito.some(nota => nota.cod_detalleventa === detalle.cod_detalleventa);
                        const subtotal = parseFloat(detalle.precio_unitario) * parseInt(detalle.cantidad_unidades);
                        totalVenta += subtotal;
                        
                        productosHTML += `
                            <div class="producto-card">
                                <div class="producto-header">
                                    <div class="producto-title">
                                        <h5 class="mb-1">${detalle.producto_nombre}</h5>
                                        <span class="producto-codigo">${detalle.cod_producto}</span>
                                    </div>
                                    ${tieneNotaCredito ? 
                                        '<span class="badge bg-success"><i class="fas fa-check"></i> Ya Devuelto</span>' : 
                                        '<span class="badge bg-warning"><i class="fas fa-clock"></i> Pendiente</span>'
                                    }
                                </div>
                                
                                <div class="producto-info">
                                    <div class="info-row">
                                        <span class="info-label">Precio Unitario:</span>
                                        <span class="info-value">S/ ${parseFloat(detalle.precio_unitario).toFixed(2)}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Cantidad Vendida:</span>
                                        <span class="info-value">${detalle.cantidad_unidades} unidades</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Subtotal:</span>
                                        <span class="info-value text-success fw-bold">S/ ${subtotal.toFixed(2)}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Stock Actual:</span>
                                        <span class="info-value">${detalle.stock} unidades</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">ID Detalle:</span>
                                        <span class="info-value text-muted">${detalle.cod_detalleventa}</span>
                                    </div>
                                </div>
                                
                                <button class="btn-devolucion" 
                                        onclick="abrirModalDevolucion(
                                            '${detalle.cod_detalleventa}',
                                            '${cod_venta}',
                                            '${detalle.producto_nombre}',
                                            '${detalle.cod_producto}',
                                            ${detalle.cantidad_unidades},
                                            ${detalle.precio_unitario}
                                        )"
                                        ${tieneNotaCredito ? 'disabled' : ''}>
                                    <i class="fas fa-undo-alt"></i> 
                                    ${tieneNotaCredito ? 'Ya Devuelto' : 'Registrar Devolución'}
                                </button>
                            </div>
                        `;
                    });
                }

                let notasHTML = '';
                if (notasCredito.length > 0) {
                    notasHTML = `
                        <div class="notas-credito">
                            <h5><i class="fas fa-file-invoice-dollar"></i> Notas de Crédito Generadas</h5>
                            ${notasCredito.map(nota => `
                                <div class="nota-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>${nota.cod_notacredito}</strong>
                                            <span class="badge bg-success ms-2">${nota.producto_nombre}</span>
                                        </div>
                                        <span class="text-success fw-bold">S/ ${parseFloat(nota.monto_devolucion).toFixed(2)}</span>
                                    </div>
                                    <div class="mt-2">
                                        <small><strong>Cantidad:</strong> ${nota.cantidad_unidades} unidades</small> | 
                                        <small><strong>Fecha:</strong> ${new Date(nota.fecha_notacredito).toLocaleDateString('es-PE')}</small>
                                    </div>
                                    <div class="mt-1">
                                        <small><strong>Motivo:</strong> ${nota.motivo}</small>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                }

                document.getElementById('detalleVenta').innerHTML = `
                    <div class="cliente-info">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-user"></i> Información del Cliente</h5>
                                <p><strong>Nombre:</strong> ${venta.nombre}</p>
                                <p><strong>DNI:</strong> ${venta.dni}</p>
                                ${venta.email ? `<p><strong>Email:</strong> ${venta.email}</p>` : ''}
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-receipt"></i> Información del Comprobante</h5>
                                <p><strong>Documento:</strong> ${venta.documento_nombre || 'No especificado'}</p>
                                ${venta.serie ? `<p><strong>Serie:</strong> ${venta.serie} - <strong>Número:</strong> ${venta.numero}</p>` : ''}
                                <p><strong>Método de Pago:</strong> ${venta.metodo_pago || 'No especificado'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="resumen-venta">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h6>Código Venta</h6>
                                <h4>${venta.cod_venta}</h4>
                            </div>
                            <div class="col-md-3">
                                <h6>Fecha de Venta</h6>
                                <h4>${new Date(venta.fecha_venta).toLocaleDateString('es-PE')}</h4>
                            </div>
                            <div class="col-md-3">
                                <h6>Total Venta</h6>
                                <h4>S/ ${parseFloat(venta.total_venta || totalVenta).toFixed(2)}</h4>
                            </div>
                            <div class="col-md-3">
                                <h6>Productos</h6>
                                <h4>${detalles.length}</h4>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3"><i class="fas fa-boxes"></i> Productos de la Venta</h5>
                    <div class="productos-grid">
                        ${productosHTML}
                    </div>
                    
                    ${notasHTML}
                `;

            } catch (error) {
                console.error('Error al cargar detalles:', error);
                document.getElementById('detalleVenta').innerHTML = `
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-times-circle fa-2x mb-3"></i>
                        <h5>Error al cargar los detalles de la venta</h5>
                        <p class="mb-0">${error.message}</p>
                        <small class="mt-2">Revisa la consola para más detalles</small>
                    </div>
                `;
            }
        }

        // FUNCIÓN PARA ABRIR MODAL DE DEVOLUCIÓN
        function abrirModalDevolucion(cod_detalleventa, cod_venta, producto_nombre, cod_producto, cantidad_maxima, precio_unitario) {
            document.getElementById('cod_detalleventa_devolucion').value = cod_detalleventa;
            document.getElementById('cod_venta_devolucion').value = cod_venta;
            document.getElementById('producto_nombre_devolucion').value = producto_nombre;
            document.getElementById('cod_producto_devolucion').value = cod_producto;
            document.getElementById('cantidad_maxima').value = cantidad_maxima + ' unidades';
            document.getElementById('precio_unitario_devolucion').value = 'S/ ' + parseFloat(precio_unitario).toFixed(2);
            document.getElementById('cantidad_devolucion').setAttribute('max', cantidad_maxima);
            document.getElementById('cantidad_devolucion').value = 1;
            document.getElementById('motivo_devolucion').value = '';
            
            calcularMonto();
            
            const modal = new bootstrap.Modal(document.getElementById('modalDevolucion'));
            modal.show();
        }

        // FUNCIÓN PARA CALCULAR MONTO DE DEVOLUCIÓN
        function calcularMonto() {
            const cantidad = document.getElementById('cantidad_devolucion').value;
            const precio = parseFloat(document.getElementById('precio_unitario_devolucion').value.replace('S/ ', ''));
            const monto = cantidad * precio;
            document.getElementById('monto_calculado').textContent = '(Monto: S/ ' + monto.toFixed(2) + ')';
        }

        // FUNCIÓN PARA CONFIRMAR DEVOLUCIÓN
        async function confirmarDevolucion() {
            const cod_detalleventa = document.getElementById('cod_detalleventa_devolucion').value;
            const cod_venta = document.getElementById('cod_venta_devolucion').value;
            const cantidad = document.getElementById('cantidad_devolucion').value;
            const motivo = document.getElementById('motivo_devolucion').value;
            const producto_nombre = document.getElementById('producto_nombre_devolucion').value;
            
            console.log("Datos a enviar:", {
                cod_detalleventa,
                cod_venta, 
                cantidad,
                motivo,
                producto_nombre
            });
            
            if(!motivo.trim()) {
                alert('❌ Por favor, describe el motivo de la devolución.');
                return;
            }
            
            if(cantidad < 1) {
                alert('❌ La cantidad debe ser al menos 1.');
                return;
            }
            
            if(!confirm(`¿Confirmar devolución del producto?\n\n📦 Producto: ${producto_nombre}\n🔢 Cantidad: ${cantidad} unidades\n💰 Monto aproximado: S/ ${(cantidad * parseFloat(document.getElementById('precio_unitario_devolucion').value.replace('S/ ', ''))).toFixed(2)}\n\nEsta acción creará una nota de crédito, actualizará el stock y registrará en inventario.`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('accion', 'crear_nota_credito');
                formData.append('cod_detalleventa', cod_detalleventa);
                formData.append('cantidad', cantidad);
                formData.append('motivo', motivo);
                formData.append('cod_venta', cod_venta);
                
                console.log("Enviando petición...");
                const response = await fetch('registrodevolucion.php', {
                    method: 'POST',
                    body: formData
                });
                
                // OBTENER COMO TEXTO PRIMERO
                const responseText = await response.text();
                console.log("Respuesta cruda:", responseText);
                
                // VERIFICAR SI ES HTML (ERROR)
                if (responseText.trim().startsWith('<') || responseText.includes('<br />') || responseText.includes('<b>')) {
                    throw new Error('El servidor devolvió HTML en lugar de JSON. Posible error PHP:\n' + responseText.substring(0, 200) + '...');
                }
                
                const resultado = JSON.parse(responseText);
                console.log("Resultado:", resultado);
                
                if(resultado.success) {
                    alert(resultado.message);
                    
                    // Cerrar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalDevolucion'));
                    modal.hide();
                    
                    // Recargar detalles para actualizar la vista
                    mostrarDetallesVenta(cod_venta);
                    
                } else {
                    alert('❌ ' + resultado.message);
                }
                
            } catch (error) {
                console.error('Error en fetch:', error);
                alert('❌ Error de conexión: ' + error.message);
            }
        }
    </script>
</body>
</html>