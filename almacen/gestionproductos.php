<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Sistema de Encargado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/almacen-gestionproductos/productos.css">
    <link rel="stylesheet" href="css/almacen-interfaz/interfaz.css">
</head>
<body>
    <?php
        $conexion=pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");

        if(!$conexion){
            echo "Un error de conexión ocurrió.";
            exit;
        }

        $result1=pg_query($conexion, "SELECT nombre FROM categoria");
        if(!$result1){
            echo "Un error de conexión ocurrió.";
            exit;
        }

        $result2=pg_query($conexion, "SELECT nombre FROM proveedor");
        if(!$result2){
            echo "Un error de conexión ocurrió.";
            exit;
        }

        $result3=pg_query($conexion, "SELECT p.cod_producto,p.nombre AS producto_nombre,p.precio,p.stock,
                                      c.nombre AS categoria_nombre,pro.nombre AS proveedor_nombre FROM producto p
                                      JOIN categoria c ON p.cod_categoria=c.cod_categoria
                                      JOIN proveedor pro ON p.cod_proveedor=pro.cod_proveedor");
        if(!$result3){
            echo "Un error de conexión ocurrió.";
            exit;
        }

        if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='insertar')){
            $codprod=$_POST['codigoProducto'] ?? '';
            $nombreprod=$_POST['nombreProducto'] ?? '';
            $precioprod=$_POST['precioProducto'] ?? '';
            $stockprod=$_POST['stockProducto'] ?? '';
            
            $cateprod=$_POST['categoriaProducto'] ?? '';
            $proveprod=$_POST['proveedorProducto'] ?? '';

            $consul1=pg_query_params($conexion,"SELECT cod_proveedor FROM proveedor WHERE nombre=$1",array($proveprod));
            if(!$consul1){
                echo "Error al seleccionar el proveedor.";
                exit;
            } else{
                $con1=pg_fetch_assoc($consul1);
                if(!$con1){
                    echo "No se encontró ningún proveedor con el nombre: $proveprod.";
                    exit;
                }
            }

            $consul2=pg_query_params($conexion,"SELECT cod_categoria FROM categoria WHERE nombre=$1",array($cateprod));
            if(!$consul2){
                echo "Error al seleccionar la categoria.";
                exit;
            } else {
                $con2=pg_fetch_assoc($consul2);
                if(!$con2){
                    echo "No se encontró ninguna categoría con el nombre: $cateprod.";
                    exit;
                }
            }

            $codprove=trim($con1['cod_proveedor'] ?? '');
            $codcate=trim($con2['cod_categoria'] ?? '');

            $sql1="INSERT INTO producto(cod_producto,nombre,precio,stock,cod_categoria,cod_proveedor) VALUES ($1,$2,$3,$4,$5,$6)";
            $result4=pg_query_params($conexion,$sql1,array($codprod,$nombreprod,$precioprod,$stockprod,$codcate,$codprove));

            if(!$result4){
                echo "Un error de conexión ocurrió.";
                exit;
            }

            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit;
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
                    <a href="notificaciones.php" class="nav-link"><ul><i class="fas fa-bell"></i>Notificaciones</ul></a>
                    <a href="reportes.php" class="nav-link"><ul><i class="fas fa-chart-bar"></i>Reportes</ul></a>
                    <a href="#" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
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
            <div class="contenedor-productos">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>   
                        <h4 class="mb-1"><i class="fas fa-boxes me-2"></i>Gestión de Productos</h4>
                        <p class="text-muted mb-0">Administrar inventario y catálogo de productos</p>
                    </div>
                    <div>
                        <button class="btn btn-success me-2" id="btnExportar">
                            <i class="fas fa-file-excel me-2"></i>Exportar
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProducto" id="btnNuevoProducto">
                            <i class="fas fa-plus me-2"></i>Nuevo Producto
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-content">
                        <div class="card-header">
                            <h5 class="card-title" id="cardTitulo">Nuevo Producto</h5>
                        </div>
                        <div class="card-body">
                            <form id="formProducto" method="POST">
                                <input type="hidden" id="productoId">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label" for="nombreProducto">Nombre del Producto *</label>
                                        <input type="text" class="form-control" id="nombreProducto" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="codigoProducto">Código *</label>
                                        <input type="text" class="form-control" id="codigoProducto" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="categoriaProducto">Categoría *</label>
                                        <select class="form-select" id="categoriaProducto" name="categoriaProducto" required>
                                            <option value="">Seleccione...</option>
                                            <?php
                                            while($row1=pg_fetch_assoc($result1)){
                                                echo "
                                                <option>$row1[nombre]</option>
                                                ";
                                            }
                                            ?>
                                        </select>
                                    </div>  
                                    <div class="col-md-6">
                                        <label class="form-label" for="proveedorProducto">Proveedor *</label>
                                        <select class="form-select" id="proveedorProducto" name="proveedorProducto" required>
                                            <option value="">Seleccione...</option>
                                            <?php
                                            while($row2=pg_fetch_assoc($result2)){
                                                echo "
                                                <option>$row2[nombre]</option>
                                                ";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="precioProducto">Precio Costo (S/) *</label>
                                        <input type="number" class="form-control" id="precioProducto" step="0.01" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="stockProducto">Stock *</label>
                                        <input type="number" class="form-control" id="stockProducto" required>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <input type="submit" class="btn btn-primary" id="btnGuardarProducto" name="accion" value="insertar">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Buscar producto</label>
                                <input type="text" class="form-control" id="buscarProducto" placeholder="Nombre o referencia...">
                            </div>

                            <div class="col-md-3">
                                <label for="categoriaProducto" class="form-label">Categoría</label>
                                <select class="form-select" name="categoriaProducto" id="categoriaSeleccionada" onchange="this.form.submit()">
                                    <option>-- Categorías --</option>
                                    <?php
                                        while($row1=pg_fetch_assoc($result1)){
                                            echo "
                                            <option>$row1[nombre]</option>
                                            ";
                                        }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="proveedorProducto">Proveedor</label>
                                <select class="form-select" id="filtroProveedor" name="proveedorProducto">
                                    <option value="">-- Proveedores --</option>
                                    <?php
                                    while($row2=pg_fetch_assoc($result2)){
                                        echo "
                                        <option>$row2[nombre]</option>
                                        ";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-outline-secondary w-100" id="btnLimpiarFiltros">
                                    <i class="fas fa-redo me-2"></i>Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Lista de Productos</h5>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Precio</th>
                                        <th>Stock</th>
                                        <th>Categoría</th>
                                        <th>Proveedor</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    while($row3=pg_fetch_assoc($result3)){
                                        echo "
                                        <tr>
                                            <td>$row3[cod_producto]</td>
                                            <td>$row3[producto_nombre]</td>
                                            <td>$row3[precio]</td>
                                            <td>$row3[stock]</td>
                                            <td>$row3[categoria_nombre]</td>
                                            <td>$row3[proveedor_nombre]</td>
                                        </tr>
                                        ";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>