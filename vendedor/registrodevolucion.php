<?php
session_start();

$conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");

if(!$conexion){
    echo "Un error de conexión ocurrió. <br>";
    exit;
}

// FUNCIÓN PARA VERIFICAR SI UN PRODUCTO YA FUE SOLUCIONADO
function productoYaSolucionado($conexion, $cod_producto, $cod_comprobante) {
    $query = "SELECT COUNT(*) as count 
              FROM historialproductos 
              WHERE cod_producto = '$cod_producto' 
              AND observacion LIKE '%Producto solucionado%' 
              AND observacion LIKE '%Comprobante: $cod_comprobante%'";
    
    $result = pg_query($conexion, $query);
    if($result) {
        $count = pg_fetch_assoc($result)['count'];
        return $count > 0;
    }
    return false;
}

// MOSTRAR MENSAJE DE VENTA EXITOSA
if(isset($_SESSION['venta_exitosa']) && $_SESSION['venta_exitosa']) {
    $comprobante = $_SESSION['comprobante'];
    echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>
            <i class='fas fa-check-circle'></i> <strong>¡Venta exitosa!</strong> 
            Se generó {$comprobante['nombre']} {$comprobante['serie']}-{$comprobante['numero']} por S/ {$comprobante['total']}
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
    unset($_SESSION['venta_exitosa']);
    unset($_SESSION['comprobante']);
}

// FUNCIÓN SIMPLIFICADA PARA OBTENER DETALLES DE VENTA - SIN FILTRAR
function obtenerDetallesVenta($conexion, $cod_comprobante) {
    // Primero obtener el código de venta desde el reporte
    $queryVenta = "SELECT v.cod_venta 
                   FROM venta v 
                   JOIN reporte r ON r.datos_reporte LIKE '%' || v.cod_venta || '%'
                   WHERE r.cod_tipodocumento = '$cod_comprobante'
                   LIMIT 1";
    
    $resultVenta = pg_query($conexion, $queryVenta);
    
    if(!$resultVenta || pg_num_rows($resultVenta) === 0) {
        return [];
    }
    
    $ventaData = pg_fetch_assoc($resultVenta);
    $cod_venta = $ventaData['cod_venta'];
    
    // Ahora obtener los detalles de la venta usando el código de venta
    $query = "SELECT 
                td.cod_tipodocumento,
                td.nombre as documento_nombre,
                td.serie,
                td.numero,
                v.cod_venta,
                v.fecha_venta,
                -- CALCULAR TOTAL SUMANDO SUBTOTALES DE DETALLEVENTA
                (SELECT SUM(dv.total) FROM detalleventa dv WHERE dv.cod_venta = v.cod_venta) as total_venta,
                v.cod_metodopago,
                dv.cod_detalleventa,
                dv.cod_producto,
                dv.cantidad_unidades,
                dv.precio_unitario,
                dv.total as subtotal_producto,
                p.nombre as producto_nombre,
                p.precio_venta,
                p.stock,
                mp.nombre as metodo_pago
             FROM venta v
             JOIN detalleventa dv ON v.cod_venta = dv.cod_venta
             JOIN producto p ON dv.cod_producto = p.cod_producto
             JOIN metodopago mp ON v.cod_metodopago = mp.cod_metodopago
             JOIN reporte r ON r.datos_reporte LIKE '%' || v.cod_venta || '%'
             JOIN tipodocumento td ON r.cod_tipodocumento = td.cod_tipodocumento
             WHERE v.cod_venta = '$cod_venta' 
             AND td.cod_tipodocumento = '$cod_comprobante'";
    
    $result = pg_query($conexion, $query);
    
    if($result && pg_num_rows($result) > 0) {
        return pg_fetch_all($result);
    }
    return [];
}

