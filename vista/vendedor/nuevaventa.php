<?php date_default_timezone_set('America/Lima'); ?>
<?php
// CONEXIÓN A LA BASE DE DATOS
$conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");

if(!$conexion){
    echo "Un error de conexión ocurrió. <br>";
    exit;
}

session_start();
$usuariovendedor = $_SESSION['nombreusuariovendedor'] ?? '';
$apellidovendedor = $_SESSION['apellidousuariovendedor'] ?? '';
$cod_usuario_session = $_SESSION['usuario'] ?? 'USU001';

$inicialNombre = substr($usuariovendedor, 0, 1);
$inicialApellido = substr($apellidovendedor, 0, 1);

// TOKEN DE API
$token_sunat = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJlbWFpbCI6ImRhY3EzMjFAZ21haWwuY29tIn0.XJHzriTxqk7AP-EGj7E2_srtYlbhd1e0X65tQznx3qY';

// FUNCIÓN MEJORADA PARA CONSULTAR RUC
function consultarRUC($ruc, $token) {
    $url = "https://dniruc.apisperu.com/api/v1/ruc/{$ruc}?token={$token}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200 && $response) {
        $data = json_decode($response, true);
        
        if(isset($data['ruc'])) {
            return procesarDatosRUC($data);
        } else {
            return [
                'success' => false,
                'error' => 'RUC no encontrado'
            ];
        }
    } else {
        return [
            'success' => false,
            'error' => 'Error en la conexión con SUNAT'
        ];
    }
}

// FUNCIÓN MEJORADA PARA PROCESAR DATOS RUC
function procesarDatosRUC($datos) {
    // Determinar tipo de contribuyente
    $primerosDigitos = substr($datos['ruc'], 0, 2);
    $tipo_contribuyente = in_array($primerosDigitos, ['10', '15', '17', '20']) ? 'persona_natural' : 'empresa';
    
    // Procesar datos según el tipo
    $razon_social = $datos['razonSocial'] ?? '';
    $nombre_comercial = $datos['nombreComercial'] ?? '';
    $direccion = $datos['direccion'] ?? '';
    $estado = $datos['estado'] ?? '';
    $condicion = $datos['condicion'] ?? '';
    
    // MEJORA: Para personas naturales, usar nombre comercial si existe, sino razón social
    if ($tipo_contribuyente === 'persona_natural') {
        // Si es persona natural y tiene nombre comercial, usarlo como "nombre"
        if (!empty($nombre_comercial) && $nombre_comercial !== '-') {
            $razon_social_mostrar = $nombre_comercial;
        } else {
            $razon_social_mostrar = $razon_social;
        }
    } else {
        // Para empresas, usar razón social
        $razon_social_mostrar = $razon_social;
    }
    
    // MEJORA: Manejo de dirección - si está vacía o es inválida, no mostrar nada
    $direccion = trim($direccion);
    if(empty($direccion) || $direccion === '-' || $direccion === 'NULL' || $direccion === 'DIRECCIÓN NO ENCONTRADA') {
        $direccion = ''; // Ahora queda vacío en lugar de mostrar "DIRECCIÓN NO ENCONTRADA"
    }
    
    // MEJORA: Limpiar nombre comercial - solo mostrar si es diferente a razón social y no está vacío
    $nombre_comercial = trim($nombre_comercial);
    if(empty($nombre_comercial) || $nombre_comercial === '-' || $nombre_comercial === $razon_social) {
        $nombre_comercial = '';
    }
    
    // Determinar el texto para mostrar según el tipo
    if ($tipo_contribuyente === 'persona_natural') {
        $tipo_texto = "RUC encontrado:";
    } else {
        $tipo_texto = "RUC encontrado:";
    }
    
    // Validar estado del contribuyente
    $estado_alert = '';
    if (strtoupper($estado) !== 'ACTIVO') {
        $estado_alert = "⚠️ <strong>ALERTA:</strong> El contribuyente se encuentra con estado: <strong>{$estado}</strong>. Los comprobantes de pago o notas de débito emitidos por este contribuyente no dan derecho a crédito fiscal del IGV.";
    }
    
    return [
        'success' => true,
        'tipo' => $tipo_contribuyente,
        'razon_social' => trim($razon_social),
        'razon_social_mostrar' => trim($razon_social_mostrar),
        'nombre_comercial' => $nombre_comercial,
        'direccion' => $direccion,
        'estado' => trim($estado),
        'condicion' => trim($condicion),
        'tipo_texto' => $tipo_texto,
        'estado_alert' => $estado_alert
    ];
}

