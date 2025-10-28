<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Alertas de Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/almacen-estilo.css">
    <link rel="stylesheet" href="css/alertas-stock/alertas.css">
</head>
<body>
    <?php
    // Conexión a la base de datos
    $conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
    if(!$conexion){
        echo "Error de conexión.";
        exit;
    }

    // Procesar formularios
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        if(isset($_POST['accion'])){
            switch($_POST['accion']){
                case 'marcar_leida':
                    $cod_notificacion = $_POST['cod_notificacion'];
                    $query = "UPDATE notificacion SET cod_estadonotificacion = 'en002' WHERE cod_notificacion = $1";
                    $result = pg_query_params($conexion, $query, array($cod_notificacion));
                    if($result){
                        showNotification("Alerta marcada como leída", "success");
                    } else {
                        showNotification("Error al marcar la alerta", "error");
                    }
                    break;
            }
        }
    }

    // Generar alertas automáticas de stock bajo con niveles de prioridad
    function generarAlertasAutomaticas($conexion) {
        $cod_usuario = 'user001';
        
        // Verificar productos con stock bajo que no tengan alertas pendientes
        $query_stock_bajo = "
            SELECT p.cod_producto, p.nombre, p.stock, p.unidades_por_caja, pr.nombre as proveedor_nombre
            FROM producto p
            JOIN proveedor pr ON p.cod_proveedor = pr.cod_proveedor
            WHERE p.stock < 20 
            AND p.cod_producto NOT IN (
                SELECT cod_producto FROM notificacion 
                WHERE cod_estadonotificacion IN ('en001', 'en002') 
                AND cod_tiponotificacion = 'not001'
            )
        ";
        
        $result = pg_query($conexion, $query_stock_bajo);
        if($result){
            while($producto = pg_fetch_assoc($result)){
                $cod_notificacion = 'N' . substr(uniqid(), -8);
                
                // Determinar prioridad según el stock
                if($producto['stock'] < 5) {
                    $prioridad = 'Alta';
                    $mensaje = "🚨 ALTA PRIORIDAD - Stock crítico para {$producto['nombre']}. Actual: {$producto['stock']} unidades. ¡Reposición urgente requerida!";
                } elseif($producto['stock'] < 10) {
                    $prioridad = 'Media';
                    $mensaje = "⚠️ Stock bajo para {$producto['nombre']}. Actual: {$producto['stock']} unidades. Se recomienda reposición.";
                } else {
                    $prioridad = 'Baja';
                    $mensaje = "ℹ️ Stock moderado para {$producto['nombre']}. Actual: {$producto['stock']} unidades. Monitorear.";
                }
                
                $query_insert = "INSERT INTO notificacion (cod_notificacion, cod_usuario, cod_producto, cod_tiponotificacion, cod_estadonotificacion, mensaje) 
                                VALUES ($1, $2, $3, 'not001', 'en001', $4)";
                pg_query_params($conexion, $query_insert, array($cod_notificacion, $cod_usuario, $producto['cod_producto'], $mensaje));
            }
        }
    }

    // Ejecutar generación de alertas automáticas
    generarAlertasAutomaticas($conexion);

    // Construir consulta con filtros - SOLO ALERTAS DE STOCK BAJO
    $where_conditions = array("n.cod_tiponotificacion = 'not001'");
    $query_params = array();
    
    // Filtro por estado
    if(isset($_GET['filtroEstado']) && !empty($_GET['filtroEstado'])){
        $where_conditions[] = "n.cod_estadonotificacion = $1";
        $query_params[] = $_GET['filtroEstado'];
    }
    
    // Filtro por proveedor
    if(isset($_GET['filtroProveedor']) && !empty($_GET['filtroProveedor'])){
        $where_conditions[] = "pr.cod_proveedor = $" . (count($query_params) + 1);
        $query_params[] = $_GET['filtroProveedor'];
    }

    // Construir consulta base
    $query_alertas = "
        SELECT n.*, p.nombre as producto_nombre, p.stock, p.unidades_por_caja, 
               pr.nombre as proveedor_nombre, pr.cod_proveedor,
               c.nombre as categoria_nombre,
               tn.nombre as tipo_notificacion,
               en.nombre as estado_notificacion
        FROM notificacion n
        JOIN producto p ON n.cod_producto = p.cod_producto
        JOIN proveedor pr ON p.cod_proveedor = pr.cod_proveedor
        JOIN categoria c ON p.cod_categoria = c.cod_categoria
        JOIN tiponotificacion tn ON n.cod_tiponotificacion = tn.cod_tiponotificacion
        JOIN estadonotificacion en ON n.cod_estadonotificacion = en.cod_estadonotificacion
        WHERE " . implode(" AND ", $where_conditions);
    
    $query_alertas .= " ORDER BY 
        CASE 
            WHEN n.mensaje LIKE '🚨 ALTA PRIORIDAD%' THEN 1
            WHEN n.mensaje LIKE '⚠️%' THEN 2
            WHEN n.mensaje LIKE 'ℹ️%' THEN 3
            ELSE 4
        END,
        p.stock ASC";
    
    // Ejecutar consulta con parámetros si existen
    if(!empty($query_params)){
        $result_alertas = pg_query_params($conexion, $query_alertas, $query_params);
    } else {
        $result_alertas = pg_query($conexion, $query_alertas);
    }

    // Contadores por prioridad
    $total_alertas = 0;
    $alertas_alta = 0;
    $alertas_media = 0;
    $alertas_baja = 0;
    
    if($result_alertas){
        $total_alertas = pg_num_rows($result_alertas);
        // Reiniciar el puntero para contar por prioridad
        pg_result_seek($result_alertas, 0);
        while($alerta = pg_fetch_assoc($result_alertas)){
            if(strpos($alerta['mensaje'], '🚨 ALTA PRIORIDAD') !== false) {
                $alertas_alta++;
            } elseif(strpos($alerta['mensaje'], '⚠️') !== false) {
                $alertas_media++;
            } elseif(strpos($alerta['mensaje'], 'ℹ️') !== false) {
                $alertas_baja++;
            }
        }
        // Volver al inicio para mostrar
        pg_result_seek($result_alertas, 0);
    }

    function showNotification($message, $type) {
        $alert_class = $type == 'success' ? 'alert-success' : 'alert-danger';
        echo "<div class='alert {$alert_class} alert-dismissible fade show position-fixed' style='top: 20px; right: 20px; z-index: 1050; min-width: 300px;'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }

    // Función para determinar el color del badge según la prioridad
    function getPriorityBadge($mensaje) {
        if(strpos($mensaje, '🚨 ALTA PRIORIDAD') !== false) {
            return ['bg-danger', 'ALTA'];
        } elseif(strpos($mensaje, '⚠️') !== false) {
            return ['bg-warning', 'MEDIA'];
        } elseif(strpos($mensaje, 'ℹ️') !== false) {
            return ['bg-info', 'BAJA'];
        } else {
            return ['bg-secondary', 'INFO'];
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

                <div class="turno-info">
                    <div class="fw-bold">María Alvarez</div>
                    <small>Turno: 08:00 - 16:00</small><br>
                    <small id="tiempoActivoSidebar">0h 0m activo</small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.php" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="gestionproductos.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Gestión de Productos</ul></a>
                    <a href="almacenproveedores.php" class="nav-link"><ul><i class="fas fa-truck"></i>Proveedores</ul></a>
                    <a href="entradaproveedor.php" class="nav-link"><ul><i class="fas fa-truck-loading"></i>Entradas Proveedor</ul></a>
                    <a href="trasladotienda.php" class="nav-link"><ul><i class="fas fa-arrow-right"></i>Traslados a Tienda</ul></a>
                    <a href="notificaciones.php" class="nav-link active"><ul><i class="fas fa-bell"></i>Notificaciones</ul></a>
                    <a href="reportes.php" class="nav-link"><ul><i class="fas fa-chart-bar"></i>Reportes</ul></a>
                    <a href="../login.php" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
                </div>
            </div>
        </main>

        <div class="secundario">
            <div class="header">
                <div class="caja-busqueda">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Buscar productos, alertas..." id="globalSearch">
                </div>
                
                <div class="usuario-info">
                    <div class="usuario-avatar" id="usuarioAvatar">MA</div>
                    <div>
                        <div class="fw-bold fs-5" id="userName">María Alvarez</div>
                        <small class="text-muted" id="userPosition">Encargado - Turno Activo</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger ms-3" onclick="cerrarTurno()">
                        <i class="fas fa-sign-out-alt me-1"></i>Cerrar Turno
                    </button>
                </div>
            </div>
            <br>
            <div class="contenido-principal">
                <div class="container-fluid">
                    <!-- Encabezado -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-1"><i class="fas fa-bell me-2"></i>Alertas de Stock Bajo</h4>
                            <p class="text-muted mb-0">Sistema automático de alertas de stock con niveles de prioridad</p>
                        </div>
                        <div>
                            <span class="badge bg-danger me-2"><?php echo $total_alertas; ?> Alertas</span>
                            <button class="btn btn-outline-secondary" id="btnActualizarAlertas" onclick="actualizarAlertas()">
                                <i class="fas fa-sync me-2"></i>Actualizar
                            </button>
                        </div>
                    </div>

                    <!-- Filtros Simplificados -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" id="filtroForm">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label class="form-label">Estado</label>
                                        <select class="form-select" id="filtroEstado" name="filtroEstado">
                                            <option value="">Todos los estados</option>
                                            <option value="en001" <?php echo (isset($_GET['filtroEstado']) && $_GET['filtroEstado'] == 'en001') ? 'selected' : ''; ?>>Pendiente</option>
                                            <option value="en002" <?php echo (isset($_GET['filtroEstado']) && $_GET['filtroEstado'] == 'en002') ? 'selected' : ''; ?>>Leída</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Proveedor</label>
                                        <select class="form-select" id="filtroProveedor" name="filtroProveedor">
                                            <option value="">Todos los proveedores</option>
                                            <?php
                                            $query_proveedores = "SELECT cod_proveedor, nombre FROM proveedor";
                                            $result_proveedores = pg_query($conexion, $query_proveedores);
                                            while($proveedor = pg_fetch_assoc($result_proveedores)){
                                                $selected = (isset($_GET['filtroProveedor']) && $_GET['filtroProveedor'] == $proveedor['cod_proveedor']) ? 'selected' : '';
                                                echo "<option value='{$proveedor['cod_proveedor']}' $selected>{$proveedor['nombre']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-mad flex-fill">
                                                <i class="fas fa-filter me-2"></i>Aplicar Filtros
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Mostrar filtros activos -->
                                <?php if(isset($_GET['filtroEstado']) || isset($_GET['filtroProveedor'])): ?>
                                <div class="mt-3">
                                    <small class="text-muted">Filtros activos:</small>
                                    <div class="d-flex flex-wrap gap-2 mt-1">
                                        <?php if(isset($_GET['filtroEstado']) && !empty($_GET['filtroEstado'])): ?>
                                            <span class="badge bg-info">
                                                Estado: <?php 
                                                    $estados = ['en001' => 'Pendiente', 'en002' => 'Leída'];
                                                    echo $estados[$_GET['filtroEstado']] ?? $_GET['filtroEstado'];
                                                ?>
                                                <a href="?" class="text-white ms-1" onclick="removerFiltro('filtroEstado')">×</a>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if(isset($_GET['filtroProveedor']) && !empty($_GET['filtroProveedor'])): 
                                            $proveedor_nombre = '';
                                            $query_prov = "SELECT nombre FROM proveedor WHERE cod_proveedor = $1";
                                            $result_prov = pg_query_params($conexion, $query_prov, array($_GET['filtroProveedor']));
                                            if($result_prov && pg_num_rows($result_prov) > 0){
                                                $proveedor_nombre = pg_fetch_result($result_prov, 0, 0);
                                            }
                                        ?>
                                            <span class="badge bg-success">
                                                Proveedor: <?php echo $proveedor_nombre ?: $_GET['filtroProveedor']; ?>
                                                <a href="?" class="text-white ms-1" onclick="removerFiltro('filtroProveedor')">×</a>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <!-- Contenido principal - ALERTAS A LA IZQUIERDA, RESUMEN A LA DERECHA -->
                    <div class="row">
                        <!-- Columna de Alertas -->
                        <div class="col-lg-8 mb-4">
                            <!-- Alertas de Stock Bajo -->
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Alertas de Stock Bajo (<?php echo $total_alertas; ?>)</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        <?php
                                        if($result_alertas && pg_num_rows($result_alertas) > 0){
                                            while($alerta = pg_fetch_assoc($result_alertas)){
                                                $estado_badge = $alerta['cod_estadonotificacion'] == 'en001' ? 'bg-primary' : 'bg-secondary';
                                                $estado_text = $alerta['cod_estadonotificacion'] == 'en001' ? 'PENDIENTE' : 'LEÍDA';
                                                list($priority_class, $priority_text) = getPriorityBadge($alerta['mensaje']);
                                                
                                                echo "
                                                <div class='list-group-item'>
                                                    <div class='d-flex justify-content-between align-items-start'>
                                                        <div class='flex-grow-1'>
                                                            <div class='d-flex justify-content-between align-items-center mb-2'>
                                                                <h6 class='mb-0'>{$alerta['producto_nombre']}</h6>
                                                                <div>
                                                                    <span class='badge {$priority_class} me-2'>{$priority_text}</span>
                                                                    <span class='badge {$estado_badge}'>{$estado_text}</span>
                                                                </div>
                                                            </div>
                                                            <p class='mb-2'>{$alerta['mensaje']}</p>
                                                            <small class='text-muted'>
                                                                <i class='fas fa-warehouse me-1'></i>Stock actual: <strong>{$alerta['stock']} unidades</strong>
                                                                | <i class='fas fa-truck me-1'></i>{$alerta['proveedor_nombre']}
                                                                | <i class='fas fa-clock me-1'></i>{$alerta['fecha_alerta']}
                                                            </small>
                                                            <div class='mt-2'>
                                                                <span class='badge bg-light text-dark me-1'>{$alerta['categoria_nombre']}</span>
                                                                <span class='badge bg-light text-dark'>Caja: {$alerta['unidades_por_caja']} und.</span>
                                                            </div>
                                                        </div>
                                                        <div class='btn-group-vertical ms-3'>
                                                            <form method='POST' class='d-inline'>
                                                                <input type='hidden' name='cod_notificacion' value='{$alerta['cod_notificacion']}'>
                                                                <button type='submit' class='btn btn-sm btn-success' name='accion' value='marcar_leida'>
                                                                    <i class='fas fa-check me-1'></i>Marcar
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                ";
                                            }
                                        } else {
                                            echo "
                                            <div class='list-group-item text-center py-4'>
                                                <i class='fas fa-check-circle text-success fa-2x mb-2'></i>
                                                <p class='mb-0 text-muted'>No hay alertas de stock bajo en este momento</p>
                                                <small class='text-muted'>El sistema genera alertas automáticas cuando el stock es menor a 20 unidades</small>
                                            </div>
                                            ";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna del Resumen a la Derecha -->
                        <div class="col-lg-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Resumen por Prioridad</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alertas-resumen">
                                        <div class="resumen-item">
                                            <div class="resumen-icon bg-danger">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                            <div class="resumen-info">
                                                <h5><?php echo $alertas_alta; ?></h5>
                                                <small>Alta</small>
                                            </div>
                                        </div>
                                        <div class="resumen-item">
                                            <div class="resumen-icon bg-warning">
                                                <i class="fas fa-exclamation-circle"></i>
                                            </div>
                                            <div class="resumen-info">
                                                <h5><?php echo $alertas_media; ?></h5>
                                                <small>Media</small>
                                            </div>
                                        </div>
                                        <div class="resumen-item">
                                            <div class="resumen-icon bg-info">
                                                <i class="fas fa-info-circle"></i>
                                            </div>
                                            <div class="resumen-info">
                                                <h5><?php echo $alertas_baja; ?></h5>
                                                <small>Baja</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Información adicional -->
                                    <div class="mt-4">
                                        <h6 class="text-muted mb-3">Niveles de Prioridad:</h6>
                                        <div class="d-flex flex-column gap-2">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-danger me-2">ALTA</span>
                                                <small class="text-muted">Stock menor a 5 unidades</small>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-warning me-2">MEDIA</span>
                                                <small class="text-muted">Stock entre 5-10 unidades</small>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-info me-2">BAJA</span>
                                                <small class="text-muted">Stock entre 10-20 unidades</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card de acciones rápidas -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-primary" onclick="actualizarAlertas()">
                                            <i class="fas fa-sync me-2"></i>Actualizar Alertas
                                        </button>
                                        <button class="btn btn-outline-success" onclick="window.location.href='gestionproductos.php'">
                                            <i class="fas fa-boxes me-2"></i>Gestión de Productos
                                        </button>
                                        <button class="btn btn-outline-info" onclick="window.location.href='almacenproveedores.php'">
                                            <i class="fas fa-truck me-2"></i>Ver Proveedores
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function initPage() {
            console.log('Sistema de alertas cargado');
            
            // Búsqueda global
            document.getElementById('globalSearch').addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                if (searchTerm.length > 2) {
                    filtrarAlertas(searchTerm);
                } else if (searchTerm.length === 0) {
                    mostrarTodasAlertas();
                }
            });
        }

        function aplicarFiltros() {
            document.getElementById('filtroForm').submit();
        }

        function limpiarFiltros() {
            window.location.href = 'notificaciones.php';
        }

        function removerFiltro(filtro) {
            const url = new URL(window.location.href);
            url.searchParams.delete(filtro);
            window.location.href = url.toString();
        }

        function actualizarAlertas() {
            showNotification('Sistema de alertas actualizado', 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        function filtrarAlertas(termino) {
            const alertas = document.querySelectorAll('.list-group-item');
            let encontradas = 0;
            alertas.forEach(alerta => {
                const texto = alerta.textContent.toLowerCase();
                if (texto.includes(termino)) {
                    alerta.style.display = 'block';
                    encontradas++;
                } else {
                    alerta.style.display = 'none';
                }
            });
            
            // Mostrar mensaje si no hay resultados
            const contenedor = document.querySelector('.list-group');
            let mensajeNoResultados = contenedor.querySelector('.no-resultados');
            
            if(encontradas === 0 && !mensajeNoResultados){
                mensajeNoResultados = document.createElement('div');
                mensajeNoResultados.className = 'list-group-item text-center py-4 no-resultados';
                mensajeNoResultados.innerHTML = `
                    <i class="fas fa-search fa-2x mb-2 text-muted"></i>
                    <p class="mb-0 text-muted">No se encontraron alertas con: "${termino}"</p>
                `;
                contenedor.appendChild(mensajeNoResultados);
            } else if(encontradas > 0 && mensajeNoResultados){
                mensajeNoResultados.remove();
            }
        }

        function mostrarTodasAlertas() {
            const alertas = document.querySelectorAll('.list-group-item');
            const mensajesNoResultados = document.querySelectorAll('.no-resultados');
            mensajesNoResultados.forEach(mensaje => mensaje.remove());
            alertas.forEach(alerta => {
                alerta.style.display = 'block';
            });
        }

        function showNotification(message, type) {
            // Crear notificación temporal
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remover después de 3 segundos
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }

        function cerrarTurno() {
            if(confirm('¿Está seguro de que desea cerrar el turno?')) {
                showNotification('Turno cerrado correctamente', 'success');
            }
        }

        // Inicializar página cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', initPage);
    </script>
</body>
</html>

<?php
// Cerrar conexión
if($conexion){
    pg_close($conexion);
}
?>