<?php
date_default_timezone_set('America/Lima');
include('../login/ingresarlogin.php');

$conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
if(!$conexion){
    die("Error de conexión a la base de datos");
}

$cod_usuario = $_SESSION['cod_usuario'] ?? 'USU001';

// Inicializar variables con valores por defecto
$precios_productos = array();
$unidades_por_caja = array();
$productos_por_proveedor = array();

// Obtener parámetro de filtro si existe
$filtro = $_GET['filtro'] ?? 'todos';
$busqueda = $_GET['busqueda'] ?? '';
$tab_activa = $_GET['tab'] ?? 'nuevaEntrada';

try {
    // Obtener datos para los select
    $result1 = pg_query($conexion, "SELECT cod_proveedor, razon_social FROM proveedor");
    if(!$result1){
        error_log("Error al cargar proveedores: " . pg_last_error($conexion));
    }

    // OBTENER CÓDIGOS DE TABLAS DE REFERENCIA
    // Métodos de pago
    $result_mp = pg_query($conexion, "SELECT cod_metodopago FROM metodopago LIMIT 1");
    if($result_mp && pg_num_rows($result_mp) > 0) {
        $row = pg_fetch_assoc($result_mp);
        $cod_metodopago = $row['cod_metodopago'];
    } else {
        $cod_metodopago = 'MP001';
    }

    // Tipos de reporte
    $result_tr = pg_query($conexion, "SELECT cod_tiporeporte FROM tiporeporte LIMIT 1");
    if($result_tr && pg_num_rows($result_tr) > 0) {
        $row = pg_fetch_assoc($result_tr);
        $cod_tiporeporte = $row['cod_tiporeporte'];
    } else {
        $cod_tiporeporte = 'REP001';
    }

    // Tipos de movimiento
    $result_tm = pg_query($conexion, "SELECT cod_tipomovimiento FROM tipomovimiento LIMIT 1");
    if($result_tm && pg_num_rows($result_tm) > 0) {
        $row = pg_fetch_assoc($result_tm);
        $cod_tipomovimiento = $row['cod_tipomovimiento'];
    } else {
        $cod_tipomovimiento = 'MOV001';
        $insert_tm = pg_query($conexion, "INSERT INTO tipomovimiento (cod_tipomovimiento, nombre) VALUES ('MOV001', 'Entrada')");
    }

    // Tipos de acción
    $result_ta = pg_query($conexion, "SELECT cod_tipoaccion FROM tipoaccion LIMIT 1");
    if($result_ta && pg_num_rows($result_ta) > 0) {
        $row = pg_fetch_assoc($result_ta);
        $cod_tipoaccion = $row['cod_tipoaccion'];
    } else {
        $cod_tipoaccion = 'ACC001';
        $insert_ta = pg_query($conexion, "INSERT INTO tipoaccion (cod_tipoaccion, nombre) VALUES ('ACC001', 'Registro')");
    }

    // Obtener productos con precios y proveedores
    $result3 = pg_query($conexion, "SELECT p.cod_producto, p.nombre, p.precio_caja, p.unidades_por_caja, p.cod_proveedor, pr.razon_social 
                                  FROM producto p 
                                  LEFT JOIN proveedor pr ON p.cod_proveedor = pr.cod_proveedor");
    if(!$result3){
        error_log("Error al cargar productos: " . pg_last_error($conexion));
    } else {
        pg_result_seek($result3, 0);
        while($row = pg_fetch_assoc($result3)){
            $precios_productos[$row['cod_producto']] = $row['precio_caja'];
            $unidades_por_caja[$row['cod_producto']] = $row['unidades_por_caja'];
            
            // Organizar productos por proveedor
            $cod_proveedor = $row['cod_proveedor'];
            if (!isset($productos_por_proveedor[$cod_proveedor])) {
                $productos_por_proveedor[$cod_proveedor] = array();
            }
            $productos_por_proveedor[$cod_proveedor][] = array(
                'cod_producto' => $row['cod_producto'],
                'nombre' => $row['nombre'],
                'precio_caja' => $row['precio_caja'],
                'unidades_por_caja' => $row['unidades_por_caja']
            );
        }
    }

    // CONSULTA CORREGIDA para historial - SOLO COMPRAS CON PRODUCTOS
    $query_historial = "SELECT 
                        c.cod_compra,
                        c.fecha_compra AS fecha,
                        pr.razon_social AS proveedor_nombre,
                        u.usuario AS usuario_registro,
                        COUNT(dc.cod_detallecompra) AS total_productos,
                        COALESCE(SUM(dc.total), 0) AS total_compra,
                        mp.nombre AS metodo_pago
                    FROM compra c
                    JOIN proveedor pr ON c.cod_proveedor = pr.cod_proveedor
                    JOIN usuario u ON c.cod_usuario = u.cod_usuario
                    JOIN metodopago mp ON c.cod_metodopago = mp.cod_metodopago
                    INNER JOIN detallecompra dc ON c.cod_compra = dc.cod_compra  -- INNER JOIN para solo compras con detalles
                    WHERE dc.cantidad_cajas > 0  -- Solo detalles con cantidad mayor a 0
                    AND dc.cod_producto IS NOT NULL  -- Solo detalles con producto válido
                    AND dc.total > 0  -- Solo detalles con total mayor a 0
                    ";

    // Aplicar filtros
    if ($filtro === 'hoy') {
        $query_historial .= " AND DATE(c.fecha_compra) = CURRENT_DATE";
    } elseif ($filtro === 'semana') {
        $query_historial .= " AND c.fecha_compra >= DATE_TRUNC('week', CURRENT_DATE)";
    } elseif ($filtro === 'mes') {
        $query_historial .= " AND c.fecha_compra >= DATE_TRUNC('month', CURRENT_DATE)";
    }

    // Aplicar búsqueda
    if (!empty($busqueda)) {
        $busqueda_like = pg_escape_string($busqueda);
        $query_historial .= " AND (c.cod_compra ILIKE '%$busqueda_like%' 
                                 OR pr.razon_social ILIKE '%$busqueda_like%'
                                 OR u.usuario ILIKE '%$busqueda_like%')";
    }

    $query_historial .= " GROUP BY c.cod_compra, c.fecha_compra, pr.razon_social, u.usuario, mp.nombre
                         HAVING COUNT(dc.cod_detallecompra) > 0  -- Solo grupos con al menos un producto
                         AND COALESCE(SUM(dc.total), 0) > 0  -- Solo grupos con total mayor a 0
                         ORDER BY c.fecha_compra DESC, c.cod_compra DESC";

    error_log("Consulta historial: " . $query_historial); // Para debugging
    
    $result4 = pg_query($conexion, $query_historial);
    
    if(!$result4){
        error_log("Error al cargar historial: " . pg_last_error($conexion));
    }

} catch (Exception $e) {
    error_log("Error en consultas iniciales: " . $e->getMessage());
}

