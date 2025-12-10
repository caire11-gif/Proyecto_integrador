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

// INCLUIR ARCHIVO DE LOGIN PARA OBTENER DATOS DEL USUARIO
include('../login/ingresarlogin.php');

// OBTENER DATOS DEL USUARIO DESDE LA SESIÓN
$usuarioencargado = $_SESSION['nombreusuarioencargado'] ?? '';
$apellidoencargado = $_SESSION['apellidousuarioencargado'] ?? '';
$cod_usuario = $_SESSION['cod_usuario'] ?? 'USU001';

$inicialNombre = substr($usuarioencargado, 0, 1);
$inicialApellido = substr($apellidoencargado, 0, 1);

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

// FUNCIÓN PARA OBTENER DETALLES DE COMPRA POR CÓDIGO DE COMPRA
function obtenerDetallesCompra($conexion, $cod_compra) {
    error_log("Buscando detalles para compra: " . $cod_compra);
    
    // Primero obtener la compra específica
    $queryCompra = "SELECT c.*, p.razon_social as proveedor_nombre, td.nombre as documento_nombre, 
                           td.serie, td.numero, mp.nombre as metodo_pago
                   FROM compra c
                   LEFT JOIN proveedor p ON c.cod_proveedor = p.cod_proveedor
                   LEFT JOIN tipodocumento td ON c.cod_tipodocumento = td.cod_tipodocumento
                   LEFT JOIN metodopago mp ON c.cod_metodopago = mp.cod_metodopago
                   WHERE c.cod_compra = '$cod_compra'";
    
    $resultCompra = pg_query($conexion, $queryCompra);
    
    if(!$resultCompra || pg_num_rows($resultCompra) === 0) {
        error_log("No se encontró compra con código: " . $cod_compra);
        return ['compra' => null, 'detalles' => []];
    }
    
    $compra = pg_fetch_assoc($resultCompra);
    error_log("Compra encontrada: " . json_encode($compra));
    
    // Obtener detalles de la compra específica
    $queryDetalles = "SELECT dc.*, pr.nombre as producto_nombre, pr.stock, pr.precio_compra_unidad
                      FROM detallecompra dc
                      LEFT JOIN producto pr ON dc.cod_producto = pr.cod_producto
                      WHERE dc.cod_compra = '$cod_compra'
                      ORDER BY dc.cod_detallecompra";
    
    $resultDetalles = pg_query($conexion, $queryDetalles);
    $detalles = [];
    
    if($resultDetalles && pg_num_rows($resultDetalles) > 0) {
        $detalles = pg_fetch_all($resultDetalles);
        error_log("Detalles encontrados: " . count($detalles));
    } else {
        error_log("No se encontraron detalles para compra: " . $cod_compra);
    }
    
    return ['compra' => $compra, 'detalles' => $detalles];
}

// FUNCIÓN PARA VERIFICAR SI YA EXISTE NOTA DE CRÉDITO PARA DETALLE DE COMPRA
function existeNotaCreditoCompra($conexion, $cod_detallecompra) {
    $query = "SELECT COUNT(*) as count 
              FROM notacredito 
              WHERE cod_detallecompra = '$cod_detallecompra'";
    
    $result = pg_query($conexion, $query);
    if($result) {
        $row = pg_fetch_assoc($result);
        $count = $row ? $row['count'] : 0;
        return $count > 0;
    }
    return false;
}

