<?php
session_start();

// CONEXIÓN A LA BASE DE DATOS
$conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");

if(!$conexion){
    echo "Un error de conexión ocurrió. <br>";
    exit;
}

// OBTENER ESTADÍSTICAS REALES DE LA BASE DE DATOS
function obtenerEstadisticasTurno($conexion) {
    $estadisticas = [
        'ventas_hoy' => 0,
        'total_vendido' => 0,
        'productos_vendidos' => 0
    ];
    
    // Ventas de hoy - CORREGIDO: calcular total desde detalleventa
    $queryVentasHoy = "SELECT COUNT(DISTINCT v.cod_venta) as total_ventas, 
                              COALESCE(SUM(dv.total), 0) as total_vendido 
                      FROM venta v
                      LEFT JOIN detalleventa dv ON v.cod_venta = dv.cod_venta
                      WHERE DATE(v.fecha_venta) = CURRENT_DATE";
    $resultVentasHoy = pg_query($conexion, $queryVentasHoy);
    if($resultVentasHoy && pg_num_rows($resultVentasHoy) > 0) {
        $row = pg_fetch_assoc($resultVentasHoy);
        $estadisticas['ventas_hoy'] = $row['total_ventas'];
        $estadisticas['total_vendido'] = $row['total_vendido'];
    }
    
    // Productos vendidos hoy
    $queryProductosVendidos = "SELECT COALESCE(SUM(dv.cantidad_unidades), 0) as total_productos
                              FROM detalleventa dv
                              JOIN venta v ON dv.cod_venta = v.cod_venta
                              WHERE DATE(v.fecha_venta) = CURRENT_DATE";
    $resultProductos = pg_query($conexion, $queryProductosVendidos);
    if($resultProductos && pg_num_rows($resultProductos) > 0) {
        $row = pg_fetch_assoc($resultProductos);
        $estadisticas['productos_vendidos'] = $row['total_productos'];
    }
    
    return $estadisticas;
}

// OBTENER VENTAS RECIENTES - CORREGIDO: calcular total desde detalleventa
function obtenerVentasRecientes($conexion, $limite = 5) {
    $query = "
        SELECT 
            v.cod_venta as id,
            v.fecha_venta as fecha,
            SUM(dv.total) as total,
            v.cod_metodopago as metodo_pago,
            COUNT(dv.cod_detalleventa) as cantidad_productos,
            STRING_AGG(p.nombre, ', ') as productos_nombres
        FROM venta v
        LEFT JOIN detalleventa dv ON v.cod_venta = dv.cod_venta
        LEFT JOIN producto p ON dv.cod_producto = p.cod_producto
        GROUP BY v.cod_venta, v.fecha_venta, v.cod_metodopago
        ORDER BY v.fecha_venta DESC
        LIMIT $limite
    ";
    
    $result = pg_query($conexion, $query);
    $ventas = [];
    
    if($result && pg_num_rows($result) > 0) {
        while($row = pg_fetch_assoc($result)) {
            $ventas[] = [
                'id' => $row['id'],
                'fecha' => $row['fecha'],
                'total' => $row['total'] ?: 0,
                'metodo_pago' => $row['metodo_pago'],
                'cantidad_productos' => $row['cantidad_productos'],
                'productos' => $row['productos_nombres'] ?: 'Sin productos'
            ];
        }
    }
    
    return $ventas;
}

// Obtener datos reales
$estadisticas = obtenerEstadisticasTurno($conexion);
$ventasRecientes = obtenerVentasRecientes($conexion);

// Inicializar contadores de sesión si no existen
if (!isset($_SESSION['inicio_turno'])) {
    $_SESSION['inicio_turno'] = time();
}