// FUNCIÓN PARA PROCESAR PRODUCTO SOLUCIONADO - AUMENTAR STOCK
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'producto_solucionado') {
    $cod_producto = pg_escape_string($conexion, $_POST['cod_producto']);
    $cantidad = intval($_POST['cantidad']);
    $cod_comprobante = pg_escape_string($conexion, $_POST['cod_comprobante']);
    
    // Iniciar transacción
    pg_query($conexion, "BEGIN");
    
    try {
        // 1. ✅ AUMENTAR stock del producto (REPUESTO)
        $queryUpdateStock = "UPDATE producto SET stock = stock + $cantidad WHERE cod_producto = '$cod_producto'";
        $resultUpdate = pg_query($conexion, $queryUpdateStock);
        
        if(!$resultUpdate) {
            throw new Exception("Error al actualizar stock");
        }
        
        // 2. Registrar movimiento de inventario
        $timestamp = substr(time(), -6);
        $cod_movimiento = 'MOV' . $timestamp;
        $queryMovimiento = "INSERT INTO movimiento (cod_movimiento, cod_producto, cod_tipomovimiento, fecha_movimiento, cod_usuario, observacion) 
                            VALUES ('$cod_movimiento', '$cod_producto', 'mov001', CURRENT_DATE, 'user001', 'Repuesto/Solución - Comprobante: $cod_comprobante - Stock aumentado: +$cantidad unidades')";
        pg_query($conexion, $queryMovimiento);
        
        // 3. Registrar en historial
        $cod_historial = 'H' . $timestamp;
        $queryHistorial = "INSERT INTO historialproductos (cod_historialproductos, cod_usuario, cod_producto, cod_tipoaccion, observacion) 
                           VALUES ('$cod_historial', 'user001', '$cod_producto', 'acc001', 'Producto solucionado como repuesto - Aumento stock: +$cantidad unidades - Comprobante: $cod_comprobante')";
        pg_query($conexion, $queryHistorial);
        
        // Confirmar transacción
        pg_query($conexion, "COMMIT");
        
        echo json_encode(['success' => true, 'message' => "✅ Producto marcado como solucionado. Stock aumentado en $cantidad unidades."]);
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        pg_query($conexion, "ROLLBACK");
        echo json_encode(['success' => false, 'message' => '❌ Error: ' . $e->getMessage()]);
    }
    exit;
}

// OBTENER TODOS LOS COMPROBANTES DE VENTA (CONSULTA MEJORADA)
$queryComprobantes = "SELECT DISTINCT
                        td.cod_tipodocumento,
                        td.nombre as documento_nombre,
                        td.serie,
                        td.numero,
                        r.fecha_reporte,
                        -- CALCULAR TOTAL DESDE DETALLEVENTA
                        (SELECT SUM(dv.total) 
                         FROM venta v2 
                         JOIN detalleventa dv ON v2.cod_venta = dv.cod_venta 
                         WHERE r.datos_reporte LIKE '%' || v2.cod_venta || '%') as total_venta,
                        (SELECT v3.cod_venta FROM venta v3 
                         WHERE r.datos_reporte LIKE '%' || v3.cod_venta || '%' 
                         LIMIT 1) as cod_venta
                      FROM tipodocumento td
                      JOIN reporte r ON td.cod_tipodocumento = r.cod_tipodocumento
                      WHERE r.datos_reporte LIKE '%Venta%'
                      ORDER BY r.fecha_reporte DESC";

$resultComprobantes = pg_query($conexion, $queryComprobantes);
$comprobantes = [];
if($resultComprobantes) {
    $comprobantes = pg_fetch_all($resultComprobantes);
}