// FUNCIÓN PARA OBTENER NOTAS DE CRÉDITO EXISTENTES PARA UNA COMPRA
function obtenerNotasCreditoCompra($conexion, $cod_compra) {
    $query = "SELECT nc.*, dc.cod_producto, p.nombre as producto_nombre
              FROM notacredito nc
              JOIN detallecompra dc ON nc.cod_detallecompra = dc.cod_detallecompra
              JOIN producto p ON dc.cod_producto = p.cod_producto
              WHERE dc.cod_compra = '$cod_compra'
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

// FUNCIÓN PARA OBTENER EL CÓDIGO DE TIPO DE MOVIMIENTO PARA DEVOLUCIONES DE COMPRA
function obtenerCodigoTipoMovimientoDevolucionCompra($conexion) {
    $query = "SELECT cod_tipomovimiento FROM tipomovimiento WHERE nombre ILIKE '%devolución compra%' OR nombre ILIKE '%devolucion compra%' LIMIT 1";
    $result = pg_query($conexion, $query);
    
    if($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        return $row['cod_tipomovimiento'];
    }
    
    // Si no existe, crear uno por defecto
    $cod_tipomovimiento = 'TMDEVCOMP';
    $queryInsert = "INSERT INTO tipomovimiento (cod_tipomovimiento, nombre) VALUES ('$cod_tipomovimiento', 'Devolución Compra')";
    pg_query($conexion, $queryInsert);
    
    return $cod_tipomovimiento;
}

// PROCESAR CREACIÓN DE NOTA DE CRÉDITO PARA COMPRAS
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_nota_credito_compra') {
    
    // LIMPIAR BUFFER ANTES DE PROCESAR
    if (ob_get_length()) ob_clean();
    
    $cod_detallecompra = pg_escape_string($conexion, $_POST['cod_detallecompra']);
    $cantidad = intval($_POST['cantidad']);
    $motivo = pg_escape_string($conexion, $_POST['motivo']);
    $cod_compra = pg_escape_string($conexion, $_POST['cod_compra']);
    
    error_log("=== INICIANDO CREACIÓN NOTA CRÉDITO COMPRA ===");
    error_log("Datos recibidos: cod_detallecompra=$cod_detallecompra, cantidad=$cantidad, motivo=$motivo, cod_compra=$cod_compra");
    
    // Iniciar transacción
    pg_query($conexion, "BEGIN");
    
    try {
        // 1. Verificar si ya existe nota de crédito para este detalle de compra
        if(existeNotaCreditoCompra($conexion, $cod_detallecompra)) {
            throw new Exception("Ya existe una nota de crédito para este producto en esta compra.");
        }
        
        // 2. Obtener información del detalle de compra
        $queryDetalle = "SELECT dc.*, p.cod_producto, p.nombre as producto_nombre, c.cod_compra, 
                                p.precio_compra_unidad, dc.cantidad_unidades as cantidad_comprada
                         FROM detallecompra dc 
                         JOIN producto p ON dc.cod_producto = p.cod_producto 
                         JOIN compra c ON dc.cod_compra = c.cod_compra
                         WHERE dc.cod_detallecompra = '$cod_detallecompra'";
        $resultDetalle = pg_query($conexion, $queryDetalle);
        
        if(!$resultDetalle) {
            throw new Exception("Error en consulta de detalle: " . pg_last_error($conexion));
        }
        
        $detalleData = pg_fetch_assoc($resultDetalle);
        
        if(!$detalleData) {
            throw new Exception("No se encontró el detalle de compra con ID: $cod_detallecompra");
        }
        
        error_log("Detalle encontrado: " . json_encode($detalleData));
        
        $precio_compra_unidad = floatval($detalleData['precio_compra_unidad']);
        $cod_producto = $detalleData['cod_producto'];
        $producto_nombre = $detalleData['producto_nombre'];
        $cod_compra_real = $detalleData['cod_compra'];
        $cantidad_comprada = intval($detalleData['cantidad_comprada']);
        
        // 3. Validar cantidad
        error_log("Validando cantidad: $cantidad vs $cantidad_comprada");
        if($cantidad > $cantidad_comprada) {
            throw new Exception("La cantidad a devolver ($cantidad) no puede ser mayor a la cantidad comprada ($cantidad_comprada).");
        }
        
        if($cantidad < 1) {
            throw new Exception("La cantidad debe ser al menos 1.");
        }
        
        // 4. Calcular monto de devolución (basado en precio de compra)
        $monto_devolucion = $precio_compra_unidad * $cantidad;
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
        
        // 6. Obtener código de usuario (de la sesión)
        $cod_usuario = $_SESSION['cod_usuario'] ?? 'USU001';
        
        // 7. INSERTAR nota de crédito en la tabla notacredito (para compras, cod_detalleventa será NULL)
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
                            NULL,  -- cod_detalleventa es NULL para devoluciones de compras
                            '$cod_detallecompra',
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
        
        // 8. DISMINUIR stock del producto (porque estamos devolviendo al proveedor)
        $queryUpdateStock = "UPDATE producto SET stock = stock - $cantidad WHERE cod_producto = '$cod_producto'";
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
        
        $cod_tipomovimiento = obtenerCodigoTipoMovimientoDevolucionCompra($conexion);
        $total_inventario = $precio_compra_unidad * $cantidad;
        
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
                            $precio_compra_unidad,
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
            'message' => "✅ NOTA DE CRÉDITO CREADA EXITOSAMENTE\n\n📋 Código: $cod_notacredito\n📦 Producto: $producto_nombre\n🔢 Cantidad: $cantidad unidades\n💰 Monto: S/ " . number_format($monto_devolucion, 2) . "\n📈 Stock actualizado: -$cantidad unidades\n📊 Registro en inventario: $cod_inventario",
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

// API para obtener detalles de compra
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'obtener_detalles_compra') {
    
    // LIMPIAR BUFFER ANTES DE PROCESAR
    if (ob_get_length()) ob_clean();
    
    $cod_compra = pg_escape_string($conexion, $_POST['cod_compra']);
    $detalles = obtenerDetallesCompra($conexion, $cod_compra);
    
    // Obtener notas de crédito existentes si hay compra
    if($detalles['compra']) {
        $notas_credito = obtenerNotasCreditoCompra($conexion, $detalles['compra']['cod_compra']);
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

// OBTENER SOLO LAS COMPRAS REALES QUE EXISTEN EN LA BSD
$queryComprobantes = "SELECT 
                        c.cod_compra,
                        c.cod_tipodocumento,
                        c.fecha_compra,
                        p.razon_social as proveedor_nombre,
                        p.ruc,
                        c.cod_metodopago,
                        td.nombre as documento_nombre,
                        td.serie,
                        td.numero,
                        mp.nombre as metodo_pago,
                        (SELECT SUM(dc.total) 
                         FROM detallecompra dc 
                         WHERE dc.cod_compra = c.cod_compra) as total_compra,
                        (SELECT COUNT(*) 
                         FROM detallecompra dc 
                         WHERE dc.cod_compra = c.cod_compra) as total_productos
                      FROM compra c
                      LEFT JOIN proveedor p ON c.cod_proveedor = p.cod_proveedor
                      LEFT JOIN tipodocumento td ON c.cod_tipodocumento = td.cod_tipodocumento
                      LEFT JOIN metodopago mp ON c.cod_metodopago = mp.cod_metodopago
                      WHERE EXISTS (
                          SELECT 1 FROM detallecompra dc WHERE dc.cod_compra = c.cod_compra
                      )
                      ORDER BY c.fecha_compra DESC, c.cod_compra DESC";

$resultComprobantes = pg_query($conexion, $queryComprobantes);
$comprobantes = [];
if($resultComprobantes) {
    $comprobantes = pg_fetch_all($resultComprobantes) ?: [];
}

error_log("COMPRAS ENCONTRADAS: " . count($comprobantes));

// PROCESAR BÚSQUEDA MEJORADA
$resultadosBusqueda = [];
if(isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $termino = pg_escape_string($conexion, $_GET['buscar']);
    $queryBusqueda = "SELECT 
                        c.cod_compra,
                        c.cod_tipodocumento,
                        c.fecha_compra,
                        p.razon_social as proveedor_nombre,
                        p.ruc,
                        c.cod_metodopago,
                        td.nombre as documento_nombre,
                        td.serie,
                        td.numero,
                        mp.nombre as metodo_pago,
                        (SELECT SUM(dc.total) 
                         FROM detallecompra dc 
                         WHERE dc.cod_compra = c.cod_compra) as total_compra,
                        (SELECT COUNT(*) 
                         FROM detallecompra dc 
                         WHERE dc.cod_compra = c.cod_compra) as total_productos
                      FROM compra c
                      LEFT JOIN proveedor p ON c.cod_proveedor = p.cod_proveedor
                      LEFT JOIN tipodocumento td ON c.cod_tipodocumento = td.cod_tipodocumento
                      LEFT JOIN metodopago mp ON c.cod_metodopago = mp.cod_metodopago
                      WHERE EXISTS (
                          SELECT 1 FROM detallecompra dc WHERE dc.cod_compra = c.cod_compra
                      )
                      AND (c.cod_compra ILIKE '%$termino%' 
                         OR p.razon_social ILIKE '%$termino%'
                         OR p.ruc ILIKE '%$termino%'
                         OR td.nombre ILIKE '%$termino%'
                         OR td.serie ILIKE '%$termino%'
                         OR CAST(td.numero AS TEXT) ILIKE '%$termino%'
                         OR c.cod_tipodocumento ILIKE '%$termino%')
                      ORDER BY c.fecha_compra DESC, c.cod_compra DESC";
    
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
    <title>Mad Market - Devoluciones de Compra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/almacen-estilo.css">
    <link rel="stylesheet" href="css/almacen-boton/boton.css">
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

        .buscar-compra, .info-compra {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }

        .busqueda-compra {
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

        .resumen-compra {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .proveedor-info {
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

        /* Estilos para el dropdown de usuario */
        .dropdown-container {
            position: relative;
        }

        .dropdown-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            transition: transform 0.3s ease;
        }

        .dropdown-list {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            min-width: 180px;
            z-index: 1000;
            margin-top: 5px;
        }

        .dropdown-list a {
            display: block;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .dropdown-list a:hover {
            background-color: #f8f9fa;
        }

        .arrow {
            transition: transform 0.3s ease;
            display: inline-block;
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
                    <small id="userRole">Almacén</small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.html" class="nav-link active"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="gestionproductos.html" class="nav-link"><ul><i class="fas fa-boxes"></i>Gestión de Productos</ul></a>
                    <a href="almacenproveedores.html" class="nav-link"><ul><i class="fas fa-truck"></i>Proveedores</ul></a>
                    <a href="entradaproveedor.php" class="nav-link"><ul><i class="fas fa-truck-loading"></i>Entradas Proveedor</ul></a>
                    <a href="registrodevolucioncompra.php" class="nav-link"><ul><i class="fas fa-undo-alt"></i>Devoluciones</ul></a>
                    <a href="notificaciones.html" class="nav-link"><ul><i class="fas fa-bell"></i>Notificaciones</ul></a>
                    <a href="reportes.html" class="nav-link"><ul><i class="fas fa-chart-bar"></i>Reportes</ul></a>
                </div>
            </div>
        </main>

        <div class="secundario">
            <div class="header">
                <div class="usuario-info">
                    <div class="usuario-avatar" id="usuarioAvatar"><?php echo htmlspecialchars($inicialNombre.$inicialApellido)?></div>
                    <div>
                        <div class="fw-bold fs-5" id="userName"><?php echo htmlspecialchars($usuarioencargado." ".$apellidoencargado) ?></div>
                        <small class="text-muted" id="userPosition">Almacén</small>
                    </div>
                    <div class="dropdown-container">
                        <div class="dropdown">
                            <button class="dropdown-btn" id="dropdownBtn">
                                <span class="arrow" id="arrow">▲</span>
                            </button>
                            <ul class="dropdown-list" id="dropdownList">
                                <a href="../login.html" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <main class="devoluciones-main">
                
                <section class="buscar-compra">
                    <h3 class="mb-4"><i class="fas fa-undo-alt"></i> Devoluciones de Compra</h3>
                    <p class="text-muted mb-4">Busca y selecciona una compra para procesar devoluciones de productos al proveedor</p>
                    
                    <!-- Debug info -->
                    <div class="debug-info">
                        <strong>DEBUG:</strong> Mostrando <?php echo count($comprobantes); ?> compras encontradas en la base de datos
                    </div>
                    
                    <div class="busqueda-compra">
                        <form method="GET" action="">
                            <div class="busqueda-input">
                                <input type="text" name="buscar" id="inputBusquedaCompra" 
                                       placeholder="🔍 Buscar por código de compra, RUC proveedor, razón social, serie o número..." 
                                       value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>" 
                                       autofocus>
                                <button type="submit" id="btnBuscarCompra" class="btn btn-primary btn-lg">
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
                                        <?php echo isset($_GET['buscar']) ? 'Resultados de búsqueda' : 'Compras Registradas'; ?>
                                        <span class="badge bg-primary ms-2"><?php echo count($documentosMostrar); ?></span>
                                    </h6>
                                </div>
                                <?php foreach($documentosMostrar as $documento): ?>
                                    <div class="resultado-item" onclick="mostrarDetallesCompra('<?php echo $documento['cod_compra']; ?>')">
                                        <div class="documento-info">
                                            <div class="documento-datos">
                                                <strong class="fs-5">
                                                    <?php echo $documento['documento_nombre'] ?: 'Sin documento'; ?>
                                                    <?php if($documento['total_compra']): ?>
                                                        <span class="total-badge">S/ <?php echo number_format($documento['total_compra'], 2); ?></span>
                                                    <?php endif; ?>
                                                </strong>
                                                <div class="mt-2">
                                                    <span class="badge bg-secondary">Compra: <?php echo $documento['cod_compra']; ?></span>
                                                    <?php if($documento['serie']): ?>
                                                        <span class="badge bg-info">Serie: <?php echo $documento['serie']; ?>-<?php echo $documento['numero']; ?></span>
                                                    <?php endif; ?>
                                                    <span class="badge bg-warning">Productos: <?php echo $documento['total_productos']; ?></span>
                                                </div>
                                                <div class="mt-1">
                                                    <small><strong>Proveedor:</strong> <?php echo $documento['proveedor_nombre']; ?> | <strong>RUC:</strong> <?php echo $documento['ruc']; ?></small>
                                                </div>
                                                <div class="mt-1">
                                                    <small><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($documento['fecha_compra'])); ?> 
                                                    <?php if($documento['metodo_pago']): ?>
                                                        | <strong>Método:</strong> <?php echo $documento['metodo_pago']; ?>
                                                    <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="documento-numero">
                                                <i class="fas fa-file-invoice fa-lg"></i>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-file-invoice"></i>
                                    <h5>No hay compras registradas</h5>
                                    <p class="mb-3">No se encontraron compras con productos en el sistema.</p>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Información:</strong> Las compras aparecerán aquí después de realizar compras en el sistema.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="info-compra" id="seccionInfoCompra" style="display: none;">
                    <h3 class="mb-4"><i class="fas fa-file-invoice"></i> Detalles de la Compra</h3>
                    <div class="compra-detalle" id="detalleCompra">
                        <!-- Información aparecerá aquí via JavaScript -->
                    </div>
                </section>

            </main>
        </div>
    </div>

    <!-- Modal para registrar devolución de compra -->
    <div class="modal fade" id="modalDevolucionCompra" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-devolucion">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-undo-alt"></i> Registrar Devolución de Compra</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formDevolucionCompra">
                        <input type="hidden" id="cod_detallecompra_devolucion" name="cod_detallecompra">
                        <input type="hidden" id="cod_compra_devolucion" name="cod_compra">
                        
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
                                    <label class="form-label fw-bold">Precio compra unitario:</label>
                                    <input type="text" id="precio_compra_unitario_devolucion" class="form-control" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Cantidad a devolver al proveedor: <span id="monto_calculado" class="text-success"></span></label>
                            <input type="number" id="cantidad_devolucion" name="cantidad" class="form-control" min="1" value="1" required onchange="calcularMontoCompra()">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Motivo de la devolución:</label>
                            <textarea id="motivo_devolucion" name="motivo" class="form-control" rows="3" placeholder="Describe el motivo de la devolución (ej: producto defectuoso, exceso de stock, etc.)..." required></textarea>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Importante:</strong> Al confirmar, se creará una nota de crédito, se disminuirá el stock del producto (por devolución al proveedor) y se registrará en el inventario automáticamente.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" onclick="confirmarDevolucionCompra()">
                        <i class="fas fa-check-circle"></i> Crear Nota de Crédito
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../controlador/almacen/usuario.js"></script>
    <script src="../../controlador/almacen/dropdown.js"></script>
    <script src="../../controlador/almacen/barralateral.js"></script>
    <script>
        // FUNCIÓN PARA MOSTRAR DETALLES DE COMPRA
        async function mostrarDetallesCompra(cod_compra) {
            console.log('Cargando detalles para compra:', cod_compra);
            
            document.getElementById('seccionInfoCompra').style.display = 'block';
            document.getElementById('detalleCompra').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2">Cargando detalles de la compra...</p>
                    <small class="text-muted">Compra: ${cod_compra}</small>
                </div>
            `;

            try {
                const formData = new FormData();
                formData.append('accion', 'obtener_detalles_compra');
                formData.append('cod_compra', cod_compra);
                
                const response = await fetch('registrodevolucioncompra.php', {
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
                
                if (!data.compra) {
                    document.getElementById('detalleCompra').innerHTML = `
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <h5>No se encontró la compra</h5>
                            <p class="mb-0">La compra ${cod_compra} no existe o no tiene productos.</p>
                        </div>
                    `;
                    return;
                }

                const compra = data.compra;
                const detalles = data.detalles;
                const notasCredito = data.notas_credito || [];
                
                let productosHTML = '';
                let totalCompra = 0;
                
                if (detalles.length === 0) {
                    productosHTML = `
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-box-open fa-2x mb-3"></i>
                            <h5>No hay productos en esta compra</h5>
                            <p class="mb-0">La compra no contiene productos para devolver.</p>
                        </div>
                    `;
                } else {
                    // GENERAR TARJETAS DE PRODUCTOS
                    detalles.forEach((detalle, index) => {
                        const tieneNotaCredito = notasCredito.some(nota => nota.cod_detallecompra === detalle.cod_detallecompra);
                        const subtotal = parseFloat(detalle.total);
                        totalCompra += subtotal;
                        
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
                                        <span class="info-label">Precio Compra Unitario:</span>
                                        <span class="info-value">S/ ${parseFloat(detalle.precio_compra_unidad).toFixed(2)}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Cantidad Comprada:</span>
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
                                        <span class="info-value text-muted">${detalle.cod_detallecompra}</span>
                                    </div>
                                </div>
                                
                                <button class="btn-devolucion" 
                                        onclick="abrirModalDevolucionCompra(
                                            '${detalle.cod_detallecompra}',
                                            '${cod_compra}',
                                            '${detalle.producto_nombre}',
                                            '${detalle.cod_producto}',
                                            ${detalle.cantidad_unidades},
                                            ${detalle.precio_compra_unidad}
                                        )"
                                        ${tieneNotaCredito ? 'disabled' : ''}>
                                    <i class="fas fa-undo-alt"></i> 
                                    ${tieneNotaCredito ? 'Ya Devuelto' : 'Devolver al Proveedor'}
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

                document.getElementById('detalleCompra').innerHTML = `
                    <div class="proveedor-info">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-truck"></i> Información del Proveedor</h5>
                                <p><strong>Razón Social:</strong> ${compra.proveedor_nombre}</p>
                                <p><strong>RUC:</strong> ${compra.ruc}</p>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-receipt"></i> Información del Comprobante</h5>
                                <p><strong>Documento:</strong> ${compra.documento_nombre || 'No especificado'}</p>
                                ${compra.serie ? `<p><strong>Serie:</strong> ${compra.serie} - <strong>Número:</strong> ${compra.numero}</p>` : ''}
                                <p><strong>Método de Pago:</strong> ${compra.metodo_pago || 'No especificado'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="resumen-compra">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h6>Código Compra</h6>
                                <h4>${compra.cod_compra}</h4>
                            </div>
                            <div class="col-md-3">
                                <h6>Fecha de Compra</h6>
                                <h4>${new Date(compra.fecha_compra).toLocaleDateString('es-PE')}</h4>
                            </div>
                            <div class="col-md-3">
                                <h6>Total Compra</h6>
                                <h4>S/ ${parseFloat(compra.total_compra || totalCompra).toFixed(2)}</h4>
                            </div>
                            <div class="col-md-3">
                                <h6>Productos</h6>
                                <h4>${detalles.length}</h4>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3"><i class="fas fa-boxes"></i> Productos de la Compra</h5>
                    <div class="productos-grid">
                        ${productosHTML}
                    </div>
                    
                    ${notasHTML}
                `;

            } catch (error) {
                console.error('Error al cargar detalles:', error);
                document.getElementById('detalleCompra').innerHTML = `
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-times-circle fa-2x mb-3"></i>
                        <h5>Error al cargar los detalles de la compra</h5>
                        <p class="mb-0">${error.message}</p>
                        <small class="mt-2">Revisa la consola para más detalles</small>
                    </div>
                `;
            }
        }

        // FUNCIÓN PARA ABRIR MODAL DE DEVOLUCIÓN DE COMPRA
        function abrirModalDevolucionCompra(cod_detallecompra, cod_compra, producto_nombre, cod_producto, cantidad_maxima, precio_compra_unitario) {
            document.getElementById('cod_detallecompra_devolucion').value = cod_detallecompra;
            document.getElementById('cod_compra_devolucion').value = cod_compra;
            document.getElementById('producto_nombre_devolucion').value = producto_nombre;
            document.getElementById('cod_producto_devolucion').value = cod_producto;
            document.getElementById('cantidad_maxima').value = cantidad_maxima + ' unidades';
            document.getElementById('precio_compra_unitario_devolucion').value = 'S/ ' + parseFloat(precio_compra_unitario).toFixed(2);
            document.getElementById('cantidad_devolucion').setAttribute('max', cantidad_maxima);
            document.getElementById('cantidad_devolucion').value = 1;
            document.getElementById('motivo_devolucion').value = '';
            
            calcularMontoCompra();
            
            const modal = new bootstrap.Modal(document.getElementById('modalDevolucionCompra'));
            modal.show();
        }

        // FUNCIÓN PARA CALCULAR MONTO DE DEVOLUCIÓN DE COMPRA
        function calcularMontoCompra() {
            const cantidad = document.getElementById('cantidad_devolucion').value;
            const precio = parseFloat(document.getElementById('precio_compra_unitario_devolucion').value.replace('S/ ', ''));
            const monto = cantidad * precio;
            document.getElementById('monto_calculado').textContent = '(Monto: S/ ' + monto.toFixed(2) + ')';
        }

        // FUNCIÓN PARA CONFIRMAR DEVOLUCIÓN DE COMPRA
        async function confirmarDevolucionCompra() {
            const cod_detallecompra = document.getElementById('cod_detallecompra_devolucion').value;
            const cod_compra = document.getElementById('cod_compra_devolucion').value;
            const cantidad = document.getElementById('cantidad_devolucion').value;
            const motivo = document.getElementById('motivo_devolucion').value;
            const producto_nombre = document.getElementById('producto_nombre_devolucion').value;
            
            console.log("Datos a enviar:", {
                cod_detallecompra,
                cod_compra, 
                cantidad,
                motivo,
                producto_nombre
            });
            
            if(!motivo.trim()) {
                Swal.fire({
                    icon: "warning",
                    title: "Falta motivo",
                    text: "❌ Por favor, describe el motivo de la devolución",
                    width: "350px",
                });
                return;
            }
            
            if(cantidad < 1) {
                Swal.fire({
                    icon: "warning",
                    title: "Falta producto",
                    text: "❌ La cantidad debe ser al menos 1",
                    width: "350px",
                });
                return;
            }
            
            if(!confirm(`¿Confirmar devolución al proveedor?\n\n📦 Producto: ${producto_nombre}\n🔢 Cantidad: ${cantidad} unidades\n💰 Monto aproximado: S/ ${(cantidad * parseFloat(document.getElementById('precio_compra_unitario_devolucion').value.replace('S/ ', ''))).toFixed(2)}\n\n⚠️ ATENCIÓN: Esta acción DISMINUIRÁ el stock del producto y creará una nota de crédito.`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('accion', 'crear_nota_credito_compra');
                formData.append('cod_detallecompra', cod_detallecompra);
                formData.append('cantidad', cantidad);
                formData.append('motivo', motivo);
                formData.append('cod_compra', cod_compra);
                
                console.log("Enviando petición...");
                const response = await fetch('registrodevolucioncompra.php', {
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
                    Swal.fire({
                    icon: "success",
                    title: "Éxito",
                    text: resultado.message,
                    width: "350px",
                });
                    
                    // Cerrar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalDevolucionCompra'));
                    modal.hide();
                    
                    // Recargar detalles para actualizar la vista
                    mostrarDetallesCompra(cod_compra);
                    
                } else {
                    Swal.fire({
                        title: "Error",
                        text: '❌ ' + error.message,
                        icon: "warning",
                    })
                }
                
            } catch (error) {
                console.error('Error en fetch:', error);
                Swal.fire({
                    title: "Error",
                    text: '❌ Error de conexión: ' + error.message,
                    icon: "warning",
                })
            }
        }
    </script>
</body>
</html>