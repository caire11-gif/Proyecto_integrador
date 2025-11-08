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
    <?php
        $conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
        if(!$conexion){
            echo "Un error de conexión ocurrió.";
            exit;
        }

        session_start();
        $usuarioencargado=$_SESSION['nombreusuarioencargado'];
        $apellidoencargado=$_SESSION['apellidousuarioencargado'];

        $inicialNombre = substr($usuarioencargado, 0, 1);
        $inicialApellido=substr($apellidoencargado,0,1);

        if (!isset($_SESSION['nombreusuarioencargado'])) {
            header("Location: ../login.php");
            exit;
        }

        // Inicializar variables con valores por defecto
        $precios_productos = array();
        $unidades_por_caja = array();

        // Obtener parámetro de filtro si existe
        $filtro = $_GET['filtro'] ?? 'todos';
        $busqueda = $_GET['busqueda'] ?? '';
        $tab_activa = $_GET['tab'] ?? 'nuevaEntrada';

        try {
            // CORRECCIÓN: Obtener datos para los select - cambiar 'nombre' por 'razon_social'
            $result1 = pg_query($conexion, "SELECT cod_proveedor, razon_social FROM proveedor");
            if(!$result1){
                error_log("Error al cargar proveedores: " . pg_last_error($conexion));
            }

            $result2 = pg_query($conexion, "SELECT cod_tipodocumento, nombre FROM tipodocumento");
            if(!$result2){
                error_log("Error al cargar tipos de documento: " . pg_last_error($conexion));
            }

            // CORRECCIÓN: Obtener productos CON PRECIO DE CAJA (no precio_costo)
            $result3 = pg_query($conexion, "SELECT cod_producto, nombre, precio_caja, unidades_por_caja FROM producto");
            if(!$result3){
                error_log("Error al cargar productos: " . pg_last_error($conexion));
            } else {
                // Crear array con precios de productos (precio por caja)
                pg_result_seek($result3, 0);
                while($row = pg_fetch_assoc($result3)){
                    $precios_productos[$row['cod_producto']] = $row['precio_caja'];
                    $unidades_por_caja[$row['cod_producto']] = $row['unidades_por_caja'];
                }
            }

            // OBTENER CÓDIGOS DE TABLAS DE REFERENCIA
            // Métodos de pago
            $result_mp = pg_query($conexion, "SELECT cod_metodopago FROM metodopago LIMIT 1");
            if($result_mp && pg_num_rows($result_mp) > 0) {
                $row = pg_fetch_assoc($result_mp);
                $cod_metodopago = $row['cod_metodopago'];
            } else {
                $cod_metodopago = 'MP001'; // Valor por defecto más común
            }

            // Tipos de reporte
            $result_tr = pg_query($conexion, "SELECT cod_tiporeporte FROM tiporeporte LIMIT 1");
            if($result_tr && pg_num_rows($result_tr) > 0) {
                $row = pg_fetch_assoc($result_tr);
                $cod_tiporeporte = $row['cod_tiporeporte'];
            } else {
                $cod_tiporeporte = 'REP001'; // Valor por defecto más común
            }

            // Tipos de movimiento - CORRECCIÓN: Buscar uno que exista realmente
            $result_tm = pg_query($conexion, "SELECT cod_tipomovimiento FROM tipomovimiento LIMIT 1");
            if($result_tm && pg_num_rows($result_tm) > 0) {
                $row = pg_fetch_assoc($result_tm);
                $cod_tipomovimiento = $row['cod_tipomovimiento'];
            } else {
                // Si no hay registros, intentar insertar uno básico
                $cod_tipomovimiento = 'MOV001';
                $insert_tm = pg_query($conexion, "INSERT INTO tipomovimiento (cod_tipomovimiento, nombre) VALUES ('MOV001', 'Entrada')");
            }

            // CORRECCIÓN: Tipos de acción - Buscar uno que exista realmente
            $result_ta = pg_query($conexion, "SELECT cod_tipoaccion FROM tipoaccion LIMIT 1");
            if($result_ta && pg_num_rows($result_ta) > 0) {
                $row = pg_fetch_assoc($result_ta);
                $cod_tipoaccion = $row['cod_tipoaccion'];
            } else {
                // Si no hay registros, intentar insertar uno básico
                $cod_tipoaccion = 'ACC001';
                $insert_ta = pg_query($conexion, "INSERT INTO tipoaccion (cod_tipoaccion, nombre) VALUES ('ACC001', 'Registro')");
            }

            // Usuario
            $result_u = pg_query($conexion, "SELECT cod_usuario FROM usuario LIMIT 1");
            if($result_u && pg_num_rows($result_u) > 0) {
                $row = pg_fetch_assoc($result_u);
                $cod_usuario = $row['cod_usuario'];
            } else {
                $cod_usuario = 'USER001'; // Valor por defecto más común
            }

            // CORRECCIÓN: CONSULTA para historial - COMPRAS AGRUPADAS con filtros - cambiar pr.nombre por pr.razon_social
            $query_historial = "SELECT 
                                c.cod_compra,
                                c.fecha_compra AS fecha,
                                pr.razon_social AS proveedor_nombre,
                                u.usuario AS usuario_registro,
                                COUNT(dc.cod_detallecompra) AS total_productos,
                                SUM(dc.total) AS total_compra,
                                mp.nombre AS metodo_pago
                            FROM compra c
                            JOIN proveedor pr ON c.cod_proveedor = pr.cod_proveedor
                            JOIN usuario u ON c.cod_usuario = u.cod_usuario
                            JOIN metodopago mp ON c.cod_metodopago = mp.cod_metodopago
                            LEFT JOIN detallecompra dc ON c.cod_compra = dc.cod_compra
                            WHERE 1=1";

            // Aplicar filtros
            if ($filtro === 'hoy') {
                $query_historial .= " AND c.fecha_compra = CURRENT_DATE";
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
                                 ORDER BY c.fecha_compra DESC, c.cod_compra DESC";

            $result4 = pg_query($conexion, $query_historial);
            
            if(!$result4){
                error_log("Error al cargar historial: " . pg_last_error($conexion));
            }

        } catch (Exception $e) {
            error_log("Error en consultas iniciales: " . $e->getMessage());
        }

        // Función para generar códigos únicos de exactamente 10 caracteres
        function generarCodigo($prefijo) {
            $numero = str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);
            return substr($prefijo, 0, 3) . $numero;
        }

        // Función para obtener el siguiente ID secuencial de inventario
        function obtenerSiguienteIdInventario($conexion) {
            // Consultar el máximo ID actual en registroinventario
            $result = pg_query($conexion, "SELECT MAX(cod_inventario) as max_id FROM registroinventario");
            if ($result && pg_num_rows($result) > 0) {
                $row = pg_fetch_assoc($result);
                $max_id = $row['max_id'];
                
                if ($max_id && preg_match('/INV(\d+)/', $max_id, $matches)) {
                    // Si hay IDs existentes, incrementar el número
                    $next_number = intval($matches[1]) + 1;
                } else {
                    // Si no hay IDs existentes, empezar desde 1
                    $next_number = 1;
                }
                
                return 'INV' . $next_number;
            }
            
            // En caso de error, devolver INV1
            return 'INV1';
        }

        // Procesar el formulario cuando se envía
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Iniciar transacción
                pg_query($conexion, "BEGIN");
                
                $proveedor = $_POST['proveedor'] ?? '';
                $fecha_entrada = $_POST['fecha_entrada'] ?? '';
                $numero_factura = $_POST['numero_factura'] ?? '';
                $tipo_comprobante = $_POST['tipo_comprobante'] ?? '';
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

                // Obtener el siguiente ID de inventario secuencial
                $primer_id_inventario = obtenerSiguienteIdInventario($conexion);
                $numero_actual = intval(str_replace('INV', '', $primer_id_inventario));

                // 1. GENERAR UN SOLO CÓDIGO DE COMPRA PARA TODOS LOS PRODUCTOS
                $cod_compra = generarCodigo('COM');
                
                // 2. INSERTAR EN COMPRA (UNA SOLA VEZ)
                $query_compra = "INSERT INTO compra (cod_compra, cod_usuario, cod_proveedor, cod_metodopago, cod_tiporeporte, fecha_compra) 
                                VALUES ('$cod_compra', '$cod_usuario', '$proveedor', '$cod_metodopago', '$cod_tiporeporte', '$fecha_entrada')";
                
                if(!pg_query($conexion, $query_compra)) {
                    throw new Exception("Error al insertar compra: " . pg_last_error($conexion));
                }

                // 3. PROCESAR CADA PRODUCTO (MÚLTIPLES DETALLES PARA LA MISMA COMPRA)
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

                        // Generar código único para cada detalle
                        $cod_detallecompra = generarCodigo('DET' . $index);
                        
                        // USAR ID SECUENCIAL PARA INVENTARIO - incrementar para cada producto
                        $cod_inventario = 'INV' . $numero_actual;
                        $numero_actual++;
                        
                        $cod_movimiento = generarCodigo('MOV' . $index);
                        $cod_historial = generarCodigo('HIS' . $index);
                        
                        // 4. Insertar en detallecompra (con el MISMO cod_compra para todos)
                        // CORRECCIÓN: La tabla detallecompra tiene cantidad_unidades, no precio_unitario
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
                                // Si falla el historial, solo loguear el error pero no detener el proceso
                                error_log("Error al insertar historial (continuando sin historial): " . pg_last_error($conexion));
                            }
                        }
                    }
                }
                
                // Confirmar transacción
                pg_query($conexion, "COMMIT");
                
                echo "<script>
                    alert('Entrada registrada correctamente. Stock actualizado. Código de compra: $cod_compra');
                    window.location.href = 'entradaproveedor.php?tab=nuevaEntrada';
                </script>";
                
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                pg_query($conexion, "ROLLBACK");
                echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
            }
        }
    ?>

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
                    <a href="dashboard.php" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="gestionproductos.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Gestión de Productos</ul></a>
                    <a href="almacenproveedores.php" class="nav-link"><ul><i class="fas fa-truck"></i>Proveedores</ul></a>
                    <a href="entradaproveedor.php" class="nav-link active"><ul><i class="fas fa-truck-loading"></i>Entradas Proveedor</ul></a>
                    <a href="notificaciones.php" class="nav-link"><ul><i class="fas fa-bell"></i>Notificaciones</ul></a>
                    <a href="reportes.php" class="nav-link"><ul><i class="fas fa-chart-bar"></i>Reportes</ul></a>
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
                                <a href="../login.php" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
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
                                            <select class="form-select" id="proveedorSelect" name="proveedor" required>
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
                                            <label class="form-label">Fecha de Entrada: <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="fechaEntrada" name="fecha_entrada" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">N° Factura/Comprobante: (Opcional)</label>
                                            <input type="text" class="form-control" id="numeroFactura" name="numero_factura" placeholder="Ej: F001-1245">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tipo de Documento:</label>
                                            <select class="form-select" id="tipoComprobante" name="tipo_comprobante">
                                                <option value="">Seleccione documento</option>
                                                <?php
                                                if($result2) {
                                                    while($row2 = pg_fetch_assoc($result2)){
                                                        echo "<option value='{$row2['cod_tipodocumento']}'>{$row2['nombre']}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
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
                                                                <option value="">Seleccione producto</option>
                                                                <?php
                                                                if($result3) {
                                                                    pg_result_seek($result3, 0);
                                                                    while($row3 = pg_fetch_assoc($result3)){
                                                                        echo "<option value='{$row3['cod_producto']}' data-precio='{$row3['precio_caja']}' data-unidades='{$row3['unidades_por_caja']}'>{$row3['nombre']}</option>";
                                                                    }
                                                                }
                                                                ?>
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
                                        <button type="submit" class="btn btn-mad">
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
                                            if($result4 && pg_num_rows($result4) > 0) {
                                                $total_compras = 0;
                                                while($row4 = pg_fetch_assoc($result4)){
                                                    $total_compras++;
                                                    echo "
                                                    <tr>
                                                        <td><strong>{$row4['cod_compra']}</strong></td>
                                                        <td>{$row4['fecha']}</td>
                                                        <td>{$row4['proveedor_nombre']}</td>
                                                        <td><span class='badge bg-info'>{$row4['total_productos']} productos</span></td>
                                                        <td><strong>S/ " . number_format($row4['total_compra'], 2) . "</strong></td>
                                                        <td>{$row4['metodo_pago']}</td>
                                                        <td>{$row4['usuario_registro']}</td>
                                                        <td>
                                                            <button class='btn btn-sm btn-outline-primary action-btn' title='Ver detalles' onclick='verDetallesCompra(\"{$row4['cod_compra']}\")'>
                                                                <i class='fas fa-eye'></i>
                                                            </button>
                                                            <button class='btn btn-sm btn-outline-success action-btn' title='Descargar PDF'>
                                                                <i class='fas fa-file-pdf'></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    ";
                                                }
                                            } else {
                                                echo "<tr><td colspan='8' class='text-center py-4'>No hay compras registradas " . 
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
                                        <strong>Total: <?php echo $total_compras ?? 0; ?> compra(s)</strong>
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
        <div class="modal-dialog modal-lg">
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
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Array con precios de productos (se llena con PHP)
        const preciosProductos = <?php echo json_encode($precios_productos); ?>;
        const unidadesPorCaja = <?php echo json_encode($unidades_por_caja); ?>;

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
                        <option value="">Seleccione producto</option>
                        <?php
                        if($result3) {
                            pg_result_seek($result3, 0);
                            while($row3 = pg_fetch_assoc($result3)){
                                echo "<option value='{$row3['cod_producto']}' data-precio='{$row3['precio_caja']}' data-unidades='{$row3['unidades_por_caja']}'>{$row3['nombre']}</option>";
                            }
                        }
                        ?>
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
            fetch('obtener_detalles_compra.php?cod_compra=' + codCompra)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('detallesCompraContent').innerHTML = data;
                    const modal = new bootstrap.Modal(document.getElementById('modalDetallesCompra'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('detallesCompraContent').innerHTML = '<p>Error al cargar los detalles</p>';
                    const modal = new bootstrap.Modal(document.getElementById('modalDetallesCompra'));
                    modal.show();
                });
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            actualizarContadorProductos();
            calcularTotalGeneral();
            
            // Cargar precios iniciales para la primera fila
            const primeraFila = document.querySelector('.product-row');
            if (primeraFila) {
                cargarPrecioProducto(primeraFila.querySelector('.product-select'));
            }

            // Configurar búsqueda en tiempo real
            const buscarHistorial = document.getElementById('buscarHistorial');
            if (buscarHistorial) {
                buscarHistorial.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        document.getElementById('formBusqueda').submit();
                    }
                });
            }

            // Validación de fecha
            document.getElementById("formEntrada").addEventListener("submit", function(event){
                const fechaentrada = document.getElementById("fechaEntrada").value.trim();
                const hoy = new Date();
                const fecha = new Date(fechaentrada);

                hoy.setHours(0,0,0,0);
                fecha.setHours(0,0,0,0);

                if(fecha > hoy){
                    alert("La fecha no puede ser superior a la de hoy");
                    event.preventDefault();
                    return;
                }
            });
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
</body>
</html>