// FUNCIÓN PARA GENERAR CÓDIGOS SECUENCIALES SIMPLIFICADOS - IGNORANDO CÓDIGOS LARGOS
function generarCodigoSecuencial($conexion, $prefijo, $tabla, $campo) {
    // Buscar el máximo número actual en la base de datos, pero solo códigos cortos
    $query = "SELECT MAX(CAST(SUBSTRING($campo FROM " . (strlen($prefijo) + 1) . ") AS INTEGER)) as max_num 
              FROM $tabla 
              WHERE $campo ~ '^" . $prefijo . "[0-9]+$'
              AND LENGTH($campo) <= " . (strlen($prefijo) + 3); // Solo códigos de máximo 3 dígitos después del prefijo
    
    $result = pg_query($conexion, $query);
    $max_num = 0;
    
    if($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $max_num = $row ? intval($row['max_num']) : 0;
    }
    
    $nuevo_numero = $max_num + 1;
    
    // Formatear con ceros a la izquierda según el número
    if($nuevo_numero < 10) {
        return $prefijo . '0' . $nuevo_numero;
    } else {
        return $prefijo . $nuevo_numero;
    }
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Iniciar transacción
        pg_query($conexion, "BEGIN");
        
        $proveedor = $_POST['proveedor'] ?? '';
        $fecha_entrada = date('Y-m-d H:i:s');
        $numero_factura = $_POST['numero_factura'] ?? '';
        $productos = $_POST['productos'] ?? [];
        $cantidades = $_POST['cantidades'] ?? [];
        $precios_caja = $_POST['precios_caja'] ?? [];
        
        // Validar datos requeridos
        if(empty($proveedor) || empty($fecha_entrada)) {
            throw new Exception("Proveedor y fecha son obligatorios");
        }

        if(empty($productos) || count(array_filter($productos)) == 0) {
            throw new Exception("Debe agregar al menos un producto");
        }

        // 1. GENERAR CÓDIGO DE COMPRA SECUENCIAL
        $cod_compra = generarCodigoSecuencial($conexion, 'COM', 'compra', 'cod_compra');
        
        // 2. INSERTAR EN COMPRA
        $query_compra = "INSERT INTO compra (cod_compra, cod_usuario, cod_proveedor, cod_metodopago, cod_tiporeporte, fecha_compra) 
                        VALUES ('$cod_compra', '$cod_usuario', '$proveedor', '$cod_metodopago', '$cod_tiporeporte', '$fecha_entrada')";
        
        if(!pg_query($conexion, $query_compra)) {
            throw new Exception("Error al insertar compra: " . pg_last_error($conexion));
        }

        // 3. PROCESAR CADA PRODUCTO
        foreach($productos as $index => $cod_producto) {
            if(!empty($cod_producto)) {
                $cantidad_cajas = intval($cantidades[$index]);
                $precio_por_caja = floatval($precios_caja[$index]);
                
                // CALCULAR PRECIO UNITARIO Y TOTAL CORREGIDOS
                $unidades_por_caja_producto = $unidades_por_caja[$cod_producto];
                $precio_unitario = $precio_por_caja / $unidades_por_caja_producto;
                
                // CALCULO CORREGIDO: Total = cantidad_cajas * precio_por_caja
                $total = $cantidad_cajas * $precio_por_caja;
                $cantidad_unidades = $cantidad_cajas * $unidades_por_caja_producto;
                
                // Validar datos del producto
                if($cantidad_cajas <= 0) {
                    throw new Exception("La cantidad debe ser mayor a 0");
                }
                if($precio_por_caja < 0) {
                    throw new Exception("El precio por caja no puede ser negativo");
                }

                // Generar códigos secuenciales para cada tabla
                $cod_detallecompra = generarCodigoSecuencial($conexion, 'DET', 'detallecompra', 'cod_detallecompra');
                $cod_inventario = generarCodigoSecuencial($conexion, 'INV', 'registroinventario', 'cod_inventario');
                $cod_movimiento = generarCodigoSecuencial($conexion, 'MOV', 'movimiento', 'cod_movimiento');
                $cod_historial = generarCodigoSecuencial($conexion, 'HIS', 'historialproductos', 'cod_historialproductos');
                
                // 4. Insertar en detallecompra
                $query_detalle = "INSERT INTO detallecompra (cod_detallecompra, cod_compra, cod_producto, cantidad_cajas, cantidad_unidades, total) 
                                 VALUES ('$cod_detallecompra', '$cod_compra', '$cod_producto', $cantidad_cajas, $cantidad_unidades, $total)";
                
                if(!pg_query($conexion, $query_detalle)) {
                    throw new Exception("Error al insertar detalle de compra: " . pg_last_error($conexion));
                }
                
                // 5. Actualizar stock en producto
                $unidades_agregadas = $cantidad_cajas * $unidades_por_caja[$cod_producto];
                $query_update_stock = "UPDATE producto SET stock = stock + $unidades_agregadas WHERE cod_producto = '$cod_producto'";
                
                if(!pg_query($conexion, $query_update_stock)) {
                    throw new Exception("Error al actualizar stock: " . pg_last_error($conexion));
                }

                // 6. Insertar en registroinventario
                $query_inventario = "INSERT INTO registroinventario (cod_inventario, cod_usuario, fecha_inventario, cod_producto, cod_tipomovimiento, cantidad, precio_unitario, total) 
                                   VALUES ('$cod_inventario', '$cod_usuario', '$fecha_entrada', '$cod_producto', '$cod_tipomovimiento', $cantidad_unidades, $precio_unitario, $total)";
                
                if(!pg_query($conexion, $query_inventario)) {
                    throw new Exception("Error al insertar en registro inventario: " . pg_last_error($conexion));
                }
                
                // 7. Insertar en movimiento
                $observacion = "Entrada de proveedor - Compra: $cod_compra - $cantidad_cajas cajas ($unidades_agregadas unidades)";
                $query_movimiento = "INSERT INTO movimiento (cod_movimiento, cod_producto, cod_tipomovimiento, fecha_movimiento, cod_usuario, observacion) 
                                   VALUES ('$cod_movimiento', '$cod_producto', '$cod_tipomovimiento', '$fecha_entrada', '$cod_usuario', '$observacion')";
                
                if(!pg_query($conexion, $query_movimiento)) {
                    throw new Exception("Error al insertar movimiento: " . pg_last_error($conexion));
                }
                
                // 8. Insertar en historialproductos SOLO SI EXISTE EL TIPO DE ACCIÓN
                if (!empty($cod_tipoaccion)) {
                    $observacion_historial = "Entrada de $cantidad_cajas cajas ($unidades_agregadas unidades) - Compra: $cod_compra - Total: S/ $total";
                    $query_historial = "INSERT INTO historialproductos (cod_historialproductos, cod_usuario, cod_producto, cod_tipoaccion, observacion) 
                                      VALUES ('$cod_historial', '$cod_usuario', '$cod_producto', '$cod_tipoaccion', '$observacion_historial')";
                    
                    if(!pg_query($conexion, $query_historial)) {
                        error_log("Error al insertar historial (continuando sin historial): " . pg_last_error($conexion));
                    }
                }
            }
        }
        
        // Confirmar transacción
        pg_query($conexion, "COMMIT");
        
        echo "<script>
            alert('✅ Entrada registrada correctamente. Stock actualizado.\\\\n\\\\n📋 Código de compra: $cod_compra');
            window.location.href = 'entradaproveedor.php?tab=nuevaEntrada';
        </script>";
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        pg_query($conexion, "ROLLBACK");
        echo "<script>alert('❌ Error: " . $e->getMessage() . "');</script>";
    }
}