// PROCESAR CONSULTA RUC MEJORADA
if(isset($_POST['consultar_ruc'])) {
    $ruc = $_POST['ruc_consulta'] ?? '';
    
    if(!empty($ruc) && strlen($ruc) === 11) {
        $consulta = consultarRUC($ruc, $token_sunat);
        
        if($consulta['success']) {
            echo json_encode([
                'success' => true,
                'tipo' => $consulta['tipo'],
                'razon_social' => $consulta['razon_social'],
                'razon_social_mostrar' => $consulta['razon_social_mostrar'],
                'nombre_comercial' => $consulta['nombre_comercial'],
                'direccion' => $consulta['direccion'],
                'estado' => $consulta['estado'],
                'condicion' => $consulta['condicion'],
                'tipo_texto' => $consulta['tipo_texto'],
                'estado_alert' => $consulta['estado_alert']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'RUC no encontrado'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'RUC debe tener 11 dígitos'
        ]);
    }
    exit;
}

// FUNCIONES PARA GENERAR CÓDIGOS ÚNICOS EN ORDEN
function obtenerSiguienteCodigo($conexion, $tabla, $prefijo) {
    $configuraciones = [
        'venta' => ['columna' => 'cod_venta', 'formato' => 'V'],
        'detalleventa' => ['columna' => 'cod_detalleventa', 'formato' => 'DV'],
        'registroinventario' => ['columna' => 'cod_inventario', 'formato' => 'INV'],
        'movimiento' => ['columna' => 'cod_movimiento', 'formato' => 'MOV'],
        'historialproductos' => ['columna' => 'cod_historialproductos', 'formato' => 'HIS'],
        'reporte' => ['columna' => 'cod_reporte', 'formato' => 'REP']
    ];

    $config = $configuraciones[$tabla] ?? ['columna' => "cod_$tabla", 'formato' => $prefijo];
    $columna = $config['columna'];
    $formato_prefijo = $config['formato'];

    // Ordenar numéricamente
    $query = "
        SELECT $columna 
        FROM $tabla 
        WHERE $columna LIKE '{$formato_prefijo}%'
        ORDER BY CAST(SUBSTRING($columna FROM '[0-9]+$') AS INTEGER) DESC
        LIMIT 1
    ";

    $result = pg_query($conexion, $query);
    if(!$result){
        throw new Exception("Error en la consulta: " . pg_last_error($conexion));
    }

    if(pg_num_rows($result) > 0) {
        $ultimo_cod = pg_fetch_assoc($result)[$columna];
        preg_match('/\d+$/', $ultimo_cod, $matches);
        $nuevo_numero = intval($matches[0]) + 1;
    } else {
        $nuevo_numero = 1;
    }

    // Ceros a la izquierda
    return sprintf("%s%03d", $formato_prefijo, $nuevo_numero);
}

// VERIFICAR Y CREAR DATOS MAESTROS SI NO EXISTEN
function verificarDatosMaestros($conexion) {
    // Verificar tipos de acción
    $checkAccion = pg_query($conexion, "SELECT COUNT(*) as count FROM tipoaccion WHERE cod_tipoaccion = 'TA001'");
    if($checkAccion && pg_fetch_result($checkAccion, 0) == 0) {
        pg_query($conexion, "INSERT INTO tipoaccion (cod_tipoaccion, nombre) VALUES 
                            ('TA001', 'Venta'), ('TA002', 'Modificación'), ('TA003', 'Eliminación')");
    }
    
    // Verificar tipos de movimiento
    $checkMovimiento = pg_query($conexion, "SELECT COUNT(*) as count FROM tipomovimiento WHERE cod_tipomovimiento = 'TM001'");
    if($checkMovimiento && pg_fetch_result($checkMovimiento, 0) == 0) {
        pg_query($conexion, "INSERT INTO tipomovimiento (cod_tipomovimiento, nombre) VALUES 
                            ('TM001', 'Entrada'), ('TM002', 'Salida'), ('TM003', 'Ajuste')");
    }
    
    // Verificar tipos de reporte
    $checkReporte = pg_query($conexion, "SELECT COUNT(*) as count FROM tiporeporte WHERE cod_tiporeporte = 'TR001'");
    if($checkReporte && pg_fetch_result($checkReporte, 0) == 0) {
        pg_query($conexion, "INSERT INTO tiporeporte (cod_tiporeporte, nombre) VALUES 
                            ('TR001', 'Reporte Ventas'), ('TR002', 'Reporte Inventario')");
    }
    
    // Verificar métodos de pago
    $checkMetodos = pg_query($conexion, "SELECT COUNT(*) as count FROM metodopago WHERE cod_metodopago = 'MP001'");
    if($checkMetodos && pg_fetch_result($checkMetodos, 0) == 0) {
        pg_query($conexion, "INSERT INTO metodopago (cod_metodopago, nombre) VALUES 
                            ('MP001', 'Efectivo'), ('MP002', 'Tarjeta Débito'), 
                            ('MP003', 'Tarjeta Crédito'), ('MP004', 'Transferencia'),
                            ('MP005', 'Yape'), ('MP006', 'Plin')");
    }
    
    // Verificar tipos de documento
    $checkDocumento = pg_query($conexion, "SELECT COUNT(*) as count FROM tipodocumento WHERE cod_tipodocumento = 'TD001'");
    if($checkDocumento && pg_fetch_result($checkDocumento, 0) == 0) {
        pg_query($conexion, "INSERT INTO tipodocumento (cod_tipodocumento, nombre, serie, numero) VALUES 
                            ('TD001', 'Boleta', 'B001', 1), 
                            ('TD002', 'Factura', 'F001', 1)");
    }
}

verificarDatosMaestros($conexion);

// PROCESAR VENTA CUANDO SE ENVÍA EL FORMULARIO
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_venta'])) {
    
    // GENERAR CÓDIGOS ÚNICOS EN ORDEN - SOLO UNA VEZ POR TABLA
    $cod_venta = obtenerSiguienteCodigo($conexion, 'venta', 'V');
    $cod_reporte = obtenerSiguienteCodigo($conexion, 'reporte', 'REP');
    
    // DATOS DE LA VENTA
    $cod_usuario = $cod_usuario_session;
    $cod_metodopago = $_POST['cod_metodopago'];
    $total = $_POST['total'];
    $tipo_documento = $_POST['tipo_documento'];
    $productos = json_decode($_POST['productos_json'], true);
    
    // DATOS DEL CLIENTE - MANEJAR TANTO BOLETA COMO FACTURA
    $dni_cliente = $_POST['dni_cliente'] ?? '';
    $nombre_cliente = $_POST['nombre_cliente'] ?? '';
    $email_cliente = $_POST['email_cliente'] ?? $_POST['email_cliente_factura'] ?? '';
    $ruc_cliente = $_POST['ruc_cliente'] ?? '';
    $razon_social_cliente = $_POST['razon_social_cliente'] ?? '';
    $direccion_cliente = $_POST['direccion_cliente'] ?? '';
    
    // VALIDAR DATOS DEL CLIENTE SEGÚN TIPO DE DOCUMENTO
    $error_venta = '';
    
    if($tipo_documento === 'factura') {
        if(empty($ruc_cliente) || strlen($ruc_cliente) !== 11) {
            $error_venta = "Para factura debe ingresar un RUC válido de 11 dígitos";
        } elseif(empty($razon_social_cliente)) {
            $error_venta = "Para factura debe ingresar la Razón Social del cliente";
        }
        // LA DIRECCIÓN AHORA ES OPCIONAL
    } else {
        // Para boleta, validar DNI si se proporciona
        if(!empty($dni_cliente) && strlen($dni_cliente) !== 8) {
            $error_venta = "El DNI debe tener 8 dígitos";
        }
        if(empty($nombre_cliente)) {
            $error_venta = "Para boleta debe ingresar el nombre del cliente";
        }
    }
    
    if(empty($error_venta)) {
        // Configurar datos del comprobante
        if($tipo_documento === 'factura') {
            $cod_tipodocumento = 'TD002'; // Factura
            $nombre_documento = 'Factura';
            $nombre_mostrar = $razon_social_cliente;
            $documento_cliente = $ruc_cliente;
        } else {
            $cod_tipodocumento = 'TD001'; // Boleta
            $nombre_documento = 'Boleta';
            $nombre_mostrar = $nombre_cliente;
            $documento_cliente = $dni_cliente;
        }
        
        // Obtener serie y número actual del documento
        $queryDocumento = "SELECT serie, numero FROM tipodocumento WHERE cod_tipodocumento = '$cod_tipodocumento'";
        $resultDocumento = pg_query($conexion, $queryDocumento);
        $documento = pg_fetch_assoc($resultDocumento);
        $serie = $documento['serie'];
        $numero = $documento['numero'];
        $fecha_salida = date('Y-m-d H:i:s');
        
        // Iniciar transacción
        pg_query($conexion, "BEGIN");
        
        try {
            // 1. ACTUALIZAR NÚMERO DEL DOCUMENTO
            $nuevo_numero = $numero + 1;
            $queryUpdateDocumento = "UPDATE tipodocumento SET numero = $nuevo_numero WHERE cod_tipodocumento = '$cod_tipodocumento'";
            if(!pg_query($conexion, $queryUpdateDocumento)) {
                throw new Exception("Error al actualizar número de documento: " . pg_last_error($conexion));
            }
            
            // 2. GUARDAR VENTA PRINCIPAL CON DATOS COMPLETOS DEL CLIENTE
            $queryVenta = "INSERT INTO venta (cod_venta, cod_usuario, dni, nombre, cod_tipodocumento, email, cod_metodopago, cod_tiporeporte, fecha_venta) 
                           VALUES ('$cod_venta', '$cod_usuario', '$documento_cliente', '$nombre_mostrar', '$cod_tipodocumento', '$email_cliente', '$cod_metodopago', 'TR001', '$fecha_salida')";
            $resultVenta = pg_query($conexion, $queryVenta);
            
            if(!$resultVenta) {
                throw new Exception("Error al guardar venta: " . pg_last_error($conexion));
            }
            
            // 3. GUARDAR PRODUCTOS EN DETALLEVENTA Y ACTUALIZAR STOCK
            foreach($productos as $index => $producto) {
                // Generar código único para detalleventa - UNO POR PRODUCTO
                $cod_detalleventa = obtenerSiguienteCodigo($conexion, 'detalleventa', 'DV');
                
                // Guardar en detalleventa
                $queryDetalle = "INSERT INTO detalleventa (cod_detalleventa, cod_venta, cod_producto, cantidad_unidades, precio_unitario, total) 
                                 VALUES ('$cod_detalleventa', '$cod_venta', '{$producto['codigo']}', {$producto['cantidad']}, '{$producto['precio']}', '{$producto['total']}')";
                $resultDetalle = pg_query($conexion, $queryDetalle);
                
                if(!$resultDetalle) {
                    throw new Exception("Error al guardar detalle venta: " . pg_last_error($conexion));
                }
                
                // Actualizar stock en producto
                $queryUpdateStock = "UPDATE producto SET stock = stock - {$producto['cantidad']} WHERE cod_producto = '{$producto['codigo']}'";
                $resultUpdate = pg_query($conexion, $queryUpdateStock);
                
                if(!$resultUpdate) {
                    throw new Exception("Error al actualizar stock para {$producto['nombre']}: " . pg_last_error($conexion));
                }
                
                // Registrar en registroinventario - UNO POR PRODUCTO
                $cod_inventario = obtenerSiguienteCodigo($conexion, 'registroinventario', 'INV');
                $query_inventario = "INSERT INTO registroinventario (cod_inventario, cod_usuario, fecha_inventario, cod_producto, cod_tipomovimiento, cantidad, precio_unitario, total) 
                                     VALUES ('$cod_inventario', '$cod_usuario', '$fecha_salida', '{$producto['codigo']}', 'TM002', {$producto['cantidad']}, '{$producto['precio']}', '{$producto['total']}')";
                
                $resultInventario = pg_query($conexion, $query_inventario);
                if(!$resultInventario) {
                    throw new Exception("Error al insertar en registro inventario: " . pg_last_error($conexion));
                }

                // Registrar movimiento de inventario - UNO POR PRODUCTO
                $cod_movimiento = obtenerSiguienteCodigo($conexion, 'movimiento', 'MOV');
                $queryMovimiento = "INSERT INTO movimiento (cod_movimiento, cod_producto, cod_tipomovimiento, fecha_movimiento, cod_usuario, observacion) 
                                    VALUES ('$cod_movimiento', '{$producto['codigo']}', 'TM002', CURRENT_TIMESTAMP, '$cod_usuario', 'Venta - $cod_venta')";
                $resultMovimiento = pg_query($conexion, $queryMovimiento);
                
                // Registrar en historial - UNO POR PRODUCTO
                $cod_historial = obtenerSiguienteCodigo($conexion, 'historialproductos', 'HIS');
                $queryHistorial = "INSERT INTO historialproductos (cod_historialproductos, cod_usuario, cod_producto, cod_tipoaccion, observacion) 
                                   VALUES ('$cod_historial', '$cod_usuario', '{$producto['codigo']}', 'TA001', 'Venta $cod_venta - Cantidad: {$producto['cantidad']}')";
                pg_query($conexion, $queryHistorial);
            }
            
            // 4. GUARDAR REPORTE - SOLO UNO
            $cod_tiporeporte = 'TR001';
            $datos_reporte = "Venta $cod_venta - $nombre_documento $serie-$numero - Cliente: $nombre_mostrar - Total: S/ $total - Método: $cod_metodopago";
            
            $queryReporte = "INSERT INTO reporte (cod_reporte, cod_usuario, fecha_reporte, cod_tiporeporte, cod_tipodocumento, datos_reporte) 
                             VALUES ('$cod_reporte', '$cod_usuario', CURRENT_TIMESTAMP, '$cod_tiporeporte', '$cod_tipodocumento', '$datos_reporte')";
            $resultReporte = pg_query($conexion, $queryReporte);
            
            if(!$resultReporte) {
                throw new Exception("Error al guardar reporte: " . pg_last_error($conexion));
            }
            
            // Confirmar transacción
            pg_query($conexion, "COMMIT");

            // Mostrar mensaje de éxito con detalles del comprobante
            $_SESSION['venta_exitosa'] = true;
            $_SESSION['mensaje_exito'] = "✅ Venta procesada correctamente!<br>
                                         <strong>Comprobante:</strong> $nombre_documento $serie-$numero<br>
                                         <strong>Código Venta:</strong> $cod_venta<br>
                                         <strong>Cliente:</strong> $nombre_mostrar<br>
                                         <strong>Total:</strong> S/ " . number_format($total, 2);
            
            // Redirigir para evitar reenvío del formulario
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            pg_query($conexion, "ROLLBACK");
            $error_venta = "Error al procesar la venta: " . $e->getMessage();
        }
    }
}

// Verificar si hay mensaje de éxito en la sesión
$venta_exitosa = false;
$mensaje_exito = '';
if(isset($_SESSION['venta_exitosa']) && $_SESSION['venta_exitosa']) {
    $venta_exitosa = true;
    $mensaje_exito = $_SESSION['mensaje_exito'];
    // Limpiar la sesión después de mostrar el mensaje
    unset($_SESSION['venta_exitosa']);
    unset($_SESSION['mensaje_exito']);
}

// CONSULTA DE PRODUCTOS
$result1 = pg_query($conexion, "SELECT cod_producto, nombre, precio_venta, stock FROM producto WHERE stock > 0 ORDER BY nombre");
if(!$result1){
    echo "Error al cargar productos: " . pg_last_error($conexion);
    exit;
}

// Obtener métodos de pago
$resultMetodos = pg_query($conexion, "SELECT cod_metodopago, nombre FROM metodopago");
if(!$resultMetodos){
    echo "Error al cargar métodos de pago";
}

// Procesar búsqueda si se envió el formulario
$resultadosBusqueda = [];
if(isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $termino = pg_escape_string($conexion, $_GET['buscar']);
    $queryBusqueda = "SELECT cod_producto, nombre, precio_venta, stock 
                     FROM producto 
                     WHERE (nombre ILIKE '%$termino%' OR cod_producto ILIKE '%$termino%') 
                     AND stock > 0 
                     ORDER BY nombre";
    $resultBusqueda = pg_query($conexion, $queryBusqueda);
    if($resultBusqueda) {
        $resultadosBusqueda = pg_fetch_all($resultBusqueda);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nueva Venta - MAD MARKET</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/vendedor-estilo.css">
  <link rel="stylesheet" href="css/vendedor-boton/boton.css">
<style>
    .documento-botones {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }

    .documento-btn {
        flex: 1;
        padding: 12px;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        background: white;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .documento-btn.active {
        font-weight: bold;
    }

    .documento-btn[data-tipo="boleta"].active {
        border-color: #007bff;
        background-color: #e7f3ff;
        color: #007bff;
    }

    .documento-btn[data-tipo="factura"].active {
        border-color: #28a745;
        background-color: #e8f5e8;
        color: #28a745;
    }

    .documento-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .datos-cliente-section {
        transition: all 0.3s ease;
    }

    .metodos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        margin-bottom: 15px;
    }

    .metodo-btn {
        padding: 12px;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        background: white;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .metodo-btn.active {
        border-color: #007bff;
        background-color: #e7f3ff;
        color: #007bff;
        font-weight: bold;
    }

    .producto-venta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin-bottom: 10px;
        background: white;
        transition: all 0.3s ease;
    }

    .producto-venta:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .producto-info {
        flex: 1;
    }

    .producto-nombre {
        font-weight: 600;
        color: #333;
    }

    .producto-controls {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .cantidad-input {
        width: 80px;
        text-align: center;
        border: 1px solid #ced4da;
        border-radius: 6px;
        padding: 8px 12px;
        font-weight: 500;
        font-size: 1em;
    }

    .cantidad-input:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        outline: none;
    }

    .resultado-item {
        cursor: pointer;
        padding: 10px;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        margin-bottom: 5px;
        transition: background-color 0.2s;
    }

    .resultado-item:hover {
        background-color: #f8f9fa;
    }

    .alert-fixed-top {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        min-width: 400px;
        max-width: 90%;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: none;
        border-radius: 10px;
    }

    .alert-success-custom {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        border-left: 4px solid #28a745;
        color: #155724;
    }

    .contenedor-venta {
        position: relative;
    }

        .contenido-principal {
        transition: all 0.3s ease;
    }
    .panel-venta {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    .venta-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e9ecef;
    }

    .venta-header h3 {
        margin: 0;
        color: #2c3e50;
        font-weight: 600;
    }

    .lista-productos {
        max-height: 400px;
        overflow-y: auto;
        margin-bottom: 20px;
        padding-right: 10px;
    }

    .lista-productos::-webkit-scrollbar {
        width: 6px;
    }

    .lista-productos::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .lista-productos::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 10px;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }

    .empty-state i {
        margin-bottom: 15px;
        opacity: 0.5;
    }

    .resumen-venta {
        background: white;
        padding: 20px;
        border-radius: 10px;
        border: 1px solid #e9ecef;
    }

    .resumen-linea {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f1f1f1;
    }

    .resumen-linea.total {
        border-top: 2px solid #007bff;
        border-bottom: none;
        font-size: 1.2em;
        font-weight: 700;
        color: #2c3e50;
        margin-top: 10px;
        padding-top: 15px;
    }

    .metodos-pago {
        margin: 25px 0;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
        border: 1px solid #e9ecef;
    }

    .metodos-pago h4 {
        color: #2c3e50;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .tipo-documento {
        margin-bottom: 20px;
    }

    .tipo-documento label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 10px;
        display: block;
    }

    .monto-efectivo {
        background: #fff3cd;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #ffeaa7;
        margin-top: 15px;
    }

    .monto-efectivo label {
        font-weight: 600;
        color: #856404;
        display: block;
        margin-bottom: 8px;
    }

    .monto-efectivo input {
        border: 1px solid #ffeaa7;
        background: white;
        padding: 8px 12px;
        border-radius: 6px;
        width: 100%;
    }

    .cambio-info {
        margin-top: 10px;
        padding: 8px;
        background: #d4edda;
        border-radius: 6px;
        text-align: center;
        font-weight: 600;
        color: #155724;
    }

    #btnFinalizar {
        width: 100%;
        padding: 15px;
        font-size: 1.1em;
        font-weight: 600;
        margin-top: 15px;
        transition: all 0.3s ease;
    }

    #btnFinalizar:not(:disabled):hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .producto-precio {
        color: #28a745;
        font-weight: 500;
    }

    .producto-total {
        font-size: 1.1em;
        min-width: 100px;
        text-align: right;
        font-weight: 600;
        color: #2c3e50;
    }

    .btn-quitar {
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 12px;
        transition: all 0.3s ease;
    }

    .btn-quitar:hover {
        background: #c82333;
        transform: scale(1.05);
    }

        .consulta-ruc-section {
        background: #e8f4fd;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #b8daff;
        margin-bottom: 15px;
    }

    .consulta-ruc-input {
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    .ruc-loading {
        display: none;
        text-align: center;
        padding: 10px;
    }

    .ruc-result {
        margin-top: 10px;
        padding: 10px;
        border-radius: 5px;
        display: none;
    }

    .ruc-success {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .ruc-error {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

        .datos-factura-simplificado .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }

    .datos-factura-simplificado .form-control {
        border: 1px solid #ced4da;
        border-radius: 6px;
        padding: 8px 12px;
    }

    .datos-factura-simplificado .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

        .seleccion-documento {
        background: white;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        margin-bottom: 20px;
    }

    .datos-cliente-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        margin-top: 15px;
    }

        #datosBoleta .col-md-6 {
        width: 100% !important;
    }

    #datosBoleta .col-12 {
        width: 100% !important;
    }

        .datos-cliente-card .row {
        margin: 0;
    }

    .datos-cliente-card .col-md-6,
    .datos-cliente-card .col-12 {
        padding-left: 0;
        padding-right: 0;
    }

        .tipo-ruc-indicator {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8em;
        font-weight: 600;
        margin-left: 10px;
    }

    .tipo-empresa {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .tipo-persona {
        background: #e8f4fd;
        color: #004085;
        border: 1px solid #b8daff;
    }

    .info-adicional {
        font-size: 0.85em;
        color: #6c757d;
        margin-top: 5px;
    }

    .campo-opcional {
        color: #6c757d;
        font-weight: normal;
    }
    .fuente-consulta {
        font-size: 0.75em;
        color: #6c757d;
        font-style: italic;
        margin-top: 5px;
    }

    .sin-direccion {
        color: #dc3545;
        font-weight: 500;
    }

    .con-direccion {
        color: #28a745;
        font-weight: 500;
    }
  </style>
</head>
<body>
    <div class="grid">
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
                    <a href="nuevaventa.php" class="nav-link active"><ul><i class="fas fa-cash-register"></i>Nueva Venta</ul></a>
                    <a href="registrodevolucion.php" class="nav-link"><ul><i class="fas fa-undo-alt"></i>Registrar Devolución</ul></a>
                    <a href="boletafactura.html" class="nav-link"><ul><i class="fas fa-receipt"></i>Boletas/Facturas</ul></a>
                    <a href="consultarstock.html" class="nav-link"><ul><i class="fas fa-boxes"></i>Consultar Stock</ul></a>
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
                                <a href="../login.html" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MENSAJE DE ÉXITO FIJO EN LA PARTE SUPERIOR -->
            <?php if($venta_exitosa): ?>
            <div class="alert alert-success alert-fixed-top alert-success-custom alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-2x me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-1">¡Venta Exitosa!</h5>
                        <div class="mb-0"><?php echo $mensaje_exito; ?></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="contenedor-venta contenido-principal">
                <!-- Mostrar error si existe -->
                <?php if(isset($error_venta)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error_venta; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <section class="panel-busqueda">
                    <div class="busqueda-header">
                        <h3><i class="fas fa-search"></i> Buscar Producto</h3>
                    </div>

                    <!-- FORMULARIO DE BÚSQUEDA -->
                    <form method="GET" action="">
                        <div class="busqueda-input">
                            <input type="text" name="buscar" id="inputBusqueda" 
                                   placeholder="Nombre o código del producto..." 
                                   value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>" 
                                   autofocus>
                            <button type="submit" id="btnBuscar" class="btn btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </form>

                    <div class="resultados-busqueda" id="resultadosBusqueda">
                        <?php if(isset($_GET['buscar']) && !empty($_GET['buscar'])): ?>
                            <?php if(!empty($resultadosBusqueda)): ?>
                                <h5>Resultados para: "<?php echo htmlspecialchars($_GET['buscar']); ?>"</h5>
                                <?php foreach($resultadosBusqueda as $producto): ?>
                                    <div class="resultado-item" onclick="agregarProductoDesdeBusqueda('<?php echo $producto['cod_producto']; ?>', '<?php echo addslashes($producto['nombre']); ?>', <?php echo $producto['precio_venta']; ?>, <?php echo $producto['stock']; ?>)">
                                        <div>
                                            <strong><?php echo $producto['nombre']; ?></strong>
                                            <br>
                                            <small>Código: <?php echo $producto['cod_producto']; ?></small>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-success">S/ <?php echo number_format($producto['precio_venta'], 2); ?></strong>
                                            <br>
                                            <span class="badge <?php echo $producto['stock'] > 10 ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo $producto['stock']; ?> unidades
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-search fa-2x mb-2"></i>
                                    <p>No se encontraron productos</p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                
                    <div class="productos-frecuentes">
                        <div class="mb-3">
                            <h4><i class="fas fa-boxes"></i> Productos Disponibles</h4>
                        </div>
                    
                        <div id="gridFrecuentes">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th>Precio Venta</th>
                                            <th>Stock</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaProductos">
                                        <?php
                                        pg_result_seek($result1, 0);
                                        while($row1 = pg_fetch_assoc($result1)){
                                            echo "
                                            <tr>
                                                <td><strong>{$row1['cod_producto']}</strong></td>
                                                <td>{$row1['nombre']}</td>
                                                <td class='text-success'><strong>S/ " . number_format($row1['precio_venta'], 2) . "</strong></td>
                                                <td>
                                                    <span class='badge " . ($row1['stock'] > 10 ? 'bg-success' : 'bg-warning') . "'>
                                                        {$row1['stock']} unidades
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class='btn btn-sm btn-primary' onclick='agregarProducto(\"{$row1['cod_producto']}\", \"{$row1['nombre']}\", {$row1['precio_venta']}, {$row1['stock']})'>
                                                        <i class='fas fa-plus'></i> Agregar
                                                    </button>
                                                </td>
                                            </tr>
                                            ";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- FORMULARIO DE VENTA MEJORADO -->
                <form id="formVenta" method="POST" action="">
                    <input type="hidden" name="finalizar_venta" value="1">
                    <input type="hidden" name="cod_metodopago" id="inputMetodoPago" value="MP001">
                    <input type="hidden" name="tipo_documento" id="inputTipoDocumento" value="boleta">
                    <input type="hidden" name="total" id="inputTotal" value="0">
                    <input type="hidden" name="productos_json" id="inputProductosJson" value="[]">

                    <section class="panel-venta">
                        <div class="venta-header">
                            <h3><i class="fas fa-shopping-cart"></i> Venta Actual</h3>
                            <button type="button" id="btnLimpiar" class="btn btn-secondary">
                                <i class="fas fa-trash"></i> Limpiar Todo
                            </button>
                        </div>

                        <div class="lista-productos" id="listaProductosVenta">
                            <div class="empty-state">
                                <i class="fas fa-shopping-cart fa-3x mb-3 text-muted"></i>
                                <p>No hay productos agregados</p>
                                <small class="text-muted">Busca y agrega productos para comenzar</small>
                            </div>
                        </div>

                        <div class="resumen-venta">
                            <!-- CORRECCIÓN: Mostrar OP. GRAVADA en lugar de Subtotal -->
                            <div class="resumen-linea">
                                <span>OP. GRAVADA:</span>
                                <span id="opGravada">S/ 0.00</span>
                            </div>
                            <div class="resumen-linea">
                                <span>IGV (18%):</span>
                                <span id="igv">S/ 0.00</span>
                            </div>
                            <div class="resumen-linea total">
                                <span>TOTAL:</span>
                                <span id="totalVenta">S/ 0.00</span>
                            </div>

                            <div class="metodos-pago">
                                <h4><i class="fas fa-credit-card"></i> Método de Pago</h4>
                                
                                <div class="metodos-grid">
                                    <?php
                                    if ($resultMetodos && pg_num_rows($resultMetodos) > 0) {
                                        pg_result_seek($resultMetodos, 0);
                                        while($metodo = pg_fetch_assoc($resultMetodos)) {
                                            $active = $metodo['cod_metodopago'] === 'MP001' ? 'active' : '';
                                            $icon = '';
                                            switch($metodo['cod_metodopago']) {
                                                case 'MP001': $icon = 'fa-money-bill-wave'; break;
                                                case 'MP002': $icon = 'fa-credit-card'; break;
                                                case 'MP003': $icon = 'fa-credit-card'; break;
                                                case 'MP004': $icon = 'fa-exchange-alt'; break;
                                                case 'MP005': $icon = 'fa-mobile-alt'; break;
                                                case 'MP006': $icon = 'fa-mobile-alt'; break;
                                                default: $icon = 'fa-money-bill-wave';
                                            }
                                            echo "
                                            <button type='button' class='metodo-btn $active' data-metodo='{$metodo['cod_metodopago']}'>
                                                <i class='fas $icon'></i> {$metodo['nombre']}
                                            </button>
                                            ";
                                        }
                                    }
                                    ?>
                                </div>

                                <!-- SECCIÓN DE TIPO DE DOCUMENTO INTEGRADA -->
                                <div class="seleccion-documento">
                                    <h5><i class="fas fa-file-invoice"></i> Tipo de Comprobante</h5>
                                    <div class="documento-botones">
                                        <button type="button" class="btn documento-btn active" data-tipo="boleta">
                                            <i class="fas fa-receipt"></i> Boleta
                                        </button>
                                        <button type="button" class="btn documento-btn" data-tipo="factura">
                                            <i class="fas fa-file-invoice-dollar"></i> Factura
                                        </button>
                                    </div>
                                </div>

                                <!-- DATOS DEL CLIENTE INTEGRADOS -->
                                <div class="datos-cliente-card" id="datosClienteCard">
                                    <h5><i class="fas fa-user"></i> Datos del Cliente</h5>
                                    
                                    <!-- DATOS PARA BOLETA (VISIBLE POR DEFECTO) -->
                                    <div class="row g-3 mt-2" id="datosBoleta">
                                        <div class="col-12">
                                            <label for="inputEmail" class="form-label">Email</label>
                                            <input type="email" name="email_cliente" id="inputEmail" class="form-control" placeholder="correo@ejemplo.com">
                                        </div>
                                        <div class="col-12">
                                            <label for="inputDNI" class="form-label">DNI</label>
                                            <input type="text" name="dni_cliente" id="inputDNI" class="form-control" placeholder="8 dígitos" maxlength="8">
                                        </div>
                                        <div class="col-12">
                                            <label for="inputNombre" class="form-label">Nombre Completo</label>
                                            <input type="text" name="nombre_cliente" id="inputNombre" class="form-control" placeholder="Nombre del cliente" required>
                                        </div>
                                    </div>
                                    
                                    <!-- DATOS ADICIONALES PARA FACTURA - VERSIÓN SIMPLIFICADA -->
                                    <div class="datos-factura-simplificado" id="datosFacturaSection" style="display: none;">
                                        <!-- SECCIÓN DE CONSULTA RUC MEJORADA -->
                                        <div class="consulta-ruc-section">
                                            <h6><i class="fas fa-search"></i> Consultar RUC en SUNAT</h6>
                                            <div class="info-adicional">
                                                <small class="text-muted">Consulta automática de datos del contribuyente</small>
                                            </div>
                                            <div class="consulta-ruc-input">
                                                <div class="flex-grow-1">
                                                    <label class="form-label">Ingrese RUC (11 dígitos)</label>
                                                    <input type="text" id="inputRucConsulta" class="form-control" placeholder="20131312955" maxlength="11">
                                                </div>
                                                <button type="button" id="btnConsultarRUC" class="btn btn-primary">
                                                    <i class="fas fa-search"></i> Consultar
                                                </button>
                                            </div>
                                            
                                            <div class="ruc-loading" id="rucLoading">
                                                <i class="fas fa-spinner fa-spin"></i> Consultando SUNAT...
                                            </div>
                                            
                                            <div class="ruc-result" id="rucResult"></div>
                                        </div>

                                        <!-- CAMPOS SIMPLIFICADOS PARA FACTURA -->
                                        <div class="row g-3 mt-2">
                                            <div class="col-12">
                                                <label for="inputEmailFactura" class="form-label">Email</label>
                                                <input type="email" name="email_cliente_factura" id="inputEmailFactura" class="form-control" placeholder="correo@ejemplo.com">
                                            </div>
                                            <div class="col-12">
                                                <label for="inputRUC" class="form-label">RUC</label>
                                                <input type="text" name="ruc_cliente" id="inputRUC" class="form-control" placeholder="11 dígitos" maxlength="11" required>
                                            </div>
                                            <div class="col-12">
                                                <label for="inputRazonSocial" class="form-label">Razón Social</label>
                                                <input type="text" name="razon_social_cliente" id="inputRazonSocial" class="form-control" placeholder="Razón social del cliente" required>
                                            </div>
                                            <div class="col-12">
                                                <label for="inputDireccion" class="form-label">Dirección Fiscal <span class="campo-opcional">(Opcional)</span></label>
                                                <input type="text" name="direccion_cliente" id="inputDireccion" class="form-control" placeholder="Dirección completa del cliente">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="monto-efectivo" id="montoEfectivo">
                                    <label for="inputEfectivo">Efectivo recibido:</label>
                                    <input type="number" id="inputEfectivo" placeholder="0.00" step="0.01" min="0">
                                    <div class="cambio-info">
                                        Cambio: <span id="cambio">S/ 0.00</span>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" id="btnFinalizar" class="btn btn-success btn-lg" disabled>
                                <i class="fas fa-check-circle"></i> Finalizar Venta
                            </button>
                        </div>
                    </section>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Variables globales para la venta
    let productosVenta = [];
    let metodoPagoSeleccionado = 'MP001';
    let tipoDocumentoSeleccionado = 'boleta';
    let subtotal = 0; // Esto es el total que YA incluye IGV
    let igv = 0;
    let total = 0; // Total = subtotal (ya incluye IGV)

    // Configurar botones de tipo documento
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.documento-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.documento-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                tipoDocumentoSeleccionado = this.getAttribute('data-tipo');
                document.getElementById('inputTipoDocumento').value = tipoDocumentoSeleccionado;
                actualizarInterfazPorDocumento();
            });
        });

        document.querySelectorAll('.metodo-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.metodo-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                metodoPagoSeleccionado = this.getAttribute('data-metodo');
                document.getElementById('inputMetodoPago').value = metodoPagoSeleccionado;
                
                const montoEfectivo = document.getElementById('montoEfectivo');
                if (metodoPagoSeleccionado === 'MP001') {
                    montoEfectivo.style.display = 'block';
                    actualizarCambio();
                } else {
                    montoEfectivo.style.display = 'none';
                }
            });
        });

        // Configurar consulta RUC
        document.getElementById('btnConsultarRUC').addEventListener('click', consultarRUC);

        // Permitir consulta RUC con Enter
        document.getElementById('inputRucConsulta').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                consultarRUC();
            }
        });

        actualizarInterfazPorDocumento();
        
        // Configurar filtro de productos
        const filtroProductos = document.getElementById('inputBusqueda');
        const tablaProductos = document.getElementById('tablaProductos');
        const filasProductos = tablaProductos.getElementsByTagName('tr');
        
        filtroProductos.addEventListener('input', function() {
            const filtro = this.value.toLowerCase();
            
            for (let i = 0; i < filasProductos.length; i++) {
                const fila = filasProductos[i];
                const celdas = fila.getElementsByTagName('td');
                let mostrarFila = false;
                
                if (celdas.length >= 2) {
                    const codigo = celdas[0].textContent.toLowerCase();
                    const nombre = celdas[1].textContent.toLowerCase();
                    
                    if (codigo.includes(filtro) || nombre.includes(filtro)) {
                        mostrarFila = true;
                    }
                }
                
                fila.style.display = mostrarFila ? '' : 'none';
            }
        });

        // Auto-ocultar mensaje de éxito después de 5 segundos
        const alertSuccess = document.querySelector('.alert-success');
        if (alertSuccess) {
            setTimeout(() => {
                alertSuccess.classList.remove('show');
                setTimeout(() => {
                    alertSuccess.remove();
                }, 300);
            }, 5000);
        }
    });

    // FUNCIÓN MEJORADA PARA CONSULTA RUC
    function consultarRUC() {
        const ruc = document.getElementById('inputRucConsulta').value.trim();
        const btnConsultar = document.getElementById('btnConsultarRUC');
        const loading = document.getElementById('rucLoading');
        const result = document.getElementById('rucResult');
        
        if(ruc.length !== 11) {
            showRucResult('error', 'El RUC debe tener exactamente 11 dígitos');
            return;
        }
        
        // Validar que sean solo números
        if(!/^\d+$/.test(ruc)) {
            showRucResult('error', 'El RUC debe contener solo números');
            return;
        }
        
        // Mostrar loading
        btnConsultar.disabled = true;
        loading.style.display = 'block';
        result.style.display = 'none';
        
        // Realizar consulta AJAX
        const formData = new FormData();
        formData.append('consultar_ruc', 'true');
        formData.append('ruc_consulta', ruc);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Autocompletar campos
                document.getElementById('inputRUC').value = ruc;
                document.getElementById('inputRazonSocial').value = data.razon_social_mostrar;
                
                // MEJORA: Manejar dirección - si está vacía, dejar el campo vacío
                document.getElementById('inputDireccion').value = data.direccion;
                
                // Mostrar resultado con el formato mejorado
                const tipoIndicator = data.tipo === 'empresa' ? 
                    '<span class="tipo-ruc-indicator tipo-empresa">EMPRESA</span>' : 
                    '';
                
                // MEJORA: Formato mejorado para dirección
                const direccionInfo = data.direccion ? 
                    `<span class="con-direccion">${data.direccion}</span>` : 
                    '<span class="campo-vacio">No especificada</span>';
                
                // MEJORA: Lógica mejorada para Nombre Comercial - solo mostrar si existe
                let nombreComercialHTML = '';
                if (data.nombre_comercial && data.nombre_comercial.trim() !== '') {
                    nombreComercialHTML = `<small>Nombre Comercial: ${data.nombre_comercial}</small><br>`;
                }
                
                // Construir el mensaje de resultado
                let resultadoHTML = `
                    ${data.tipo_texto} ${data.razon_social_mostrar} ${tipoIndicator}<br>
                    ${nombreComercialHTML}
                    <small>Dirección: ${direccionInfo}</small><br>
                    <small>Estado: ${data.estado} | Condición: ${data.condicion}</small>
                `;
                
                // MEJORA: Agregar alerta de estado si no está ACTIVO
                if (data.estado_alert) {
                    resultadoHTML += `<div class="estado-alerta mt-2">${data.estado_alert}</div>`;
                }
                
                showRucResult('success', resultadoHTML);
            } else {
                showRucResult('error', data.error || 'RUC no encontrado');
            }
        })
        .catch(error => {
            showRucResult('error', 'Error en la consulta. Verifique su conexión a internet.');
        })
        .finally(() => {
            btnConsultar.disabled = false;
            loading.style.display = 'none';
        });
    }

    function showRucResult(type, message) {
        const result = document.getElementById('rucResult');
        result.innerHTML = message;
        result.className = `ruc-result ${type === 'success' ? 'ruc-success' : 'ruc-error'}`;
        result.style.display = 'block';
        
        // Auto-ocultar mensajes de éxito después de 10 segundos (más tiempo para leer la alerta)
        if(type === 'success') {
            setTimeout(() => {
                result.style.display = 'none';
            }, 50000);
        }
    }

    function actualizarInterfazPorDocumento() {
        const btnFinalizar = document.getElementById('btnFinalizar');
        const datosBoleta = document.getElementById('datosBoleta');
        const datosFacturaSection = document.getElementById('datosFacturaSection');
        
        if (tipoDocumentoSeleccionado === 'factura') {
            btnFinalizar.innerHTML = '<i class="fas fa-file-invoice-dollar"></i> Generar Factura';
            datosBoleta.style.display = 'none';
            datosFacturaSection.style.display = 'block';
            
            // Hacer requeridos solo RUC y Razón Social
            document.getElementById('inputRUC').required = true;
            document.getElementById('inputRazonSocial').required = true;
            document.getElementById('inputDireccion').required = false; // Dirección ahora opcional
            document.getElementById('inputNombre').required = false;
            
            // Limpiar campos de boleta
            document.getElementById('inputDNI').value = '';
            document.getElementById('inputNombre').value = '';
        } else {
            btnFinalizar.innerHTML = '<i class="fas fa-check-circle"></i> Finalizar Venta';
            datosBoleta.style.display = 'block';
            datosFacturaSection.style.display = 'none';
            
            // Quitar requerido de campos de factura
            document.getElementById('inputRUC').required = false;
            document.getElementById('inputRazonSocial').required = false;
            document.getElementById('inputDireccion').required = false;
            document.getElementById('inputNombre').required = true;
            
            // Limpiar campos de factura
            document.getElementById('inputRUC').value = '';
            document.getElementById('inputRazonSocial').value = '';
            document.getElementById('inputDireccion').value = '';
            document.getElementById('inputRucConsulta').value = '';
            document.getElementById('rucResult').style.display = 'none';
        }
    }

    // ACTUALIZAR LA VALIDACIÓN DEL FORMULARIO - DIRECCIÓN OPCIONAL
    document.getElementById('formVenta').addEventListener('submit', function(e) {
        if (productosVenta.length === 0) {
            e.preventDefault();
            Swal.fire({
                title: "Faltan productos",
                text: "❌ No hay productos en la venta",
                icon: "warning",
            })
            return;
        }
        
        // Validaciones específicas por tipo de documento
        if (tipoDocumentoSeleccionado === 'factura') {
            const ruc = document.getElementById('inputRUC').value;
            const razonSocial = document.getElementById('inputRazonSocial').value;
            
            if (!ruc || ruc.length !== 11) {
                e.preventDefault();
                Swal.fire({
                    title: "Ruc inválido",
                    text: "❌ Para factura debe ingresar un RUC válido de 11 dígitos",
                    icon: "warning",
                })
                document.getElementById('inputRUC').focus();
                return;
            }
            
            if (!razonSocial.trim()) {
                e.preventDefault();
                if (!ruc || ruc.length !== 11) {
                    e.preventDefault();
                    Swal.fire({
                        title: "Factura incompleta",
                        text: "❌ Para factura debe ingresar la Razón Social del cliente",
                        icon: "warning",
                    })
                    document.getElementById('inputRazonSocial').focus();
                    return;
                }    
            }
            
            // LA DIRECCIÓN AHORA ES OPCIONAL - NO SE VALIDA
        } else {
            // Para boleta, validar DNI si se proporciona
            const dni = document.getElementById('inputDNI').value;
            const nombre = document.getElementById('inputNombre').value;
            
            if (!nombre.trim()) {
                e.preventDefault();
                Swal.fire({
                    title: "Falta nombre",
                    text: "❌ Debe ingresar el nombre del cliente",
                    icon: "warning",
                })
                document.getElementById('inputNombre').focus();
                return;
            }
            
            if (dni && dni.length !== 8) {
                e.preventDefault();
                Swal.fire({
                    title: "Falta dni",
                    text: "❌ El DNI debe tener 8 dígitos",
                    icon: "warning",
                })
                document.getElementById('inputDNI').focus();
                return;
            }
        }
        
        if (metodoPagoSeleccionado === 'MP001') {
            const efectivo = parseFloat(document.getElementById('inputEfectivo').value) || 0;
            if (efectivo <= 0) {
                e.preventDefault();
                Swal.fire({
                    title: "Falta monto",
                    text: "❌ Ingrese el monto en efectivo recibido",
                    icon: "warning",
                })
                document.getElementById('inputEfectivo').focus();
                return;
            }
            
            if (efectivo < total) {
                e.preventDefault();
                Swal.fire({
                    title: "Falta efectivo",
                    text: `❌ El efectivo recibido (S/ ${efectivo.toFixed(2)}) es menor al total de la venta (S/ ${total.toFixed(2)})`,
                    icon: "warning",
                })
                document.getElementById('inputEfectivo').focus();
                return;
            }
        }
        
        // Mostrar mensaje de procesamiento
        const btnFinalizar = document.getElementById('btnFinalizar');
        btnFinalizar.disabled = true;
        btnFinalizar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    });

    // Función para agregar productos - SIN ALERTA, CON CANTIDAD DIRECTA 1
    function agregarProducto(codigo, nombre, precio, stock) {
        const productoExistente = productosVenta.find(p => p.codigo === codigo);
        
        if (productoExistente) {
            // Si el producto ya existe, aumentar la cantidad en 1
            if (productoExistente.cantidad < stock) {
                productoExistente.cantidad++;
                productoExistente.total = productoExistente.cantidad * precio;
            } else {
                Swal.fire({
                    title: "Stock insuficiente",
                    text: "❌ No hay suficiente stock disponible",
                    icon: "warning",
                })
                return;
            }
        } else {
            // Si es nuevo producto, agregar con cantidad 1
            if (stock <= 0) {
                Swal.fire({
                    title: "Stock insuficiente",
                    text: "❌ Producto sin stock disponible",
                    icon: "warning",
                })
                return;
            }
            
            productosVenta.push({
                codigo: codigo,
                nombre: nombre,
                precio: parseFloat(precio),
                cantidad: 1,
                total: parseFloat(precio),
                stock: stock
            });
        }
        
        actualizarVenta();
    }

    function agregarProductoDesdeBusqueda(codigo, nombre, precio, stock) {
        agregarProducto(codigo, nombre, precio, stock);
    }

    // Función para actualizar la venta CON INPUTS DE CANTIDAD AUTOMÁTICOS
    function actualizarVenta() {
        const listaProductos = document.getElementById('listaProductosVenta');
        
        if (productosVenta.length === 0) {
            listaProductos.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-shopping-cart fa-3x mb-3 text-muted"></i>
                    <p>No hay productos agregados</p>
                    <small class="text-muted">Busca y agrega productos para comenzar</small>
                </div>
            `;
        } else {
            let html = '';
            subtotal = 0;
            
            productosVenta.forEach((producto, index) => {
                subtotal += producto.total;
                html += `
                    <div class="producto-venta">
                        <div class="producto-info">
                            <div class="producto-nombre">${producto.nombre}</div>
                            <small class="text-muted">Código: ${producto.codigo}</small>
                            <div class="producto-precio">S/ ${producto.precio.toFixed(2)} c/u</div>
                        </div>
                        <div class="producto-controls">
                            <input type="number" 
                                   class="cantidad-input" 
                                   value="${producto.cantidad}" 
                                   min="1" 
                                   max="${producto.stock}"
                                   onchange="actualizarCantidad(${index}, this.value)">
                            <div class="producto-total text-success">
                                <strong>S/ ${producto.total.toFixed(2)}</strong>
                            </div>
                            <button type="button" class="btn-quitar" onclick="eliminarProducto(${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            listaProductos.innerHTML = html;
        }
        
        // CORRECCIÓN: Calcular IGV correctamente (ya está incluido en el precio)
        // El subtotal ya incluye IGV, por lo tanto:
        // Base Imponible (OP. GRAVADA) = Subtotal / 1.18
        // IGV = Subtotal - Base Imponible
        const opGravada = subtotal / 1.18;
        igv = subtotal - opGravada;
        total = subtotal; // El total es el subtotal (ya incluye IGV)
        
        // Mostrar los valores correctamente
        document.getElementById('opGravada').textContent = 'S/ ' + opGravada.toFixed(2);
        document.getElementById('igv').textContent = 'S/ ' + igv.toFixed(2);
        document.getElementById('totalVenta').textContent = 'S/ ' + total.toFixed(2);
        
        // Actualizar inputs hidden del formulario
        document.getElementById('inputTotal').value = total;
        document.getElementById('inputProductosJson').value = JSON.stringify(productosVenta);
        
        // Habilitar/deshabilitar botón finalizar
        const btnFinalizar = document.getElementById('btnFinalizar');
        btnFinalizar.disabled = productosVenta.length === 0;
        
        // Actualizar cambio si es pago en efectivo
        if (metodoPagoSeleccionado === 'MP001') {
            actualizarCambio();
        }
    }

    // FUNCIÓN PARA ACTUALIZAR CANTIDAD AUTOMÁTICAMENTE
    function actualizarCantidad(index, nuevaCantidad) {
        const cantidadNum = parseInt(nuevaCantidad);
        const producto = productosVenta[index];
        
        if (isNaN(cantidadNum) || cantidadNum <= 0) {
            // Restaurar valor anterior si no es válido
            document.querySelectorAll('.cantidad-input')[index].value = producto.cantidad;
            return;
        }
        
        if (cantidadNum > producto.stock) {
            Swal.fire({
                title: "Stock insuficiente",
                text: `❌ No hay suficiente stock. Stock disponible: ${producto.stock}`,
                icon: "warning",
            })
            document.querySelectorAll('.cantidad-input')[index].value = producto.cantidad;
            return;
        }
        
        // Actualizar cantidad y recalcular total
        producto.cantidad = cantidadNum;
        producto.total = producto.cantidad * producto.precio;
        actualizarVenta();
    }

    function eliminarProducto(index) {
        productosVenta.splice(index, 1);
        actualizarVenta();
    }

    // Evento para limpiar venta
    document.getElementById('btnLimpiar').addEventListener('click', function() {
        if(productosVenta.length === 0) {
            Swal.fire({
                title: "Faltan productos",
                text: "⚠️ No hay productos en la venta",
                icon: "warning",
            })
            return;
        }
        
        Swal.fire({
            title: "¿Estás seguro?",
            text: "¿Estás seguro de que deseas limpiar toda la venta?",
            icon: "info",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Sí, estoy seguro"
        }).then((result1) => {                
            if (result1.isConfirmed) {                    
                productosVenta = [];
            actualizarVenta();
            }
        });
    });

    function actualizarCambio() {
        const efectivo = parseFloat(document.getElementById('inputEfectivo').value) || 0;
        const cambio = efectivo - total;
        document.getElementById('cambio').textContent = 'S/ ' + (cambio > 0 ? cambio.toFixed(2) : '0.00');
    }

    document.getElementById('inputEfectivo').addEventListener('input', actualizarCambio);
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../controlador/vendedor/dropdown.js"></script>
    <script src="../../controlador/vendedor/barralateral.js"></script>
</body>
</html>