// Calcular tiempo activo
$tiempo_activo = time() - $_SESSION['inicio_turno'];
$horas = floor($tiempo_activo / 3600);
$minutos = floor(($tiempo_activo % 3600) / 60);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Dashboard Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/vendedor-estilo.css">
    <style>
        /* ESTILOS MEJORADOS PARA VENTAS RECIENTES */
        .ventas-recientes {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }

        .ventas-recientes h3 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 0;
        }

        .ventas-lista {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .ventas-lista::-webkit-scrollbar {
            width: 6px;
        }

        .ventas-lista::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .ventas-lista::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        .venta-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px;
            margin-bottom: 12px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
        }

        .venta-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #007bff;
        }

        .venta-info {
            flex: 1;
            margin-right: 20px;
        }

        .venta-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .venta-id {
            font-size: 0.85em;
            font-weight: 600;
            background: linear-gradient(135deg, #007bff, #0056b3);
            padding: 4px 10px;
            border-radius: 6px;
            color: white;
        }

        .venta-fecha {
            font-size: 0.8em;
            color: #6c757d;
            font-weight: 500;
        }

        .venta-productos {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .venta-detalles {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .venta-metodo, .venta-unidades {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8em;
            color: #6c757d;
            background: white;
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .venta-metodo i, .venta-unidades i {
            font-size: 0.9em;
        }

        .venta-total {
            font-weight: 700;
            font-size: 1.3em;
            color: #28a745;
            min-width: 100px;
            text-align: right;
            background: white;
            padding: 10px 15px;
            border-radius: 8px;
            border: 2px solid #d4edda;
        }

        .empty-ventas {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-ventas i {
            font-size: 3em;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-ventas p {
            margin-bottom: 5px;
            font-weight: 500;
        }

        .empty-ventas small {
            font-size: 0.9em;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f1f1;
        }

        .ventas-count {
            background: #007bff;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        /* Estilos para métodos de pago */
        .metodo-efectivo { color: #28a745; }
        .metodo-tarjeta { color: #ff6b35; }
        .metodo-transferencia { color: #6f42c1; }

        /* Responsive */
        @media (max-width: 768px) {
            .venta-item {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            .venta-info {
                margin-right: 0;
            }
            
            .venta-total {
                text-align: center;
                min-width: auto;
            }
            
            .venta-detalles {
                justify-content: space-between;
            }
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

                <div class="turno-info">
                    <div class="fw-bold">Carlos Rodríguez</div>
                    <small>Turno: 08:00 - 16:00</small><br>
                    <small id="tiempoActivoSidebar"><?php echo $horas . 'h ' . $minutos . 'm activo'; ?></small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.php" class="nav-link active"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="nuevaventa.php" class="nav-link"><ul><i class="fas fa-cash-register"></i>Nueva Venta</ul></a>
                    <a href="registrodevolucion.php" class="nav-link"><ul><i class="fas fa-undo-alt"></i>Registrar Devolución</ul></a>
                    <a href="boletas-facturas.php" class="nav-link"><ul><i class="fas fa-receipt"></i>Boletas/Facturas</ul></a>
                    <a href="consulta-stock.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Consulta-stock</ul></a>
                    <a href="../login.php" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
                </div>
            </div>
        </main>

        <div class="secundario">
            <div class="header">
                <div class="caja-busqueda">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Buscar productos, ventas..." id="globalSearch">
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
                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="actualizarDashboard()" title="Actualizar datos">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
            </div>

            <div class="contenedor-dashboard">
                <!-- RESUMEN DEL TURNO -->
                <section class="turno-actual">
                    <div class="turno-card">
                        <h3><i class="fas fa-chart-line"></i> Resumen Turno Actual</h3>
                        <div class="turno-stats">
                            <div class="stat">
                                <span class="stat-value" id="ventasHoy"><?php echo $estadisticas['ventas_hoy']; ?></span>
                                <span class="stat-label">Ventas Hoy</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value" id="totalVendido">S/ <?php echo number_format($estadisticas['total_vendido'], 2); ?></span>
                                <span class="stat-label">Total Vendido</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value" id="productosVendidos"><?php echo $estadisticas['productos_vendidos']; ?></span>
                                <span class="stat-label">Productos Vendidos</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value" id="tiempoActivo"><?php echo $horas . 'h ' . $minutos . 'm'; ?></span>
                                <span class="stat-label">Tiempo Activo</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ACCIONES RÁPIDAS -->
                <section class="acciones-principales">
                    <h3><i class="fas fa-bolt"></i> Acciones Rápidas</h3>
                    <div class="acciones-grid">
                        <a href="nuevaventa.php">
                            <div class="accion-card">
                                <div class="accion-icono"><i class="fas fa-cash-register"></i></div>
                                <div class="accion-titulo">Nueva Venta</div>
                                <div class="accion-descripcion">Registrar venta al público</div>
                            </div>
                        </a>

                        <a href="boletas-facturas.php">
                            <div class="accion-card">
                                <div class="accion-icono"><i class="fas fa-receipt"></i></div>
                                <div class="accion-titulo">Boletas/Facturas</div>
                                <div class="accion-descripcion">Crear boletas y facturas</div>
                            </div>
                        </a>

                        <a href="registrodevolucion.php">
                            <div class="accion-card">
                                <div class="accion-icono"><i class="fas fa-undo-alt"></i></div>
                                <div class="accion-titulo">Registrar Devolución</div>
                                <div class="accion-descripcion">Procesar devoluciones</div>
                            </div>
                        </a>

                        <a href="consulta-stock.php">
                            <div class="accion-card">
                                <div class="accion-icono"><i class="fas fa-boxes"></i></div>
                                <div class="accion-titulo">Consultar Stock</div>
                                <div class="accion-descripcion">Verificar inventario</div>
                            </div>
                        </a>
                    </div>
                </section>

                <!-- VENTAS RECIENTES - MEJOR ORGANIZADO -->
                <section class="ventas-recientes">
                    <div class="section-header">
                        <h3><i class="fas fa-history"></i> Ventas Recientes</h3>
                        <span class="ventas-count"><?php echo count($ventasRecientes); ?> ventas</span>
                    </div>
                    
                    <div class="ventas-lista" id="listaVentasRecientes">
                        <?php if(empty($ventasRecientes)): ?>
                            <div class="empty-ventas">
                                <i class="fas fa-shopping-cart"></i>
                                <p>No hay ventas hoy</p>
                                <small>Las ventas aparecerán aquí automáticamente</small>
                            </div>
                        <?php else: ?>
                            <?php foreach($ventasRecientes as $venta): 
                                $icono_metodo = $venta['metodo_pago'] === 'mp001' ? 'money-bill-wave' : 
                                              ($venta['metodo_pago'] === 'mp002' ? 'credit-card' : 'mobile-alt');
                                $clase_metodo = $venta['metodo_pago'] === 'mp001' ? 'metodo-efectivo' : 
                                              ($venta['metodo_pago'] === 'mp002' ? 'metodo-tarjeta' : 'metodo-transferencia');
                                $texto_metodo = $venta['metodo_pago'] === 'mp001' ? 'Efectivo' : 
                                              ($venta['metodo_pago'] === 'mp002' ? 'Tarjeta' : 'Transferencia');
                            ?>
                                <div class="venta-item">
                                    <div class="venta-info">
                                        <div class="venta-header">
                                            <span class="venta-id">#<?php echo $venta['id']; ?></span>
                                            <span class="venta-fecha">
                                                <?php echo date('H:i', strtotime($venta['fecha'])); ?> - 
                                                <?php echo date('d/m', strtotime($venta['fecha'])); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="venta-productos" title="<?php echo htmlspecialchars($venta['productos']); ?>">
                                            <?php echo htmlspecialchars($venta['productos']); ?>
                                        </div>
                                        
                                        <div class="venta-detalles">
                                            <span class="venta-metodo <?php echo $clase_metodo; ?>">
                                                <i class="fas fa-<?php echo $icono_metodo; ?>"></i>
                                                <?php echo $texto_metodo; ?>
                                            </span>
                                            <span class="venta-unidades">
                                                <i class="fas fa-box"></i>
                                                <?php echo $venta['cantidad_productos']; ?> unidades
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="venta-total">
                                        S/ <?php echo number_format($venta['total'], 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        // ===== SISTEMA DE ACTUALIZACIÓN DEL DASHBOARD =====
        
        // Actualizar tiempo en tiempo real
        function actualizarTiempo() {
            const inicioTurno = <?php echo $_SESSION['inicio_turno']; ?>;
            const ahora = Math.floor(Date.now() / 1000);
            const diferencia = ahora - inicioTurno;
            const horas = Math.floor(diferencia / 3600);
            const minutos = Math.floor((diferencia % 3600) / 60);
            
            document.getElementById('tiempoActivo').textContent = horas + 'h ' + minutos + 'm';
            document.getElementById('tiempoActivoSidebar').textContent = horas + 'h ' + minutos + 'm activo';
        }
        
        // Función para actualizar el dashboard
        function actualizarDashboard() {
            // Mostrar indicador de carga
            const btnActualizar = event.target;
            const iconoOriginal = btnActualizar.innerHTML;
            btnActualizar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btnActualizar.disabled = true;
            
            // Recargar la página después de un breve delay
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
        
        // Función para cerrar turno
        function cerrarTurno() {
            if(confirm('¿Estás seguro de que deseas cerrar el turno?')) {
                window.location.href = '../login.html';
            }
        }
        
        // Función para buscar globalmente
        function configurarBusquedaGlobal() {
            const busquedaInput = document.getElementById('globalSearch');
            busquedaInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const termino = this.value.trim();
                    if (termino) {
                        // Redirigir a búsqueda en productos
                        window.location.href = `consulta-stock.php?buscar=${encodeURIComponent(termino)}`;
                    }
                }
            });
        }
        
        // Auto-actualización cada 30 segundos
        function iniciarAutoActualizacion() {
            setInterval(() => {
                // Solo actualizar si la página está visible
                if (!document.hidden) {
                    window.location.reload();
                }
            }, 30000); // 30 segundos
        }
        
        // Inicializar dashboard
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Dashboard inicializado con datos reales de BD');
            
            // Configurar búsqueda global
            configurarBusquedaGlobal();
            
            // Actualizar tiempo cada minuto
            setInterval(actualizarTiempo, 60000);
            
            // Iniciar auto-actualización
            iniciarAutoActualizacion();
            
            // Mostrar notificación de actualización automática
            console.log('🔄 Dashboard se actualizará automáticamente cada 30 segundos');
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>