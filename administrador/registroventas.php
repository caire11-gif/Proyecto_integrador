<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Sistema de Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/administrador-kardexprincipal/kardex.css">
    <link rel="stylesheet" href="css/administrador-estilo.css">
</head>
<body>
    <?php
        $conexion=pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
        if(!$conexion){
            echo "Un error de conexión ocurrió.";
        }

        $result1=pg_query($conexion,"SELECT e.cod_empleado,e.nombre FROM empleado e JOIN rol r ON e.cod_rol=r.cod_rol WHERE r.nombre='Vendedor'");
        if(!$result1){
            echo "Error al seleccionar los vendedores";
        }

        $cantiven=pg_query($conexion,"SELECT COUNT(cod_venta) AS cantidad_ventas FROM venta");
        if(!$cantiven){
            echo "Error al contar las ventas.";
        }

        $sum_check=pg_query($conexion,"SELECT 1 FROM detalleventa LIMIT 1");
        if(!$sum_check){
            echo "Error en verificar la cantidad de filas.";
        }

        if(pg_num_rows($sum_check)==0){
            $sumven=0;
        } else {
            $sumaven=pg_query($conexion,"SELECT SUM(total) AS suma_ventas FROM detalleventa");
            if(!$sumaven){
                echo "Error al sumar las ventas";
            }

            $sumven=pg_fetch_assoc($sumaven);
            if(!$sumven){
                echo "Error en la suma";
            }

            $sumven=(float)$sumven['suma_ventas'];
        }
        

        $prom_check = pg_query($conexion, "SELECT 1 FROM venta LIMIT 1");
        if(!$prom_check){
            echo "Error en verificar la cantidad de filas: ".pg_last_error($conexion);
        }

        if(pg_num_rows($prom_check)==0){
            $promsumacanti=0;
        } else {
            $prom=pg_query($conexion,"SELECT (SUM(total)/COUNT(cod_venta)) AS promedio_ventas FROM detalleventa");
            if(!$prom){
                echo "Error al promediar las ventas.";
            }

            $promsumacanti=pg_fetch_assoc($prom);
            if(!$promsumacanti){
                echo "Error con el promedio";
            }

            $promsumacanti=(float)$promsumacanti['promedio_ventas'];
        }
        

        $hisreven=pg_query($conexion,"SELECT v.cod_venta AS venta_codigo,v.fecha_venta AS fecha_venta,u.usuario AS usuario_nombre,r.nombre AS rol_nombre,
                                      SUM(dv.cantidad_unidades) AS venta_cantidad,SUM(dv.total) AS venta_total FROM detalleventa dv
                                      JOIN venta v ON dv.cod_venta=v.cod_venta
                                      JOIN usuario u ON v.cod_usuario=u.cod_usuario
                                      JOIN empleado e ON u.cod_empleado=e.cod_empleado
                                      JOIN rol r ON e.cod_rol=r.cod_rol
                                      WHERE r.nombre='Vendedor'
                                      GROUP by venta_codigo,fecha_venta,usuario_nombre,rol_nombre");
        if(!$hisreven){
            echo "Error al consultar el historial de las ventas.";
        }

        $resultventas=pg_query($conexion,"SELECT dv.cod_venta, v.fecha_venta,SUM(dv.cantidad_unidades) AS cantidad_unidades,SUM(dv.total) AS total,u.usuario FROM detalleventa dv
                                                 JOIN venta v ON dv.cod_venta=v.cod_venta
                                                 JOIN usuario u ON v.cod_usuario=u.cod_usuario
                                                 JOIN empleado e ON u.cod_empleado=e.cod_empleado
                                                 JOIN rol r ON e.cod_rol=r.cod_rol
                                                 WHERE r.nombre='Vendedor'
                                                 GROUP by dv.cod_venta,v.fecha_venta,u.usuario,r.nombre");
    ?>
    <div class="grid">
        <main class="principal">
            <button class="boton-menu" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>

    
            <div class="barra-lateral" id="barra-lateral">
                <div class="logo">
                    <h4><i class="fas fa-store"></i> MAD MARKET</h4>
                    <small id="userRole">Administrador</small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.php" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="kardexprincipal.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Kardex Principal</ul></a>
                    <a href="proveedores.php" class="nav-link"><ul><i class="fas fa-truck"></i>Proveedores</ul></a>
                    <a href="controlpersonal.php" class="nav-link"><ul><i class="fas fa-truck-loading"></i>Control de Personal</ul></a>
                    <a href="registroventas.php" class="nav-link"><ul><i class="fas fa-arrow-right"></i>Registro de Ventas</ul></a>
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
                    <div class="usuario-avatar" id="usuarioAvatar">AP</div>
                    <div>
                        <div class="fw-bold fs-5" id="userName">Admin Principal</div>
                        <small class="text-muted" id="userPosition">Administrador - Turno Activo</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger ms-3" onclick="cerrarTurno()">
                        <i class="fas fa-sign-out-alt me-1"></i>Cerrar Turno
                    </button>
                </div>
            </div>
            <br>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0"><i class="fas fa-cash-register me-2"></i>Registro de Ventas</h1>
                <div>
                    <button class="btn btn-mad" data-bs-toggle="modal" data-bs-target="#modalNuevaVenta">
                        <i class="fas fa-plus me-2"></i>Nueva Venta
                    </button>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" id="filtroForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Vendedor</label>
                                <select class="form-select" id="filtroVendedor">
                                    <option value="">Todos los vendedores</option>
                                    <?php
                                    while($row1=pg_fetch_assoc($result1)){
                                        echo "<option>$row1[nombre]</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="fechaInicio">Fecha Inicio</label>
                                <input type="date" class="form-control" id="fechaInicio" name="fechaInicio">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="fechaFin">Fecha Fin</label>
                                <input type="date" class="form-control" id="fechaFin" name="fechaFin">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select" id="filtroEstado">
                                    <option value="">Todos</option>
                                    <option value="completada">Completada</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="cancelada">Cancelada</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-outline-mad" onclick="aplicarFiltros()">
                                    <i class="fas fa-filter me-2"></i>Aplicar Filtros
                                </button>
                                <button class="btn btn-outline-secondary ms-2" onclick="limpiarFiltros()">
                                    <i class="fas fa-eraser me-2"></i>Limpiar
                                </button>
                            </div>
                        </div>
                    </form>                
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <?php
                            while($cantven=pg_fetch_assoc($cantiven)){
                                echo "<h3 class='text-primary mb-1' id='totalVentas'>$cantven[cantidad_ventas]</h3>";

                                if($cantven['cantidad_ventas']===1){
                                    echo "<small class='text-muted'>Total Venta</small>";
                                } else {
                                    echo "<small class='text-muted'>Total Ventas</small>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <?php
                            if(isset($sumven) && $sumven!==null){
                                echo "<h3 class='text-success mb-1' id='montoTotal'>S/.".number_format($sumven,2)."</h3>";
                            }
                            ?>
                            <small class="text-muted">Monto Total</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <?php
                            if(isset($promsumacanti) && $promsumacanti !== null){
                                echo "<h3 class='text-info mb-1' id='promedioVenta'>S/.".number_format($promsumacanti,2)."</h3>";
                            }
                            ?>
                            <small class="text-muted">Promedio por Venta</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h3 class="text-warning mb-1" id="productosVendidos">156</h3>
                            <small class="text-muted">Productos Vendidos</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Historial de Ventas</h5>
                    <div class="text-muted">
                        Mostrando <span id="ventasMostradas">8</span> de <span id="ventasTotales">24</span> ventas
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>Venta</th>
                                    <th>Fecha</th>
                                    <th>Vendedor</th>
                                    <th>Productos</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                while($conhisrevin=pg_fetch_assoc($hisreven)){
                                    echo "
                                    <tr>
                                        <td>$conhisrevin[venta_codigo]</td>
                                        <td>$conhisrevin[fecha_venta]</td>
                                        <td>$conhisrevin[usuario_nombre]</td>
                                        <td>$conhisrevin[venta_cantidad]</td>
                                        <td>$conhisrevin[venta_total]</td>
                                        <td></td>
                                    </tr>
                                    ";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
            
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item disabled">
                                <a class="page-link" href="#">Anterior</a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">Siguiente</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>