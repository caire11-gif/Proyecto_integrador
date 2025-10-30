<?php
// CONEXIÓN A LA BASE DE DATOS
$conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");

if(!$conexion){
    echo "Un error de conexión ocurrió. <br>";
    exit;
}

session_start();
$usuariovendedor=$_SESSION['nombreusuariovendedor'];
$apellidovendedor=$_SESSION['apellidousuariovendedor'];

$inicialNombre = substr($usuariovendedor, 0, 1);
$inicialApellido=substr($apellidovendedor,0,1);

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
}

verificarDatosMaestros($conexion);

// PROCESAR VENTA CUANDO SE ENVÍA EL FORMULARIO
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_venta'])) {
    
    // GENERAR CÓDIGOS ÚNICOS
    $timestamp = substr(time(), -6);
    $cod_venta = 'V' . $timestamp;
    
    // DATOS DE LA VENTA
    $cod_usuario = 'user001'; // Usuario por defecto según tu BD
    $cod_metodopago = $_POST['cod_metodopago'];
    $total = $_POST['total'];
    $tipo_documento = $_POST['tipo_documento'];
    $productos = json_decode($_POST['productos_json'], true);
    
    // VALIDAR DATOS DEL CLIENTE PARA FACTURA
    $error_venta = '';
    if($tipo_documento === 'factura') {
        $ruc = $_POST['ruc'] ?? '';
        $razon_social = $_POST['razon_social'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        
        if(empty($ruc) || strlen($ruc) !== 11) {
            $error_venta = "Para factura debe ingresar un RUC válido de 11 dígitos";
        } elseif(empty($razon_social)) {
            $error_venta = "Para factura debe ingresar la Razón Social del cliente";
        }
    }
    
    if(empty($error_venta)) {
        // Generar datos del comprobante
        if($tipo_documento === 'factura') {
            $nombre_documento = 'Factura';
            $serie = 'F001';
            $numero = rand(1000, 9999);
            $cod_comprobante = 'F' . $timestamp;
        } else {
            $nombre_documento = 'Boleta';
            $serie = 'B001';
            $numero = rand(1000, 9999);
            $cod_comprobante = 'B' . $timestamp;
        }
        
        // Iniciar transacción
        pg_query($conexion, "BEGIN");
        
        try {
            // 1. GUARDAR COMPROBANTE EN tipodocumento
            $queryComprobante = "INSERT INTO tipodocumento (cod_tipodocumento, nombre, serie, numero) 
                                 VALUES ('$cod_comprobante', '$nombre_documento', '$serie', $numero)";
            $resultComprobante = pg_query($conexion, $queryComprobante);
            
            if(!$resultComprobante) {
                throw new Exception("Error al guardar comprobante: " . pg_last_error($conexion));
            }
            
            // 2. GUARDAR VENTA PRINCIPAL (SIN total, se calcula desde detalles)
            $queryVenta = "INSERT INTO venta (cod_venta, cod_usuario, cod_metodopago, cod_tiporeporte, fecha_venta) 
                           VALUES ('$cod_venta', '$cod_usuario', '$cod_metodopago', 'TR001', CURRENT_DATE)";
            $resultVenta = pg_query($conexion, $queryVenta);
            
            if(!$resultVenta) {
                throw new Exception("Error al guardar venta: " . pg_last_error($conexion));
            }
            
            // 3. GUARDAR PRODUCTOS EN DETALLEVENTA Y ACTUALIZAR STOCK
            foreach($productos as $index => $producto) {
                // Generar código único para detalleventa
                $cod_detalleventa = 'DV' . $timestamp . $index;
                
                // Guardar en detalleventa
                $queryDetalle = "INSERT INTO detalleventa (cod_detalleventa, cod_venta, cod_producto, cantidad_unidades, precio_unitario, total) 
                                 VALUES ('$cod_detalleventa', '$cod_venta', '{$producto['codigo']}', {$producto['cantidad']}, {$producto['precio']}, {$producto['total']})";
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
                
                $cod_inventario = 'INV' . $timestamp . $index;
                $query_inventario = "INSERT INTO registroinventario (cod_inventario, cod_usuario, fecha_inventario, cod_producto, cod_tipomovimiento, cantidad, precio_unitario, total) 
                                           VALUES ('$cod_inventario', '$cod_usuario', CURRENT_DATE, '{$producto['codigo']}', 'TM002', '{$producto['cantidad']}', '{$producto['precio']}', '{$producto['total']}')";
                        
                if(!pg_query($conexion, $query_inventario)) {
                    throw new Exception("Error al insertar en registro inventario: " . pg_last_error($conexion));
                }

                // Registrar movimiento de inventario
                $cod_movimiento = 'MOV' . $timestamp . $index;
                $queryMovimiento = "INSERT INTO movimiento (cod_movimiento, cod_producto, cod_tipomovimiento, fecha_movimiento, cod_usuario, observacion) 
                                    VALUES ('$cod_movimiento', '{$producto['codigo']}', 'TM002', CURRENT_DATE, '$cod_usuario', 'Venta - $cod_venta')";
                $resultMovimiento = pg_query($conexion, $queryMovimiento);
                
                // Registrar en historial
                $cod_historial = 'H' . $timestamp . $index;
                $queryHistorial = "INSERT INTO historialproductos (cod_historialproductos, cod_usuario, cod_producto, cod_tipoaccion, observacion) 
                                   VALUES ('$cod_historial', '$cod_usuario', '{$producto['codigo']}', 'TA001', 'Venta $cod_venta - Cantidad: {$producto['cantidad']}')";
                pg_query($conexion, $queryHistorial);
            }
            
            // 4. GUARDAR REPORTE
            $cod_reporte = 'REP' . $timestamp;
            $cod_tiporeporte = 'TR001';
            $datos_reporte = "Venta $cod_venta - $nombre_documento $serie-$numero - Total: S/ $total - Método: $cod_metodopago";
            
            $queryReporte = "INSERT INTO reporte (cod_reporte, cod_usuario, fecha_reporte, cod_tiporeporte, cod_tipodocumento, datos_reporte) 
                             VALUES ('$cod_reporte', '$cod_usuario', CURRENT_DATE, '$cod_tiporeporte', '$cod_comprobante', '$datos_reporte')";
            $resultReporte = pg_query($conexion, $queryReporte);
            
            if(!$resultReporte) {
                throw new Exception("Error al guardar reporte: " . pg_last_error($conexion));
            }
            
            // Confirmar transacción
            pg_query($conexion, "COMMIT");

            // Mostrar mensaje de éxito
            $_SESSION['venta_exitosa'] = true;
            $_SESSION['mensaje_exito'] = "✅ Venta procesada correctamente!<br><strong>Código:</strong> $cod_venta<br><strong>Total:</strong> S/ " . number_format($total, 2);
            
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
        gap: 10px;
    }

    .cantidad-controls {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f8f9fa;
        padding: 5px 10px;
        border-radius: 6px;
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

    /* ESTILOS PARA EL MENSAJE DE ÉXITO */
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

    /* Asegurar que el contenido no se mueva */
    .contenido-principal {
        transition: all 0.3s ease;
    }

    /* NUEVOS ESTILOS PARA MEJOR ORGANIZACIÓN DEL PANEL DERECHO */
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
    }

    .btn-quitar {
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 6px 10px;
        transition: all 0.3s ease;
    }

    .btn-quitar:hover {
        background: #c82333;
        transform: scale(1.05);
    }

    .btn-cantidad {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dee2e6;
        background: white;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .btn-cantidad:hover {
        background: #f8f9fa;
        border-color: #007bff;
    }

    .badge-cantidad {
        min-width: 40px;
        font-size: 0.9em;
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
                    <a href="dashboard.php" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="nuevaventa.php" class="nav-link active"><ul><i class="fas fa-cash-register"></i>Nueva Venta</ul></a>
                    <a href="registrodevolucion.php" class="nav-link"><ul><i class="fas fa-undo-alt"></i>Registrar Devolución</ul></a>
                    <a href="boletafactura.php" class="nav-link"><ul><i class="fas fa-receipt"></i>Boletas/Facturas</ul></a>
                    <a href="consultastock.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Consulta-stock</ul></a>
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
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-search fa-2x mb-2"></i>
                                <p>Ingresa un término de búsqueda</p>
                            </div>
                        <?php endif; ?>
                    </div>
                
                    <div class="productos-frecuentes">
                        <div class="mb-3">
                            <h4><i class="fas fa-boxes"></i> Productos Disponibles</h4>
                            <small class="text-muted">Haz clic en 'Agregar' para incluir en la venta</small>
                        </div>
                        
                        <!-- FILTRO PARA PRODUCTOS DISPONIBLES -->
                        <div class="filtro-productos">
                            <input type="text" id="filtroProductos" placeholder="Filtrar productos por código o nombre...">
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

                <!-- FORMULARIO PRINCIPAL PARA ENVIAR VENTA -->
                <form id="formVenta" method="POST" action="">
                    <input type="hidden" name="finalizar_venta" value="1">
                    <input type="hidden" name="cod_metodopago" id="inputMetodoPago" value="mp001">
                    <input type="hidden" name="tipo_documento" id="inputTipoDocumento" value="boleta">
                    <input type="hidden" name="total" id="inputTotal" value="0">
                    <input type="hidden" name="productos_json" id="inputProductosJson" value="[]">

                    <!-- SECCIÓN PARA DATOS DEL CLIENTE (SOLO FACTURA) -->
                    <div class="datos-cliente-section mb-4" id="datosClienteSection" style="display: none;">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-user-tie"></i> Datos del Cliente - Factura</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="inputRUC" class="form-label">RUC *</label>
                                        <input type="text" name="ruc" id="inputRUC" class="form-control" placeholder="11 dígitos" maxlength="11">
                                    </div>
                                    <div class="col-md-8">
                                        <label for="inputRazonSocial" class="form-label">Razón Social *</label>
                                        <input type="text" name="razon_social" id="inputRazonSocial" class="form-control" placeholder="Nombre o razón social del cliente">
                                    </div>
                                    <div class="col-12">
                                        <label for="inputDireccion" class="form-label">Dirección</label>
                                        <input type="text" name="direccion" id="inputDireccion" class="form-control" placeholder="Dirección del cliente">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                            <div class="resumen-linea">
                                <span>Subtotal:</span>
                                <span id="subtotal">S/ 0.00</span>
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
                                
                                <div class="tipo-documento">
                                    <label class="form-label"><i class="fas fa-file-invoice"></i> Tipo de Documento:</label>
                                    <div class="documento-botones">
                                        <button type="button" class="btn documento-btn active" data-tipo="boleta">
                                            <i class="fas fa-receipt"></i> Boleta
                                        </button>
                                        <button type="button" class="btn documento-btn" data-tipo="factura">
                                            <i class="fas fa-file-invoice-dollar"></i> Factura
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="metodos-grid">
                                    <?php
                                    if ($resultMetodos && pg_num_rows($resultMetodos) > 0) {
                                        pg_result_seek($resultMetodos, 0);
                                        while($metodo = pg_fetch_assoc($resultMetodos)) {
                                            $active = $metodo['cod_metodopago'] === 'mp001' ? 'active' : '';
                                            $icon = $metodo['cod_metodopago'] === 'mp001' ? 'fa-money-bill-wave' : 
                                                   ($metodo['cod_metodopago'] === 'mp002' ? 'fa-credit-card' : 'fa-mobile-alt');
                                            echo "
                                            <button type='button' class='metodo-btn $active' data-metodo='{$metodo['cod_metodopago']}'>
                                                <i class='fas $icon'></i> {$metodo['nombre']}
                                            </button>
                                            ";
                                        }
                                    }
                                    ?>
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

    <script>
    // Variables globales para la venta
    let productosVenta = [];
    let metodoPagoSeleccionado = 'mp001';
    let tipoDocumentoSeleccionado = 'boleta';
    let subtotal = 0;
    let igv = 0;
    let total = 0;

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
                if (metodoPagoSeleccionado === 'mp001') {
                    montoEfectivo.style.display = 'block';
                    actualizarCambio();
                } else {
                    montoEfectivo.style.display = 'none';
                }
            });
        });

        actualizarInterfazPorDocumento();
        
        // Configurar filtro de productos
        const filtroProductos = document.getElementById('filtroProductos');
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

    function actualizarInterfazPorDocumento() {
        const btnFinalizar = document.getElementById('btnFinalizar');
        const datosClienteSection = document.getElementById('datosClienteSection');
        
        if (tipoDocumentoSeleccionado === 'factura') {
            btnFinalizar.innerHTML = '<i class="fas fa-file-invoice-dollar"></i> Generar Factura';
            datosClienteSection.style.display = 'block';
        } else {
            btnFinalizar.innerHTML = '<i class="fas fa-check-circle"></i> Finalizar Venta';
            datosClienteSection.style.display = 'none';
        }
    }

    // Función para agregar productos
    function agregarProducto(codigo, nombre, precio, stock) {
        const productoExistente = productosVenta.find(p => p.codigo === codigo);
        
        if (productoExistente) {
            if (productoExistente.cantidad < stock) {
                productoExistente.cantidad++;
                productoExistente.total = productoExistente.cantidad * precio;
            } else {
                alert('❌ No hay suficiente stock disponible');
                return;
            }
        } else {
            if (stock <= 0) {
                alert('❌ Producto sin stock disponible');
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

    // Función para actualizar la venta
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
                            <div class="cantidad-controls">
                                <button type="button" class="btn-cantidad" onclick="modificarCantidad(${index}, -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="badge bg-primary badge-cantidad mx-2">${producto.cantidad}</span>
                                <button type="button" class="btn-cantidad" onclick="modificarCantidad(${index}, 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
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
        
        // Calcular totales
        igv = parseFloat((subtotal * 0.18).toFixed(2));
        total = parseFloat((subtotal + igv).toFixed(2));
        document.getElementById('subtotal').textContent = 'S/ ' + subtotal.toFixed(2);
        document.getElementById('igv').textContent = 'S/ ' + igv.toFixed(2);
        document.getElementById('totalVenta').textContent = 'S/ ' + total.toFixed(2);
        
        // Actualizar inputs hidden del formulario
        document.getElementById('inputTotal').value = total;
        document.getElementById('inputProductosJson').value = JSON.stringify(productosVenta);
        
        // Habilitar/deshabilitar botón finalizar
        const btnFinalizar = document.getElementById('btnFinalizar');
        btnFinalizar.disabled = productosVenta.length === 0;
        
        // Actualizar cambio si es pago en efectivo
        if (metodoPagoSeleccionado === 'mp001') {
            actualizarCambio();
        }
    }

    function modificarCantidad(index, cambio) {
        const producto = productosVenta[index];
        const nuevaCantidad = producto.cantidad + cambio;
        
        if (nuevaCantidad <= 0) {
            eliminarProducto(index);
            return;
        }
        
        if (nuevaCantidad > producto.stock) {
            alert(`❌ No hay suficiente stock. Stock disponible: ${producto.stock}`);
            return;
        }
        
        producto.cantidad = nuevaCantidad;
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
            alert('⚠️ No hay productos en la venta');
            return;
        }
        
        if(confirm('¿Estás seguro de que deseas limpiar toda la venta?')) {
            productosVenta = [];
            actualizarVenta();
        }
    });

    function actualizarCambio() {
        const efectivo = parseFloat(document.getElementById('inputEfectivo').value) || 0;
        const cambio = efectivo - total;
        document.getElementById('cambio').textContent = 'S/ ' + (cambio > 0 ? cambio.toFixed(2) : '0.00');
    }

    document.getElementById('inputEfectivo').addEventListener('input', actualizarCambio);

    // Validar formulario antes de enviar
    document.getElementById('formVenta').addEventListener('submit', function(e) {
        if (productosVenta.length === 0) {
            e.preventDefault();
            alert('❌ No hay productos en la venta');
            return;
        }
        
        // Validar datos del cliente para factura
        if (tipoDocumentoSeleccionado === 'factura') {
            const ruc = document.getElementById('inputRUC').value;
            const razonSocial = document.getElementById('inputRazonSocial').value;
            
            if (!ruc || ruc.length !== 11) {
                e.preventDefault();
                alert('❌ Para factura debe ingresar un RUC válido de 11 dígitos');
                document.getElementById('inputRUC').focus();
                return;
            }
            
            if (!razonSocial) {
                e.preventDefault();
                alert('❌ Para factura debe ingresar la Razón Social del cliente');
                document.getElementById('inputRazonSocial').focus();
                return;
            }
        }
        
        if (metodoPagoSeleccionado === 'mp001') {
            const efectivo = parseFloat(document.getElementById('inputEfectivo').value) || 0;
            if (efectivo <= 0) {
                e.preventDefault();
                alert('❌ Ingrese el monto en efectivo recibido');
                document.getElementById('inputEfectivo').focus();
                return;
            }
            
            if (efectivo < total) {
                e.preventDefault();
                alert(`❌ El efectivo recibido (S/ ${efectivo.toFixed(2)}) es menor al total de la venta (S/ ${total.toFixed(2)})`);
                document.getElementById('inputEfectivo').focus();
                return;
            }
        }
        
        // Mostrar mensaje de procesamiento
        const btnFinalizar = document.getElementById('btnFinalizar');
        btnFinalizar.disabled = true;
        btnFinalizar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    });

    const dropdownBtn = document.getElementById("dropdownBtn");
    const dropdownList = document.getElementById("dropdownList");
    const arrow = document.getElementById("arrow");

    dropdownBtn.addEventListener("click", () => {
        const isVisible = dropdownList.style.display === "block";
        dropdownList.style.display = isVisible ? "none" : "block";
        arrow.style.transform = isVisible ? "rotate(0deg)" : "rotate(180deg)";
    });
                            
    // Cierra el menú si haces clic fuera
    document.addEventListener("click", (e) => {
        if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
            dropdownList.style.display = "none";
            arrow.style.transform = "rotate(0deg)";
        }
    });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>