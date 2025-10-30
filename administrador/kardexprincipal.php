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
    <link rel="stylesheet" href="css/administrador-boton/boton.css">
</head>
<body>
    <?php
    $conexion=pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
    if(!$conexion){
        echo "Un error de conexión ocurrió.";
    }

    session_start();
    $usuarioadmin=$_SESSION['nombreusuarioadmin'];
    $apellidoadmin=$_SESSION['apellidousuarioadmin'];

    $inicialNombre = substr($usuarioadmin, 0, 1);
    $inicialApellido=substr($apellidoadmin,0,1);

    if (!isset($_SESSION['nombreusuarioadmin'])) {
        header("Location: ../login.php");
        exit;
    }

    $result1=pg_query($conexion,"SELECT nombre FROM producto");
    if(!$result1){
        echo "Error al seleccionar los productos.";
    }

    $result2=pg_query($conexion,"SELECT nombre FROM tipomovimiento");
    if(!$result2){
        echo "Error al seleccionar el tipo de movimiento.";
    }
    
    $codactu=$_POST['producto_codigo'] ?? '';
    $con=pg_query_params($conexion,"SELECT 1 FROM registroinventario  WHERE cod_producto=$1 LIMIT 1",array($codactu));
    if(!$con){
        echo "Error al seleccionar el código del producto";
        exit;
    }

    $result7=pg_query($conexion,"SELECT ri.fecha_inventario AS fecha,p.nombre AS producto_nombre,p.cod_producto AS producto_codigo,tm.nombre AS tipomovimiento_nombre,
                                 tm.cod_tipomovimiento AS tipomovimiento_codigo,u.usuario AS usuario_nombre,ri.cantidad AS cantidad,ri.precio_unitario AS precio_unitario,
                                 ri.total AS total FROM registroinventario ri
                                 JOIN producto p ON ri.cod_producto=p.cod_producto
                                 JOIN tipomovimiento tm ON ri.cod_tipomovimiento=tm.cod_tipomovimiento
                                 JOIN usuario u ON ri.cod_usuario=u.cod_usuario
                                 ORDER BY fecha_inventario desc");
    if(!$result7){
        echo "Error al seleccionar el registro del inventario." . pg_last_error($conexion);
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
                    <small id="userRole">Administrador</small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.php" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="kardexprincipal.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Kardex Principal</ul></a>
                    <a href="proveedores.php" class="nav-link"><ul><i class="fas fa-truck"></i>Proveedores</ul></a>
                    <a href="controlpersonal.php" class="nav-link"><ul><i class="fas fa-truck-loading"></i>Control de Personal</ul></a>
                    <a href="registroventas.php" class="nav-link"><ul><i class="fas fa-arrow-right"></i>Registro de Ventas</ul></a>
                </div>
            </div>
        </main>

        <div class="secundario">
            <div class="header">
                <div class="usuario-info">
                    <div class="usuario-avatar" id="usuarioAvatar"><?php echo htmlspecialchars($inicialNombre.$inicialApellido)?></div>
                    <div>
                        <div class="fw-bold fs-5" id="userName"><?php echo htmlspecialchars($usuarioadmin." ".$apellidoadmin) ?></div>
                        <small class="text-muted" id="userPosition">Administrador</small>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0"><i class="fas fa-chart-line me-2"></i>Kardex Principal</h1>
                <div>
                    <button class="btn btn-mad me-2">
                        <i class="fas fa-download me-2"></i>Exportar
                    </button>
                    <button class="btn btn-outline-secondary">
                        <i class="fas fa-filter me-2"></i>Filtros
                    </button>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Producto</label>
                            <select class="form-select" id="productFilter">
                                <option value="">Todos los productos</option>
                                <?php
                                while($row1=pg_fetch_assoc($result1)){
                                    echo"
                                    <option>$row1[nombre]</option>
                                    ";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Desde</label>
                            <input type="date" class="form-control" id="dateFrom">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Hasta</label>
                            <input type="date" class="form-control" id="dateTo">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo Movimiento</label>
                            <select class="form-select" id="movementType">
                                <option value="">Todos</option>
                                <?php
                                while($row2=pg_fetch_assoc($result2)){
                                    echo "
                                    <option>$row2[nombre]</option>
                                    ";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-0 bg-primary text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1">156</h4>
                            <small>Movimientos Hoy</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-success text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1">S/ 8,450</h4>
                            <small>Valor Entradas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-danger text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1">S/ 12,780</h4>
                            <small>Valor Salidas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-info text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-1">S/ 124,500</h4>
                            <small>Stock Valorizado</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0 text-primary"><i class="fas fa-list-alt me-2"></i>Registro de Movimientos - Septiembre 2025</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered text-center align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th rowspan="2" class="bg-light">Fecha</th>
                                    <th rowspan="2" class="bg-light">Usuario</th>
                                    <th rowspan="2" class="bg-light">Producto</th>
                                    <th colspan="3" class="bg-success text-white">Entradas</th>
                                    <th colspan="3" class="bg-danger text-white">Salidas</th>
                                    <th colspan="3" class="bg-primary text-white">Saldo Final</th>
                                </tr>
                                <tr>

                                    <th class="bg-success-light">Unidades</th>
                                    <th class="bg-success-light">Costo Unit.</th>
                                    <th class="bg-success-light">Costo Total</th>

                                    <th class="bg-danger-light">Unidades</th>
                                    <th class="bg-danger-light">Costo Unit.</th>
                                    <th class="bg-danger-light">Costo Total</th>

                                    <th class="bg-primary-light">Unidades</th>
                                    <th class="bg-primary-light">Costo Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                while($row7=pg_fetch_assoc($result7)){
                                    if($row7['tipomovimiento_codigo']==='mov001'){
                                        echo "
                                        <tr>
                                            <td>$row7[fecha]</td>
                                            <td>$row7[usuario_nombre]</td>
                                            <td>
                                                <div class='fw-bold text-primary'>$row7[producto_nombre]</div>
                                                <small class='text-muted'>$row7[producto_codigo]</small>
                                            </td>
                                            <td class='text-success fw-bold'>$row7[cantidad]</td>
                                            <td>$row7[precio_unitario]</td>
                                            <td class='fw-bold'>$row7[total]</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>-</td>
                                            ";
                                        echo "</tr>";
                                        
                                    } else if($row7['tipomovimiento_codigo']==='TM002'){
                                        echo "
                                            
                                        <td>$row7[fecha]</td>
                                            <td>$row7[usuario_nombre]</td>
                                            <td>
                                                <div class='fw-bold text-primary'>$row7[producto_nombre]</div>
                                                <small class='text-muted'>$row7[producto_codigo]</small>
                                            </td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td class='text-success fw-bold'>$row7[cantidad]</td>
                                            <td>$row7[precio_unitario]</td>
                                            <td class='fw-bold'>$row7[total]</td>
                                        </tr>
                                        ";
                                    }               
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
            
            
                    <nav>
                        <ul class="pagination justify-content-center mt-4">
                            <li class="page-item disabled"><a class="page-link" href="#">Anterior</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item"><a class="page-link" href="#">Siguiente</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <script>
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