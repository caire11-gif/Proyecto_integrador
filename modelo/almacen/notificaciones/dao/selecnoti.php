<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarNotificacionDao {
    public function seleccionar() {
        $conexion=Conexion::getConexion();

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

        // Construir consulta base CORREGIDA - usando pr.razon_social en lugar de pr.nombre
        $query_alertas = "
            SELECT n.*, p.nombre as producto_nombre, p.stock, p.unidades_por_caja, pr.razon_social as proveedor_nombre, pr.cod_proveedor, c.nombre as categoria_nombre,
                    tn.nombre as tipo_notificacion, en.nombre as estado_notificacion FROM notificacion n
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

        $selecnoti['data']='';

        if($result_alertas && pg_num_rows($result_alertas) > 0){
            while($alerta = pg_fetch_assoc($result_alertas)){
                $estado_badge = $alerta['cod_estadonotificacion'] == 'en001' ? 'bg-primary' : 'bg-secondary';
                $estado_text = $alerta['cod_estadonotificacion'] == 'en001' ? 'PENDIENTE' : 'LEÍDA';
                        list($priority_class, $priority_text) = getPriorityBadge($alerta['mensaje']);
                
                $selecnoti['data'].= '
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">'.$alerta['producto_nombre'].'</h6>
                                    <div>
                                        <span class="badge '.$priority_class.' me-2">'.$priority_text.'</span>
                                        <span class="badge '.$estado_badge.'"><span class="estado">'.$estado_text.'</span></span>
                                    </div>
                                </div>
                                <p class="mb-2">'.$alerta['mensaje'].'</p>
                                <small class="text-muted">
                                    <i class="fas fa-warehouse me-1"></i>Stock actual: <strong>'.$alerta['stock'].' unidades</strong>
                                    | <i class="fas fa-truck me-1"></i><span class="proveedor">'.$alerta['proveedor_nombre'].'</span>
                                    | <i class="fas fa-clock me-1"></i>'.$alerta['fecha_alerta'].'
                                </small>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark me-1">'.$alerta['categoria_nombre'].'</span>
                                    <span class="badge bg-light text-dark">Caja: '.$alerta['unidades_por_caja'].' und.</span>
                                </div>
                            </div>
                            <div class="btn-group-vertical ms-3">
                                <button class="btn btn-sm btn-success btnActualizarEstado" data-codestnoti="'.$alerta['cod_estadonotificacion'].'" data-codnoti="'.$alerta['cod_notificacion'].'">
                                    <i class="fas fa-check me-1"></i>Marcar
                                </button>
                            </div>
                        </div>
                    </div>
                ';
            }
        } else {
            $selecnoti['data'].= '
                <div class="list-group-item text-center py-4">
                    <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                    <p class="mb-0 text-muted">No hay alertas de stock bajo en este momento</p>
                    <small class="text-muted">El sistema genera alertas automáticas cuando el stock es menor a 20 unidades</small>
                </div>
            ';
        }

        header('Content-Type: application/json');
        echo json_encode($selecnoti);
    }
}
?>