// PROCESAR BÚSQUEDA
$resultadosBusqueda = [];
if(isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $termino = pg_escape_string($conexion, $_GET['buscar']);
    $queryBusqueda = "SELECT DISTINCT
                        td.cod_tipodocumento,
                        td.nombre as documento_nombre,
                        td.serie,
                        td.numero,
                        r.fecha_reporte,
                        -- CALCULAR TOTAL DESDE DETALLEVENTA
                        (SELECT SUM(dv.total) 
                         FROM venta v2 
                         JOIN detalleventa dv ON v2.cod_venta = dv.cod_venta 
                         WHERE r.datos_reporte LIKE '%' || v2.cod_venta || '%') as total_venta,
                        (SELECT v3.cod_venta FROM venta v3 
                         WHERE r.datos_reporte LIKE '%' || v3.cod_venta || '%' 
                         LIMIT 1) as cod_venta
                      FROM tipodocumento td
                      JOIN reporte r ON td.cod_tipodocumento = r.cod_tipodocumento
                      WHERE r.datos_reporte LIKE '%Venta%'
                      AND (td.nombre ILIKE '%$termino%' 
                         OR td.cod_tipodocumento ILIKE '%$termino%'
                         OR td.serie ILIKE '%$termino%'
                         OR CAST(td.numero AS TEXT) ILIKE '%$termino%'
                         OR r.datos_reporte ILIKE '%$termino%')
                      ORDER BY r.fecha_reporte DESC";
    
    $resultBusqueda = pg_query($conexion, $queryBusqueda);
    if($resultBusqueda) {
        $resultadosBusqueda = pg_fetch_all($resultBusqueda);
    }
}

// API para obtener detalles de venta
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'obtener_detalles_venta') {
    $cod_comprobante = pg_escape_string($conexion, $_POST['cod_comprobante']);
    $detalles = obtenerDetallesVenta($conexion, $cod_comprobante);
    
    header('Content-Type: application/json');
    echo json_encode($detalles);
    exit;
}