// Obtener datos del usuario para la interfaz
$usuarioencargado = $_SESSION['nombreusuarioencargado'] ?? '';
$apellidoencargado = $_SESSION['apellidousuarioencargado'] ?? '';
$inicialNombre = substr($usuarioencargado, 0, 1);
$inicialApellido = substr($apellidoencargado, 0, 1);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Sistema de Encargado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/almacen-estilo.css">
    <link rel="stylesheet" href="css/almacen-boton/boton.css">
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
                    <small id="userRole">Encargado</small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.html" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="gestionproductos.html" class="nav-link"><ul><i class="fas fa-boxes"></i>Gestión de Productos</ul></a>
                    <a href="almacenproveedores.html" class="nav-link"><ul><i class="fas fa-truck"></i>Proveedores</ul></a>
                    <a href="entradaproveedor.php" class="nav-link active"><ul><i class="fas fa-truck-loading"></i>Entradas Proveedor</ul></a>
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
                        <small class="text-muted" id="userPosition">Encargado</small>
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
            <br>
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1"><i class="fas fa-truck-loading me-2"></i>Entradas de Proveedor</h4>
                        <p class="text-muted mb-0">Registra nuevas entradas de productos al almacén</p>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <span class="badge bg-success">Hoy: <?php echo date('d/m/Y'); ?></span>
                        </div>
                        <button class="btn btn-outline-primary">
                            <i class="fas fa-file-export me-1"></i>Exportar
                        </button>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-4" id="entradasTabs">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $tab_activa === 'nuevaEntrada' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#nuevaEntrada" style="color:black" onclick="cambiarTab('nuevaEntrada')">
                            <i class="fas fa-plus-circle me-1"></i>Nueva Entrada
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $tab_activa === 'historialEntradas' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#historialEntradas" style="color:black" onclick="cambiarTab('historialEntradas')">
                            <i class="fas fa-history me-1"></i>Historial
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade <?php echo $tab_activa === 'nuevaEntrada' ? 'show active' : ''; ?>" id="nuevaEntrada">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Registrar Nueva Entrada</h5>
                            </div>
                            <div class="card-body">
                                <form id="formEntrada" method="POST">
                                    <input type="hidden" name="tab" value="nuevaEntrada">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Proveedor: <span class="text-danger">*</span></label>
                                            <select class="form-select" id="proveedorSelect" name="proveedor" required onchange="filtrarProductosPorProveedor()">
                                                <option value="">Seleccione proveedor</option>
                                                <?php
                                                if($result1) {
                                                    while($row1 = pg_fetch_assoc($result1)){
                                                        echo "<option value='{$row1['cod_proveedor']}'>{$row1['razon_social']}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">N° Factura: (Opcional)</label>
                                            <input type="text" class="form-control" id="numeroFactura" name="numero_factura" placeholder="Ej: F001-1245">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tipo de Documento:</label>
                                            <input type="text" class="form-control" value="Factura" readonly style="background-color: #f8f9fa;">
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Productos de la Entrada</h6>
                                            <span class="badge bg-primary" id="contadorProductos">1 producto(s)</span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="25%">Producto</th>
                                                        <th width="10%">Cantidad (Cajas)</th>
                                                        <th width="10%">Unidades x Caja</th>
                                                        <th width="10%">Total Unidades</th>
                                                        <th width="15%">Precio por Caja (S/)</th>
                                                        <th width="15%">Total (S/)</th>
                                                        <th width="10%">Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="detallesEntrada">
                                                    <tr class="product-row">
                                                        <td>        
                                                            <select class="form-select product-select" name="productos[]" required onchange="cargarPrecioProducto(this)">
                                                                <option value="">Seleccione proveedor primero</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control cantidad-input" name="cantidades[]" value="1" min="1" required onchange="calcularTotalFila(this)">
                                                        </td>
                                                        <td class="unidades-caja text-center">0</td>
                                                        <td class="total-unidades text-center">0</td>
                                                        <td>
                                                            <input type="number" class="form-control precio-caja-input" name="precios_caja[]" value="0.00" step="0.01" min="0" required onchange="calcularTotalFila(this)">
                                                        </td>
                                                        <td class="total-producto">S/ 0.00</td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-danger action-btn" onclick="eliminarFila(this)">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </td>       
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="7" class="text-end">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="agregarFila()">
                                                                <i class="fas fa-plus me-1"></i>Agregar Producto
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr class="table-active total-row">
                                                        <td colspan="5" class="text-end"><strong>Total General:</strong></td>
                                                        <td colspan="2"><strong id="totalGeneral">S/ 0.00</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="mt-4 text-end">
                                        <button type="reset" class="btn btn-secondary me-2" onclick="resetForm()">Cancelar</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Registrar Entrada
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
    
                    <div class="tab-pane fade <?php echo $tab_activa === 'historialEntradas' ? 'show active' : ''; ?>" id="historialEntradas">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Entradas</h5>
                                    <div class="d-flex">
                                        <form method="GET" class="d-flex me-2" id="formBusqueda">
                                            <input type="hidden" name="tab" value="historialEntradas">
                                            <div class="search-box me-2 position-relative">
                                                <i class="fas fa-search position-absolute" style="left: 10px; top: 50%; transform: translateY(-50%); z-index: 10;"></i>
                                                <input type="text" class="form-control ps-4" placeholder="Buscar compras..." id="buscarHistorial" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
                                                <input type="hidden" name="filtro" value="<?php echo htmlspecialchars($filtro); ?>">
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search me-1"></i>Buscar
                                            </button>
                                        </form>
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-filter me-1"></i>
                                                <?php 
                                                $nombres_filtros = [
                                                    'todos' => 'Todos',
                                                    'hoy' => 'Hoy', 
                                                    'semana' => 'Esta semana',
                                                    'mes' => 'Este mes'
                                                ];
                                                echo $nombres_filtros[$filtro] ?? 'Filtrar';
                                                ?>
                                            </button>
                                            <ul class="dropdown-menu filter-dropdown" aria-labelledby="filterDropdown">
                                                <li><a class="dropdown-item <?php echo $filtro === 'todos' ? 'active' : ''; ?>" href="#" onclick="aplicarFiltro('todos')">Todos</a></li>
                                                <li><a class="dropdown-item <?php echo $filtro === 'hoy' ? 'active' : ''; ?>" href="#" onclick="aplicarFiltro('hoy')">Hoy</a></li>
                                                <li><a class="dropdown-item <?php echo $filtro === 'semana' ? 'active' : ''; ?>" href="#" onclick="aplicarFiltro('semana')">Esta semana</a></li>
                                                <li><a class="dropdown-item <?php echo $filtro === 'mes' ? 'active' : ''; ?>" href="#" onclick="aplicarFiltro('mes')">Este mes</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="tablaHistorial">
                                        <thead>
                                            <tr>
                                                <th>Código Compra</th>
                                                <th>Fecha</th>
                                                <th>Proveedor</th>
                                                <th>Total Productos</th>
                                                <th>Total Compra</th>
                                                <th>Método Pago</th>
                                                <th>Registrado por</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $total_compras = 0;
                                            if($result4 && pg_num_rows($result4) > 0) {
                                                while($row4 = pg_fetch_assoc($result4)){
                                                    $total_compras++;
                                                    echo "
                                                    <tr>
                                                        <td><strong>{$row4['cod_compra']}</strong></td>
                                                        <td>" . date('d/m/Y H:i', strtotime($row4['fecha'])) . "</td>
                                                        <td>{$row4['proveedor_nombre']}</td>
                                                        <td><span class='badge bg-info'>{$row4['total_productos']} productos</span></td>
                                                        <td><strong>S/ " . number_format($row4['total_compra'], 2) . "</strong></td>
                                                        <td>{$row4['metodo_pago']}</td>
                                                        <td>{$row4['usuario_registro']}</td>
                                                        <td>
                                                            <button class='btn btn-sm btn-outline-primary action-btn' title='Ver detalles' onclick='verDetallesCompra(\"{$row4['cod_compra']}\")'>
                                                                <i class='fas fa-eye'></i>
                                                            </button>
                                                            <button class='btn btn-sm btn-outline-success action-btn' title='Descargar PDF' onclick='generarPdf(\"{$row4['cod_compra']}\")'>
                                                                <i class='fas fa-file-pdf'></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    ";
                                                }
                                            } else {
                                                echo "<tr><td colspan='8' class='text-center py-4'>No hay compras registradas con productos " . 
                                                     (!empty($busqueda) ? "para la búsqueda '" . htmlspecialchars($busqueda) . "'" : "") . 
                                                     ($filtro !== 'todos' ? " en el período seleccionado" : "") . 
                                                     "</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Total: <?php echo $total_compras; ?> compra(s) con productos</strong>
                                    </div>
                                    <?php if(!empty($busqueda) || $filtro !== 'todos'): ?>
                                    <div>
                                        <a href="entradaproveedor.php?tab=historialEntradas" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>Limpiar filtros
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles de compra -->
    <div class="modal fade" id="modalDetallesCompra" tabindex="-1" aria-labelledby="modalDetallesCompraLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetallesCompraLabel">Detalles de Compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detallesCompraContent">
                        <!-- Aquí se cargarán los detalles dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="descargarPDF()">
                        <i class="fas fa-file-pdf me-1"></i>Descargar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Array con precios de productos (se llena con PHP)
        const preciosProductos = <?php echo json_encode($precios_productos); ?>;
        const unidadesPorCaja = <?php echo json_encode($unidades_por_caja); ?>;
        const productosPorProveedor = <?php echo json_encode($productos_por_proveedor); ?>;

        // NUEVA FUNCIÓN: Filtrar productos según el proveedor seleccionado
        function filtrarProductosPorProveedor() {
            const proveedorSelect = document.getElementById('proveedorSelect');
            const proveedorId = proveedorSelect.value;
            
            // Obtener todos los selects de productos
            const productSelects = document.querySelectorAll('.product-select');
            
            productSelects.forEach(select => {
                // Limpiar opciones actuales
                select.innerHTML = '<option value="">Seleccione producto</option>';
                
                if (proveedorId && productosPorProveedor[proveedorId]) {
                    // Agregar productos del proveedor seleccionado
                    productosPorProveedor[proveedorId].forEach(producto => {
                        const option = document.createElement('option');
                        option.value = producto.cod_producto;
                        option.textContent = producto.nombre;
                        option.setAttribute('data-precio', producto.precio_caja);
                        option.setAttribute('data-unidades', producto.unidades_por_caja);
                        select.appendChild(option);
                    });
                } else if (!proveedorId) {
                    select.innerHTML = '<option value="">Seleccione proveedor primero</option>';
                } else {
                    select.innerHTML = '<option value="">Este proveedor no tiene productos</option>';
                }
            });
            
            // Resetear precios y cálculos
            resetearCalculos();
        }

        function resetearCalculos() {
            document.querySelectorAll('.product-row').forEach(row => {
                row.querySelector('.precio-caja-input').value = '0.00';
                row.querySelector('.unidades-caja').textContent = '0';
                row.querySelector('.total-unidades').textContent = '0';
                row.querySelector('.total-producto').textContent = 'S/ 0.00';
            });
            calcularTotalGeneral();
        }

        // Funciones JavaScript para la interactividad
        function cargarPrecioProducto(selectElement) {
            const productoId = selectElement.value;
            const row = selectElement.closest('tr');
            const precioInput = row.querySelector('.precio-caja-input');
            const unidadesCell = row.querySelector('.unidades-caja');
            
            if (productoId && preciosProductos[productoId]) {
                precioInput.value = parseFloat(preciosProductos[productoId]).toFixed(2);
                unidadesCell.textContent = unidadesPorCaja[productoId] || 0;
                calcularTotalFila(selectElement);
            } else {
                precioInput.value = '0.00';
                unidadesCell.textContent = '0';
                row.querySelector('.total-unidades').textContent = '0';
                row.querySelector('.total-producto').textContent = 'S/ 0.00';
            }
        }

        function calcularTotalFila(inputElement) {
            const row = inputElement.closest('tr');
            const cantidadCajas = parseFloat(row.querySelector('.cantidad-input').value) || 0;
            const precioPorCaja = parseFloat(row.querySelector('.precio-caja-input').value) || 0;
            const productoSelect = row.querySelector('.product-select');
            const productoId = productoSelect.value;
            const totalUnidadesCell = row.querySelector('.total-unidades');
            
            // Calcular total basado en PRECIO POR CAJA (CORRECTO)
            const unidadesPorCajaProducto = unidadesPorCaja[productoId] || 1;
            const cantidadUnidades = cantidadCajas * unidadesPorCajaProducto;
            const total = cantidadCajas * precioPorCaja; // Total = cantidad_cajas * precio_por_caja
            
            // Actualizar todas las celdas
            totalUnidadesCell.textContent = cantidadUnidades;
            row.querySelector('.total-producto').textContent = 'S/ ' + total.toFixed(2);
            calcularTotalGeneral();
        }

        function calcularTotalGeneral() {
            let totalGeneral = 0;
            document.querySelectorAll('.product-row').forEach(row => {
                const totalTexto = row.querySelector('.total-producto').textContent;
                const total = parseFloat(totalTexto.replace('S/ ', '')) || 0;
                totalGeneral += total;
            });
            
            document.getElementById('totalGeneral').textContent = 'S/ ' + totalGeneral.toFixed(2);
        }

        function agregarFila() {
            const tbody = document.getElementById('detallesEntrada');
            const newRow = document.createElement('tr');
            newRow.className = 'product-row';
            newRow.innerHTML = `
                <td>        
                    <select class="form-select product-select" name="productos[]" required onchange="cargarPrecioProducto(this)">
                        <option value="">Seleccione proveedor primero</option>
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control cantidad-input" name="cantidades[]" value="1" min="1" required onchange="calcularTotalFila(this)">
                </td>
                <td class="unidades-caja text-center">0</td>
                <td class="total-unidades text-center">0</td>
                <td>
                    <input type="number" class="form-control precio-caja-input" name="precios_caja[]" value="0.00" step="0.01" min="0" required onchange="calcularTotalFila(this)">
                </td>
                <td class="total-producto">S/ 0.00</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger action-btn" onclick="eliminarFila(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(newRow);
            actualizarContadorProductos();
            
            // Si hay un proveedor seleccionado, cargar sus productos en la nueva fila
            const proveedorSelect = document.getElementById('proveedorSelect');
            if (proveedorSelect.value) {
                filtrarProductosPorProveedor();
            }
        }

        function eliminarFila(button) {
            const row = button.closest('tr');
            if (document.querySelectorAll('.product-row').length > 1) {
                row.remove();
                actualizarContadorProductos();
                calcularTotalGeneral();
            } else {
                alert('Debe haber al menos un producto en la entrada.');
            }
        }

        function actualizarContadorProductos() {
            const count = document.querySelectorAll('.product-row').length;
            document.getElementById('contadorProductos').textContent = count + ' producto(s)';
        }

        function resetForm() {
            document.getElementById('formEntrada').reset();
            // Resetear totales
            document.querySelectorAll('.product-row').forEach(row => {
                row.querySelector('.total-producto').textContent = 'S/ 0.00';
                row.querySelector('.unidades-caja').textContent = '0';
                row.querySelector('.total-unidades').textContent = '0';
            });
            document.getElementById('totalGeneral').textContent = 'S/ 0.00';
            actualizarContadorProductos();
            
            // Resetear selects de productos
            const productSelects = document.querySelectorAll('.product-select');
            productSelects.forEach(select => {
                select.innerHTML = '<option value="">Seleccione proveedor primero</option>';
            });
        }

        // Funciones para el filtrado del historial
        function aplicarFiltro(filtro) {
            const url = new URL(window.location.href);
            url.searchParams.set('filtro', filtro);
            url.searchParams.set('tab', 'historialEntradas');
            const busqueda = document.getElementById('buscarHistorial').value;
            if (busqueda) {
                url.searchParams.set('busqueda', busqueda);
            }
            window.location.href = url.toString();
        }

        function cambiarTab(tab) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            if (tab === 'nuevaEntrada') {
                url.searchParams.delete('filtro');
                url.searchParams.delete('busqueda');
            }
            window.location.href = url.toString();
        }

        function verDetallesCompra(codCompra) {
            // Mostrar loading
            document.getElementById('detallesCompraContent').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2">Cargando detalles de la compra...</p>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('modalDetallesCompra'));
            modal.show();
            
            fetch('obtener_detalles_compra.php?cod_compra=' + codCompra)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor: ' + response.status);
                    }
                    return response.text();
                })
                .then(data => {
                    document.getElementById('detallesCompraContent').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('detallesCompraContent').innerHTML = 
                        '<div class="alert alert-danger text-center">' +
                        '<i class="fas fa-exclamation-triangle fa-2x mb-3"></i>' +
                        '<h5>Error al cargar los detalles</h5>' +
                        '<p class="mb-0">' + error.message + '</p>' +
                        '</div>';
                });
        }

        function descargarPDF() {
            const element = document.getElementById('detallesCompraContent');
            html2pdf().from(element).save('detalle_compra.pdf');
        }

        function generarPdf(codCompra) {
            window.open('obtener_detalles_compra.php?cod_compra=' + codCompra + '&pdf=1', '_blank');
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            actualizarContadorProductos();
            calcularTotalGeneral();
            
            // Configurar búsqueda en tiempo real
            const buscarHistorial = document.getElementById('buscarHistorial');
            if (buscarHistorial) {
                buscarHistorial.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        document.getElementById('formBusqueda').submit();
                    }
                });
            }
        });

        // Configurar eventos del dropdown
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
    <!-- Incluir la librería html2pdf -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</body>
</html>