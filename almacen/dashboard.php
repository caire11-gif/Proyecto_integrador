<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Sistema de Encargado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/almacen-dashboard/datos.css">
    <link rel="stylesheet" href="css/almacen-interfaz/interfaz.css">
    <link rel="stylesheet" href="css/almacen-boton/boton.css">
</head>
<body>
    <?php
        $conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
        if(!$conexion){
            echo "Un error de conexión ocurrió.";
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

        // 1. Contar productos totales
        $result1 = pg_query($conexion, "SELECT COUNT(cod_producto) AS cantidad_producto FROM producto");
        if(!$result1){
            echo "Error al contar los productos.";
        }

        // 2. Contar categorías
        $result2 = pg_query($conexion, "SELECT COUNT(cod_categoria) AS cantidad_categoria FROM categoria");
        if(!$result2){
            echo "Error al contar las categorías.";
        }

        // 3. Contar entradas este mes (desde detallecompra)
        $result3 = pg_query($conexion, "SELECT COUNT(DISTINCT dc.cod_compra) AS entradas_mes 
                                       FROM detallecompra dc
                                       JOIN compra c ON dc.cod_compra = c.cod_compra
                                       WHERE c.fecha_compra >= DATE_TRUNC('month', CURRENT_DATE)");
        if(!$result3){
            echo "Error al contar entradas del mes.";
        }

        // 4. Contar alertas activas (notificaciones pendientes)
        $result4 = pg_query($conexion, "SELECT COUNT(*) AS alertas_activas 
                                       FROM notificacion 
                                       WHERE cod_estadonotificacion IN ('en001', 'en002')");
        if(!$result4){
            echo "Error al contar alertas activas.";
        }

        // 5. Contar alertas urgentes (stock menor a 3)
        $result5 = pg_query($conexion, "SELECT COUNT(*) AS alertas_urgentes 
                                       FROM producto 
                                       WHERE stock < 3");
        if(!$result5){
            echo "Error al contar alertas urgentes.";
        }

        // 6. Movimientos recientes (últimos 3)
        $result6 = pg_query($conexion, "SELECT m.fecha_movimiento, p.nombre as producto_nombre, 
                                               tm.nombre as tipo_movimiento, m.observacion
                                        FROM movimiento m
                                        JOIN producto p ON m.cod_producto = p.cod_producto
                                        JOIN tipomovimiento tm ON m.cod_tipomovimiento = tm.cod_tipomovimiento
                                        ORDER BY m.fecha_movimiento DESC 
                                        LIMIT 3");
        if(!$result6){
            echo "Error al obtener movimientos recientes.";
        }

        // 7. Alertas de stock urgentes (3 más urgentes)
        $result7 = pg_query($conexion, "SELECT p.nombre as producto_nombre, p.stock, 
                                               c.nombre as categoria_nombre,
                                               pr.nombre as proveedor_nombre
                                        FROM producto p
                                        JOIN categoria c ON p.cod_categoria = c.cod_categoria
                                        JOIN proveedor pr ON p.cod_proveedor = pr.cod_proveedor
                                        WHERE p.stock < 10
                                        ORDER BY p.stock ASC 
                                        LIMIT 3");
        if(!$result7){
            echo "Error al obtener alertas urgentes.";
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
                    <a href="dashboard.php" class="nav-link active"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="gestionproductos.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Gestión de Productos</ul></a>
                    <a href="almacenproveedores.php" class="nav-link"><ul><i class="fas fa-truck"></i>Proveedores</ul></a>
                    <a href="entradaproveedor.php" class="nav-link"><ul><i class="fas fa-truck-loading"></i>Entradas Proveedor</ul></a>
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

            <div class="container-fluid">
                <div class="row mb-4">
                    <!-- Tarjeta 1: Productos en Almacén -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card primary h-100">
                            <i class="fas fa-boxes text-primary"></i>
                            <?php
                            if($row1 = pg_fetch_assoc($result1)){
                                echo "<div class='number'>{$row1['cantidad_producto']}</div>";
                            } else {
                                echo "<div class='number'>0</div>";
                            }
                            ?>
                            <div class="label">Productos en Almacén</div>
                            <?php
                            if($row2 = pg_fetch_assoc($result2)){
                                $categoria_text = ($row2['cantidad_categoria'] == 1) ? "categoría" : "categorías";
                                echo "<div class='text-muted'>{$row2['cantidad_categoria']} {$categoria_text}</div>";
                            } else {
                                echo "<div class='text-muted'>0 categorías</div>";
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Tarjeta 2: Entradas Este Mes (desde compras) -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card success h-100">
                            <i class="fas fa-truck-loading text-success"></i>
                            <?php
                            if($row3 = pg_fetch_assoc($result3)){
                                echo "<div class='number'>{$row3['entradas_mes']}</div>";
                            } else {
                                echo "<div class='number'>0</div>";
                            }
                            ?>
                            <div class="label">Compras Este Mes</div>
                            <small class="text-muted">Entradas desde proveedores</small>
                        </div>
                    </div>

                    <!-- Tarjeta 3: Alertas Activas -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card warning h-100">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            <?php
                            if($row4 = pg_fetch_assoc($result4)){
                                echo "<div class='number'>{$row4['alertas_activas']}</div>";
                            } else {
                                echo "<div class='number'>0</div>";
                            }
                            ?>
                            <div class="label">Alertas Activas</div>
                            <?php
                            if($row5 = pg_fetch_assoc($result5)){
                                echo "<small class='text-muted'>{$row5['alertas_urgentes']} requieren atención urgente</small>";
                            } else {
                                echo "<small class='text-muted'>0 requieren atención urgente</small>";
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Tarjeta 4: Productos con Stock Bajo -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card danger h-100">
                            <i class="fas fa-exclamation-circle text-danger"></i>
                            <?php
                            $query_bajo_stock = pg_query($conexion, "SELECT COUNT(*) as bajos FROM producto WHERE stock <= 5");
                            if($query_bajo_stock){
                                $row_bajo = pg_fetch_assoc($query_bajo_stock);
                                echo "<div class='number'>{$row_bajo['bajos']}</div>";
                            } else {
                                echo "<div class='number'>0</div>";
                            }
                            ?>
                            <div class="label">Stock Bajo</div>
                            <small class="text-muted">Stock menor o igual a 5 unidades</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="acciones-grid">
                                    <a href="entradaproveedor.php">
                                        <div class="accion-card">
                                            <div class="accion-icono"><i class="fas fa-truck-loading fa-2x mb-2 d-block"></i></div>
                                            <div class="accion-titulo"><span class="fw-bold">Nueva Entrada</span></div>
                                            <div class="accion-descripcion"><small class="d-block mt-1">Registrar compra</small></div>
                                        </div>
                                    </a>

                                    <a href="gestionproductos.php">
                                        <div class="accion-card">
                                            <div class="accion-icono"><i class="fas fa-plus-circle fa-2x mb-2 d-block"></i></div>
                                            <div class="accion-titulo"><span class="fw-bold">Nuevo producto</span></div>
                                            <div class="accion-descripcion"><small class="d-block mt-1">Agregar productos al sistema</small></div>
                                        </div>
                                    </a>

                                    <a href="almacenproveedores.php">
                                        <div class="accion-card">
                                            <div class="accion-icono"><i class="fas fa-truck fa-2x mb-2 d-block"></i></div>
                                            <div class="accion-titulo"><span class="fw-bold">Ver Proveedores</span></div>
                                            <div class="accion-descripcion"><small class="d-block mt-1">Gestionar proveedores</small></div>
                                        </div>
                                    </a>

                                    <a href="notificaciones.php">
                                        <div class="accion-card">
                                            <div class="accion-icono"><i class="fas fa-bell fa-2x mb-2 d-block"></i></div>
                                            <div class="accion-titulo"><span class="fw-bold">Ver notificaciones</span></div>
                                            <div class="accion-descripcion"><small class="d-block mt-1">Revisar notificaciones</small></div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Panel de Alertas de Stock Urgentes (3 alertas) -->
                <div class="col-xl-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Alertas de Stock Urgentes</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php
                                if($result7 && pg_num_rows($result7) > 0){
                                    while($alerta = pg_fetch_assoc($result7)){
                                        $badge_class = ($alerta['stock'] < 3) ? 'bg-danger' : 'bg-warning';
                                        $badge_text = ($alerta['stock'] < 3) ? 'URGENTE' : 'BAJO';
                                        $text_class = ($alerta['stock'] < 3) ? 'text-danger' : 'text-warning';
                                        
                                        echo "
                                        <div class='list-group-item'>
                                            <div class='d-flex w-100 justify-content-between align-items-center'>
                                                <div>
                                                    <h6 class='mb-1 {$text_class}'>{$alerta['producto_nombre']}</h6>
                                                    <p class='mb-1'>Stock: <strong>{$alerta['stock']} unidades</strong></p>
                                                    <small class='text-muted'>Categoría: {$alerta['categoria_nombre']}</small>
                                                </div>
                                                <span class='badge {$badge_class}'>{$badge_text}</span>
                                            </div>
                                        </div>
                                        ";
                                    }
                                } else {
                                    echo "
                                    <div class='list-group-item text-center py-4'>
                                        <i class='fas fa-check-circle text-success fa-2x mb-2'></i>
                                        <p class='mb-0 text-muted'>No hay alertas urgentes</p>
                                        <small class='text-muted'>Todos los productos tienen stock suficiente</small>
                                    </div>
                                    ";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel de Movimientos Recientes (3 movimientos) -->
                <div class="col-xl-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Movimientos Recientes</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php
                                if($result6 && pg_num_rows($result6) > 0){
                                    while($movimiento = pg_fetch_assoc($result6)){
                                        $icon_class = '';
                                        $text_class = '';
                                        
                                        if($movimiento['tipo_movimiento'] == 'Entrada') {
                                            $icon_class = 'fas fa-truck-loading text-success';
                                        } elseif($movimiento['tipo_movimiento'] == 'Salida') {
                                            $icon_class = 'fas fa-arrow-right text-warning';
                                            $text_class = 'warning';
                                        } else {
                                            $icon_class = 'fas fa-exchange-alt text-info';
                                        }
                                        
                                        $fecha = date('d/m/Y H:i', strtotime($movimiento['fecha_movimiento']));
                                        $hoy = date('d/m/Y');
                                        $fecha_simple = date('d/m/Y', strtotime($movimiento['fecha_movimiento']));
                                        
                                        $fecha_display = ($fecha_simple == $hoy) ? "Hoy, " . date('H:i', strtotime($movimiento['fecha_movimiento'])) : $fecha;
                                        
                                        echo "
                                        <div class='timeline-item {$text_class}'>
                                            <small class='text-muted'>{$fecha_display}</small>
                                            <p class='mb-0'><i class='{$icon_class} me-2'></i>{$movimiento['tipo_movimiento']} - {$movimiento['producto_nombre']}</p>
                                            <small class='text-muted'>{$movimiento['observacion']}</small>
                                        </div>
                                        ";
                                    }
                                } else {
                                    echo "
                                    <div class='text-center py-4'>
                                        <i class='fas fa-info-circle fa-2x text-muted mb-2'></i>
                                        <p class='mb-0 text-muted'>No hay movimientos recientes</p>
                                    </div>
                                    ";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Inicializar tooltips de Bootstrap si los hay
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard cargado correctamente');
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