// API para obtener información básica del comprobante
if(isset($_GET['cod_comprobante'])) {
    $cod_comprobante = pg_escape_string($conexion, $_GET['cod_comprobante']);
    
    $query = "SELECT 
                td.cod_tipodocumento,
                td.nombre as documento_nombre,
                td.serie,
                td.numero,
                r.fecha_reporte,
                -- CALCULAR TOTAL DESDE DETALLEVENTA
                (SELECT SUM(dv.total) 
                 FROM venta v2 
                 JOIN detalleventa dv ON v2.cod_venta = dv.cod_venta 
                 WHERE r.datos_reporte LIKE '%' || v2.cod_venta || '%') as total_venta,
                (SELECT v3.cod_venta FROM venta v3 
                 WHERE r.datos_reporte LIKE '%' || v3.cod_venta || '%' 
                 LIMIT 1) as cod_venta,
                (SELECT v4.fecha_venta FROM venta v4 
                 WHERE r.datos_reporte LIKE '%' || v4.cod_venta || '%' 
                 LIMIT 1) as fecha_venta
              FROM tipodocumento td
              JOIN reporte r ON td.cod_tipodocumento = r.cod_tipodocumento
              WHERE td.cod_tipodocumento = '$cod_comprobante'
              LIMIT 1";
    
    $result = pg_query($conexion, $query);
    $comprobante = pg_fetch_all($result);
    
    header('Content-Type: application/json');
    echo json_encode($comprobante);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Comprobantes de Venta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/vendedor-estilo.css">
    <style>
        .producto-devolucion {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #f8f9fa;
            margin-bottom: 10px;
        }

        .producto-info {
            flex: 1;
        }

        .producto-acciones {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-end;
            min-width: 150px;
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            white-space: nowrap;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
            color: white;
        }

        .devoluciones-main {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .buscar-venta, .info-venta {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .busqueda-venta {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .busqueda-input {
            display: flex;
            gap: 10px;
        }

        .busqueda-input input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .venta-detalle {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .productos-venta {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }

        .resultados-busqueda {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 10px;
            background: #f8f9fa;
        }

        .resultado-item {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .resultado-item:hover {
            background: #3498db;
            color: white;
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
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .total-badge {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 10px;
        }

        .resumen-venta {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border: 1px solid #d4edda;
        }
    </style>
</head>
<body>
    
    <div class="grid">
        <!-- BARRA LATERAL COMPLETA -->
        <main class="principal">
            <button class="boton-menu" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>

            <div class="barra-lateral" id="barra-lateral">
                <div class="logo">
                    <h4><i class="fas fa-store"></i> MAD MARKET</h4>
                    <small id="userRole">Vendedor</small>
                </div>

                <div class="turno-info">
                    <div class="fw-bold">Carlos Rodríguez</div>
                    <small>Turno: 08:00 - 16:00</small><br>
                    <small id="tiempoActivoSidebar">0h 0m activo</small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.php" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="nuevaventa.php" class="nav-link"><ul><i class="fas fa-cash-register"></i>Nueva Venta</ul></a>
                    <a href="registrodevolucion.php" class="nav-link active"><ul><i class="fas fa-undo-alt"></i>Registrar Devolución</ul></a>
                    <a href="boletas-facturas.php" class="nav-link"><ul><i class="fas fa-receipt"></i>Boletas/Facturas</ul></a>
                    <a href="consulta-stock.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Consulta-stock</ul></a>
                    <a href="../login.html" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
                </div>
            </div>
        </main>

        <div class="secundario">
            <div class="header">
                <div class="caja-busqueda">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Buscar comprobantes..." id="globalSearch">
                </div>
                
                <div class="usuario-info">
                    <div class="usuario-avatar" id="usuarioAvatar">CR</div>
                    <div>
                        <div class="fw-bold fs-5" id="userName">Carlos Rodríguez</div>
                        <small class="text-muted" id="userPosition">Vendedor - Turno Activo</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger ms-3" onclick="cerrarTurno()">
                        <i class="fas fa-sign-out-alt me-1"></i>Cerrar Turno
                    </button>
                </div>
            </div>

            <main class="devoluciones-main">
                
                <section class="buscar-venta">
                    <h3><i class="fas fa-search"></i> Comprobantes de Venta</h3>
                    <div class="busqueda-venta">
                        <form method="GET" action="">
                            <div class="busqueda-input">
                                <input type="text" name="buscar" id="inputBusquedaVenta" 
                                       placeholder="Buscar por número, serie, código..." 
                                       value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>" 
                                       autofocus>
                                <button type="submit" id="btnBuscarVenta" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </form>

                        <div class="resultados-busqueda" id="resultadosBusqueda">
                            <?php 
                            $documentosMostrar = isset($_GET['buscar']) && !empty($_GET['buscar']) ? $resultadosBusqueda : $comprobantes;
                            
                            if(!empty($documentosMostrar)): ?>
                                <h5><?php echo isset($_GET['buscar']) ? 'Resultados de búsqueda' : 'Todos los Comprobantes'; ?></h5>
                                <?php foreach($documentosMostrar as $documento): ?>
                                   <div class="resultado-item" onclick="debugDetallesVenta('<?php echo $documento['cod_tipodocumento']; ?>'); mostrarDetallesVenta('<?php echo $documento['cod_tipodocumento']; ?>')">
                                        <div class="documento-info">
                                            <div class="documento-datos">
                                                <strong>
                                                    <?php echo $documento['documento_nombre']; ?>
                                                    <?php if($documento['total_venta']): ?>
                                                        <span class="total-badge">S/ <?php echo number_format($documento['total_venta'], 2); ?></span>
                                                    <?php endif; ?>
                                                </strong>
                                                <br>
                                                <small>Serie: <?php echo $documento['serie']; ?> - Número: <?php echo $documento['numero']; ?></small>
                                                <br>
                                                <small>Código: <?php echo $documento['cod_tipodocumento']; ?></small>
                                                <?php if($documento['cod_venta']): ?>
                                                    <br>
                                                    <small>Venta: <?php echo $documento['cod_venta']; ?></small>
                                                <?php endif; ?>
                                                <br>
                                                <small>Fecha: <?php echo date('d/m/Y H:i', strtotime($documento['fecha_reporte'])); ?></small>
                                            </div>
                                            <div class="documento-numero">
                                                <i class="fas fa-receipt"></i>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-search fa-2x mb-2"></i>
                                    <p>No se encontraron comprobantes de venta</p>
                                    <small>Realiza una venta en "Nueva Venta" para generar comprobantes</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="info-venta" id="seccionInfoVenta" style="display: none;">
                    <h3><i class="fas fa-file-invoice"></i> Detalles de la Venta</h3>
                    <div class="venta-detalle" id="detalleVenta">
                        <!-- Información aparecerá aquí via JavaScript -->
                    </div>
                </section>

            </main>
        </div>
    </div>

<script>
// FUNCIÓN TEMPORAL PARA DEBUG
function debugDetallesVenta(cod_comprobante) {
    console.log('🔍 Debug: Cargando detalles para comprobante:', cod_comprobante);
    
    // Mostrar loading
    document.getElementById('seccionInfoVenta').style.display = 'block';
    document.getElementById('detalleVenta').innerHTML = `
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Cargando detalles de la venta...</p>
            <small>Comprobante: ${cod_comprobante}</small>
        </div>
    `;
}

// FUNCIÓN PARA MOSTRAR DETALLES DE VENTA
async function mostrarDetallesVenta(cod_comprobante) {
    console.log('🔄 Iniciando mostrarDetallesVenta para:', cod_comprobante);
    
    document.getElementById('seccionInfoVenta').style.display = 'block';
    document.getElementById('detalleVenta').innerHTML = `
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Cargando detalles de la venta...</p>
            <small>Comprobante: ${cod_comprobante}</small>
        </div>
    `;

    try {
        const formData = new FormData();
        formData.append('accion', 'obtener_detalles_venta');
        formData.append('cod_comprobante', cod_comprobante);
        
        console.log('📤 Enviando solicitud para:', cod_comprobante);
        
        const response = await fetch('registrodevolucion.php', {
            method: 'POST',
            body: formData
        });
        
        console.log('📥 Respuesta recibida, status:', response.status);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const detallesVenta = await response.json();
        console.log('📊 Datos recibidos:', detallesVenta);
        
        if (!detallesVenta || detallesVenta.length === 0) {
            console.log('❌ No se encontraron detalles');
            document.getElementById('detalleVenta').innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    No se encontraron detalles de venta para este comprobante
                    <br><small>Código: ${cod_comprobante}</small>
                    <br><small>Posible causa: No hay datos de venta asociados</small>
                </div>
            `;
            return;
        }

        console.log('✅ Datos encontrados, procesando...');
        const primeraVenta = detallesVenta[0];
        let productosHTML = '';
        let totalVenta = 0;
        
        // MOSTRAR TODOS los productos
        detallesVenta.forEach(detalle => {
            // Verificar si el producto YA está solucionado
            const yaSolucionado = productoYaSolucionadoEnFrontend(detalle.cod_producto, cod_comprobante);
            
            productosHTML += `
                <div class="producto-devolucion" id="producto-${detalle.cod_producto}-${cod_comprobante}">
                    <div class="producto-info">
                        <strong>${detalle.producto_nombre}</strong>
                        <br>
                        <small>Código: ${detalle.cod_producto}</small>
                        <br>
                        <small>Precio: S/ ${parseFloat(detalle.precio_unitario).toFixed(2)}</small>
                        <br>
                        <small>Cantidad: ${detalle.cantidad_unidades} unidades</small>
                        <br>
                        <small>Subtotal: S/ ${parseFloat(detalle.subtotal_producto).toFixed(2)}</small>
                        <br>
                        <small>Stock actual: ${detalle.stock} unidades</small>
                        ${yaSolucionado ? '<br><span class="badge bg-success">✅ Ya Solucionado</span>' : ''}
                    </div>
                    <div class="producto-acciones">
                        ${!yaSolucionado ? `
                            <button class="btn btn-success btn-sm" 
                                    onclick="marcarProductoSolucionado('${detalle.cod_producto}', ${detalle.cantidad_unidades}, '${cod_comprobante}', this)">
                                <i class="fas fa-check-circle"></i> Producto Solucionado
                            </button>
                        ` : `
                            <button class="btn btn-secondary btn-sm" disabled>
                                <i class="fas fa-check"></i> Ya Solucionado
                            </button>
                        `}
                    </div>
                </div>
            `;
            totalVenta += parseFloat(detalle.subtotal_producto);
        });

        document.getElementById('detalleVenta').innerHTML = `
            <div class="resumen-venta">
                <h5>Información del Comprobante</h5>
                <p><strong>Documento:</strong> ${primeraVenta.documento_nombre}</p>
                <p><strong>Código Comprobante:</strong> ${primeraVenta.cod_tipodocumento}</p>
                <p><strong>Serie:</strong> ${primeraVenta.serie}</p>
                <p><strong>Número:</strong> ${primeraVenta.numero}</p>
                <p><strong>Código Venta:</strong> ${primeraVenta.cod_venta}</p>
                <p><strong>Fecha de Venta:</strong> ${new Date(primeraVenta.fecha_venta).toLocaleDateString('es-PE')}</p>
                <p><strong>Método de Pago:</strong> ${primeraVenta.metodo_pago}</p>
                <p><strong>Total Venta:</strong> S/ ${parseFloat(primeraVenta.total_venta).toFixed(2)}</p>
                <p><strong>Productos en la Venta:</strong> ${detallesVenta.length}</p>
            </div>
            <div class="productos-venta">
                <h5>📦 Productos de la Venta:</h5>
                ${productosHTML}
            </div>
        `;

        console.log('✅ Detalles mostrados correctamente');

    } catch (error) {
        console.error('❌ Error al cargar detalles:', error);
        document.getElementById('detalleVenta').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-times-circle"></i>
                Error al cargar los detalles de la venta
                <br><small>Error: ${error.message}</small>
                <br><small>Comprobante: ${cod_comprobante}</small>
            </div>
        `;
    }
}

// ✅ FUNCIÓN PARA VERIFICAR SI UN PRODUCTO YA ESTÁ SOLUCIONADO (en frontend)
function productoYaSolucionadoEnFrontend(cod_producto, cod_comprobante) {
    const clave = `solucionado-${cod_producto}-${cod_comprobante}`;
    return localStorage.getItem(clave) === 'true';
}

// ✅ FUNCIÓN PARA MARCAR PRODUCTO COMO SOLUCIONADO
async function marcarProductoSolucionado(cod_producto, cantidad, cod_comprobante, boton) {
    if(!confirm(`¿Estás seguro de marcar este producto como solucionado?\n\nSe AUMENTARÁ el stock en ${cantidad} unidades.`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('accion', 'producto_solucionado');
        formData.append('cod_producto', cod_producto);
        formData.append('cantidad', cantidad);
        formData.append('cod_comprobante', cod_comprobante);
        
        const response = await fetch('registrodevolucion.php', {
            method: 'POST',
            body: formData
        });
        
        const resultado = await response.json();
        
        if(resultado.success) {
            alert(resultado.message);
            
            // ✅ MARCAR EN LOCALSTORAGE que ya está solucionado
            const clave = `solucionado-${cod_producto}-${cod_comprobante}`;
            localStorage.setItem(clave, 'true');
            
            // ✅ REMOVER el producto del DOM con animación
            const productoElement = boton.closest('.producto-devolucion');
            if (productoElement) {
                productoElement.style.opacity = '0.5';
                productoElement.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    productoElement.remove();
                    
                    // Mostrar mensaje si no quedan más productos
                    const productosRestantes = document.querySelectorAll('.producto-devolucion');
                    if (productosRestantes.length === 0) {
                        document.getElementById('detalleVenta').innerHTML += `
                            <div class="alert alert-success mt-3">
                                <i class="fas fa-check-circle"></i>
                                <strong>¡Todos los productos han sido solucionados!</strong>
                            </div>
                        `;
                    }
                }, 500);
            }
            
        } else {
            alert(resultado.message);
        }
        
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Error al procesar la solicitud');
    }
}

function cerrarTurno() {
    if(confirm('¿Estás seguro de que deseas cerrar el turno?')) {
        window.location.href = '../login.html';
    }
}

// Búsqueda en tiempo real
document.getElementById('globalSearch').addEventListener('input', function() {
    const termino = this.value.toLowerCase();
    const items = document.querySelectorAll('.resultado-item');
    
    items.forEach(item => {
        const texto = item.textContent.toLowerCase();
        item.style.display = texto.includes(termino) ? 'block' : 'none';
    });
});
</script>
</body>
</html>