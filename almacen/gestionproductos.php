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
            header("Location: ../login.html");
            exit;
        }

        // Obtener categorías y proveedores para los selects - CORREGIDO
        $result1 = pg_query($conexion, "SELECT cod_categoria, nombre FROM categoria");
        if(!$result1){
            echo "Error al cargar categorías.";
        }

        // CORRECCIÓN: Cambiar 'nombre' por 'razon_social'
        $result2 = pg_query($conexion, "SELECT cod_proveedor, razon_social FROM proveedor");
        if(!$result2){
            echo "Error al cargar proveedores.";
        }

        // Obtener productos con información de categoría y proveedor - CORREGIDO
        $result3 = pg_query($conexion, "SELECT 
                                        p.cod_producto,
                                        p.nombre AS producto_nombre,
                                        p.precio_caja AS precio_costo,
                                        p.precio_compra_unidad,
                                        p.precio_venta,
                                        p.stock,
                                        p.unidades_por_caja,
                                        c.nombre AS categoria_nombre,
                                        pro.razon_social AS proveedor_nombre,
                                        c.cod_categoria,
                                        pro.cod_proveedor
                                    FROM producto p
                                    JOIN categoria c ON p.cod_categoria = c.cod_categoria
                                    JOIN proveedor pro ON p.cod_proveedor = pro.cod_proveedor
                                    ORDER BY p.nombre");
        if(!$result3){
            echo "Error al cargar productos.";
        }

        // Procesar el formulario cuando se envía - CORREGIDO
        if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])){
            if($_POST['accion'] === 'insertar'){
                $codprod = $_POST['codigoProducto'] ?? '';
                $nombreprod = $_POST['nombreProducto'] ?? '';
                $precio_costo = $_POST['precioCosto'] ?? '';
                $precio_venta = $_POST['precioVenta'] ?? '';
                $stockprod = $_POST['stockProducto'] ?? '';
                $unidades_caja = $_POST['unidadesCaja'] ?? '';
                $categoria_id = $_POST['categoriaProducto'] ?? '';
                $proveedor_id = $_POST['proveedorProducto'] ?? '';

                // Validar que todos los campos requeridos estén presentes
                if(empty($codprod) || empty($nombreprod) || empty($precio_costo) || empty($precio_venta) || 
                    empty($unidades_caja) || empty($categoria_id) || empty($proveedor_id)) {
                    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                                        <script>
                                            document.addEventListener('DOMContentLoaded', function(){
                                                Swal.fire({
                                                    icon: 'warning',
                                                    title: 'Faltan campos',
                                                    text: 'Complete todos los campos',
                                                    width: '350px'
                                                }).then(() => {
                                                    window.location.href = '" . $_SERVER['PHP_SELF'] . "';
                                                });
                                            });
                                        </script>";
                                        exit;
                    
                } else {
                    try {
                        // Verificar si el código ya existe
                        $check_sql = "SELECT COUNT(*) FROM producto WHERE cod_producto = $1";
                        $check_result = pg_query_params($conexion, $check_sql, array($codprod));
                        $exists = pg_fetch_result($check_result, 0, 0);
                        
                        if($exists > 0) {
                            echo "<script>alert('El código de producto ya existe');</script>";
                        } else {
                            // CORRECCIÓN: Calcular precio_compra_unidad automáticamente
                            $precio_compra_unidad = $precio_costo / $unidades_caja;

                            // CORRECCIÓN: Insertar producto con precio_caja y precio_compra_unidad
                            $sql = "INSERT INTO producto (cod_producto, nombre, precio_caja, precio_compra_unidad, precio_venta, unidades_por_caja, cod_categoria, cod_proveedor) 
                                    VALUES ($1, $2, $3, $4, $5, $6, $7, $8)";
                            $result = pg_query_params($conexion, $sql, array(
                                $codprod, $nombreprod, $precio_costo, $precio_compra_unidad, $precio_venta, 
                                $unidades_caja, $categoria_id, $proveedor_id
                            ));

                            if($result) {
                                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                                        <script>
                                            document.addEventListener('DOMContentLoaded', function(){
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Producto registrado',
                                                    text: 'Se registró el producto correctamente',
                                                    width: '350px'
                                                }).then(() => {
                                                    window.location.href = '" . $_SERVER['PHP_SELF'] . "';
                                                });
                                            });
                                        </script>";
                                        exit;
                            } else {
                                echo "<script>alert('Error al registrar el producto: " . pg_last_error($conexion) . "');</script>";
                            }
                        }
                    } catch (Exception $e) {
                        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
                    }
                }
                exit;
            }
            else if($_POST['accion'] === 'editar'){
                $codprod = $_POST['codigoProducto'] ?? '';
                $nombreprod = $_POST['nombreProducto'] ?? '';
                $precio_costo = $_POST['precioCosto'] ?? '';
                $precio_venta = $_POST['precioVenta'] ?? '';
                $stockprod = $_POST['stockProducto'] ?? '';
                $unidades_caja = $_POST['unidadesCaja'] ?? '';
                $categoria_id = $_POST['categoriaProducto'] ?? '';
                $proveedor_id = $_POST['proveedorProducto'] ?? '';

                try {
                    // CORRECCIÓN: Calcular precio_compra_unidad para la actualización
                    $precio_compra_unidad = $precio_costo / $unidades_caja;

                    // CORRECCIÓN: Actualizar producto con precio_caja y precio_compra_unidad
                    $sql = "UPDATE producto SET 
                            nombre = $1, 
                            precio_caja = $2,
                            precio_compra_unidad = $3,
                            precio_venta = $4, 
                            unidades_por_caja = $5, 
                            cod_categoria = $6, 
                            cod_proveedor = $7 
                            WHERE cod_producto = $8";
                    
                    $result = pg_query_params($conexion, $sql, array(
                        $nombreprod, $precio_costo, $precio_compra_unidad, $precio_venta, $unidades_caja, 
                        $categoria_id, $proveedor_id, $codprod
                    ));

                    if($result) {
                        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function(){
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Producto actualizado',
                                            text: 'Se actualizó el producto correctamente',
                                            width: '350px'
                                        }).then(() => {
                                            window.location.href = '" . $_SERVER['PHP_SELF'] . "';
                                        });                                        
                                    });
                                </script>";
                        exit;
                    } else {
                        echo "<script>alert('Error al actualizar el producto');</script>";
                    }
                } catch (Exception $e) {
                    echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
                }
            }
            elseif($_POST['accion'] === 'eliminar'){
                $codprod = $_POST['codigoProducto'] ?? '';
                
                if(empty($codprod)) {
                    echo "<script>alert('Código de producto no especificado');</script>";
                } else {
                    try {
                        // Verificar si el producto tiene movimientos relacionados
                        $check_movimientos = "SELECT COUNT(*) FROM detallecompra WHERE cod_producto = $1 
                                             UNION ALL 
                                             SELECT COUNT(*) FROM detalleventa WHERE cod_producto = $1 
                                             UNION ALL 
                                             SELECT COUNT(*) FROM movimiento WHERE cod_producto = $1";
                        $result_check = pg_query_params($conexion, $check_movimientos, array($codprod));
                        $total_movimientos = 0;
                        while($row = pg_fetch_array($result_check)) {
                            $total_movimientos += $row[0];
                        }
                        
                        if($total_movimientos > 0) {
                            echo "<script>
                                alert('No se puede eliminar el producto porque tiene movimientos relacionados en el sistema');
                            </script>";
                        } else {
                            $sql = "DELETE FROM producto WHERE cod_producto = $1";
                            $result = pg_query_params($conexion, $sql, array($codprod));

                            if($result) {
                                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                                        <script>
                                            document.addEventListener('DOMContentLoaded', function(){
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Producto eliminado',
                                                    text: 'Se eliminó el producto correctamente',
                                                    width: '350px'
                                                }).then(() => {
                                                    window.location.href = '" . $_SERVER['PHP_SELF'] . "';
                                                });
                                            });
                                        </script>";
                                exit;
                            } else {
                                echo "<script>alert('Error al eliminar el producto');</script>";
                            }
                        }
                    } catch (Exception $e) {
                        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
                    }
                }
            }
        }

        // Obtener datos de un producto para editar (si se solicita)
        $producto_editar = null;
        if(isset($_GET['editar'])) {
            $cod_producto = $_GET['editar'];
            $sql_editar = "SELECT * FROM producto WHERE cod_producto = $1";
            $result_editar = pg_query_params($conexion, $sql_editar, array($cod_producto));
            $producto_editar = pg_fetch_assoc($result_editar);
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
                    <a href="dashboard.html" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="gestionproductos.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Gestión de Productos</ul></a>
                    <a href="almacenproveedores.html" class="nav-link"><ul><i class="fas fa-truck"></i>Proveedores</ul></a>
                    <a href="entradaproveedor.php" class="nav-link active"><ul><i class="fas fa-truck-loading"></i>Entradas Proveedor</ul></a>
                    <a href="registrodevolucioncompra.php" class="nav-link"><ul><i class="fas fa-chart-bar"></i>Devoluciones</ul></a>
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
                                <a href="../login.php" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
                            </ul>
                        </div>
                    </div> 
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
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProducto" id="btnNuevoProducto" onclick="limpiarFormulario()">
                            <i class="fas fa-plus me-2"></i>Nuevo Producto
                        </button>
                    </div>
                </div>

                <!-- Modal para nuevo/editar producto -->
                <div class="modal fade" id="modalProducto" tabindex="-1" aria-labelledby="modalProductoLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalProductoLabel"><?php echo $producto_editar ? 'Editar Producto' : 'Nuevo Producto'; ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="formProducto" method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="accion" value="<?php echo $producto_editar ? 'editar' : 'insertar'; ?>">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="codigoProducto">Código del Producto *</label>
                                            <input type="text" class="form-control" id="codigoProducto" name="codigoProducto" 
                                                   value="<?php echo $producto_editar ? $producto_editar['cod_producto'] : ''; ?>" 
                                                   <?php echo $producto_editar ? 'readonly' : 'required'; ?>>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="nombreProducto">Nombre del Producto *</label>
                                            <input type="text" class="form-control" id="nombreProducto" name="nombreProducto" 
                                                   value="<?php echo $producto_editar ? $producto_editar['nombre'] : ''; ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="precioCosto">Precio Costo (S/) * <small class="text-muted">(Precio por caja)</small></label>
                                            <input type="number" class="form-control" id="precioCosto" name="precioCosto" step="0.01" 
                                                   value="<?php echo $producto_editar ? $producto_editar['precio_caja'] : ''; ?>" required  
                                                   oninput="calcularCostoUnitario()">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="precioVenta">Precio Venta (S/) *</label>
                                            <input type="number" class="form-control" id="precioVenta" name="precioVenta" step="0.01" 
                                                   value="<?php echo $producto_editar ? $producto_editar['precio_venta'] : ''; ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="unidadesCaja">Unidades por Caja *</label>
                                            <input type="number" class="form-control" id="unidadesCaja" name="unidadesCaja" 
                                                   value="<?php echo $producto_editar ? $producto_editar['unidades_por_caja'] : ''; ?>" required 
                                                   oninput="calcularCostoUnitario()">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="costoUnitario">Costo Unitario (S/)</label>
                                            <input type="text" class="form-control bg-light" id="costoUnitario" readonly 
                                                   placeholder="Se calcula automáticamente">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="stockProducto">Stock *</label>
                                            <input type="number" class="form-control" id="stockProducto" name="stockProducto" 
                                                   value="<?php echo $producto_editar ? $producto_editar['stock'] : ''; ?>" disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="categoriaProducto">Categoría *</label>
                                            <select class="form-select" id="categoriaProducto" name="categoriaProducto" required>
                                                <option value="">Seleccione categoría...</option>
                                                <?php
                                                if($result1) {
                                                    pg_result_seek($result1, 0);
                                                    while($row1 = pg_fetch_assoc($result1)){
                                                        $selected = ($producto_editar && $producto_editar['cod_categoria'] == $row1['cod_categoria']) ? 'selected' : '';
                                                        echo "<option value='{$row1['cod_categoria']}' $selected>{$row1['nombre']}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="proveedorProducto">Proveedor *</label>
                                            <select class="form-select" id="proveedorProducto" name="proveedorProducto" required>
                                                <option value="">Seleccione proveedor...</option>
                                                <?php
                                                if($result2) {
                                                    pg_result_seek($result2, 0);
                                                    while($row2 = pg_fetch_assoc($result2)){
                                                        $selected = ($producto_editar && $producto_editar['cod_proveedor'] == $row2['cod_proveedor']) ? 'selected' : '';
                                                        // CORRECCIÓN: Mostrar razon_social en lugar de nombre
                                                        echo "<option value='{$row2['cod_proveedor']}' $selected>{$row2['razon_social']}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary"><?php echo $producto_editar ? 'Actualizar Producto' : 'Guardar Producto'; ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal de confirmación para eliminar -->
                <div class="modal fade" id="modalConfirmarEliminar" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirmar Eliminación</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>¿Está seguro de que desea eliminar este producto?</p>
                                <form id="formEliminarProducto" method="POST">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="codigoProducto" id="codigoProductoEliminar">
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-danger" onclick="document.getElementById('formEliminarProducto').submit()">Eliminar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros de búsqueda -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Buscar producto</label>
                                <input type="text" class="form-control" id="buscarProducto" placeholder="Nombre o código...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Categoría</label>
                                <select class="form-select" id="filtroCategoria">
                                    <option value="">Todas las categorías</option>
                                    <?php
                                    if($result1) {
                                        pg_result_seek($result1, 0);
                                        while($row1 = pg_fetch_assoc($result1)){
                                            echo "<option value='{$row1['cod_categoria']}'>{$row1['nombre']}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Proveedor</label>
                                <select class="form-select" id="filtroProveedor">
                                    <option value="">Todos los proveedores</option>
                                    <?php
                                    if($result2) {
                                        pg_result_seek($result2, 0);
                                        while($row2 = pg_fetch_assoc($result2)){
                                            // CORRECCIÓN: Mostrar razon_social en lugar de nombre
                                            echo "<option value='{$row2['cod_proveedor']}'>{$row2['razon_social']}</option>";
                                        }
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

                <!-- Lista de productos -->
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
                                        <th>Precio Costo</th>
                                        <th>Precio Venta</th>
                                        <th>Stock</th>
                                        <th>Unidades/Caja</th>
                                        <th>Categoría</th>
                                        <th>Proveedor</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if($result3 && pg_num_rows($result3) > 0) {
                                        pg_result_seek($result3, 0);
                                        while($row3 = pg_fetch_assoc($result3)){
                                            echo "
                                            <tr>
                                                <td><strong>{$row3['cod_producto']}</strong></td>
                                                <td>{$row3['producto_nombre']}</td>
                                                <td>S/ {$row3['precio_costo']}</td>
                                                <td>S/ {$row3['precio_venta']}</td>
                                                <td>
                                                    <span class='badge " . ($row3['stock'] > 10 ? 'bg-success' : 'bg-warning') . "'>
                                                        {$row3['stock']} unidades
                                                    </span>
                                                </td>
                                                <td>{$row3['unidades_por_caja']}</td>
                                                <td>{$row3['categoria_nombre']}</td>
                                                <td>{$row3['proveedor_nombre']}</td>
                                                <td>
                                                    <a href='gestionproductos.php?editar={$row3['cod_producto']}' class='btn btn-sm btn-outline-primary' title='Editar'>
                                                        <i class='fas fa-edit'></i>
                                                    </a>
                                                    <button class='btn btn-sm btn-outline-danger' title='Eliminar' onclick='confirmarEliminacion(\"{$row3['cod_producto']}\")'>
                                                        <i class='fas fa-trash'></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            ";
                                        }
                                    } else {
                                        echo "<tr><td colspan='9' class='text-center'>No hay productos registrados</td></tr>";
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Función para calcular costo unitario automáticamente
        function calcularCostoUnitario() {
            const precioCosto = parseFloat(document.getElementById('precioCosto').value) || 0;
            const unidadesCaja = parseInt(document.getElementById('unidadesCaja').value) || 1;
            
            if (unidadesCaja > 0 && precioCosto > 0) {
                const costoUnitario = precioCosto / unidadesCaja;
                document.getElementById('costoUnitario').value = 'S/ ' + costoUnitario.toFixed(2);
            } else {
                document.getElementById('costoUnitario').value = '';
            }
        }

        // Calcular automáticamente al cargar la página si hay datos de edición
        document.addEventListener('DOMContentLoaded', function() {
            calcularCostoUnitario();
            
            <?php if($producto_editar): ?>
            const modal = new bootstrap.Modal(document.getElementById('modalProducto'));
            modal.show();
            <?php endif; ?>
        });

        // Funciones JavaScript
        function cerrarTurno() {
            if(confirm('¿Está seguro de que desea cerrar el turno?')) {
                alert('Turno cerrado correctamente');
            }
        }

        // Limpiar filtros
        document.getElementById('btnLimpiarFiltros').addEventListener('click', function() {
            document.getElementById('buscarProducto').value = '';
            document.getElementById('filtroCategoria').selectedIndex = 0;
            document.getElementById('filtroProveedor').selectedIndex = 0;
            
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });
        });

        // Función para confirmar eliminación
        function confirmarEliminacion(codigoProducto) {
            document.getElementById('codigoProductoEliminar').value = codigoProducto;
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmarEliminar'));
            modal.show();
        }

        // Función para limpiar formulario al crear nuevo producto
        function limpiarFormulario() {
            document.querySelector('input[name="codigoProducto"]').removeAttribute('readonly');
            document.getElementById('costoUnitario').value = '';
        }

        // Buscar productos en tiempo real
        document.getElementById('buscarProducto').addEventListener('input', aplicarFiltros);
        document.getElementById('filtroCategoria').addEventListener('change', aplicarFiltros);
        document.getElementById('filtroProveedor').addEventListener('change', aplicarFiltros);

        function aplicarFiltros() {
            const searchTerm = document.getElementById('buscarProducto').value.toLowerCase();
            const categoriaValue = document.getElementById('filtroCategoria').value;
            const proveedorValue = document.getElementById('filtroProveedor').value;
            
            const categoriaText = categoriaValue ? 
                document.getElementById('filtroCategoria').options[document.getElementById('filtroCategoria').selectedIndex].textContent : '';
            const proveedorText = proveedorValue ? 
                document.getElementById('filtroProveedor').options[document.getElementById('filtroProveedor').selectedIndex].textContent : '';
            
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                if (row.cells.length < 8) return;
                
                const nombreProducto = row.cells[1].textContent.toLowerCase();
                const codigoProducto = row.cells[0].textContent.toLowerCase();
                const categoriaProducto = row.cells[6].textContent;
                const proveedorProducto = row.cells[7].textContent;
                
                const matchSearch = !searchTerm || nombreProducto.includes(searchTerm) || codigoProducto.includes(searchTerm);
                const matchCategoria = !categoriaValue || categoriaProducto === categoriaText;
                const matchProveedor = !proveedorValue || proveedorProducto === proveedorText;
                
                row.style.display = (matchSearch && matchCategoria && matchProveedor) ? '' : 'none';
            });
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Sistema de gestión de productos cargado');
        });
        
        document.getElementById("formProducto").addEventListener("submit",function(event){
            const codprod=document.getElementById("codigoProducto").value.trim();
            const nomprod=document.getElementById("nombreProducto").value.trim();
            const unicajaprod=document.getElementById("unidadesCaja").value.trim();
            const precosprod=document.getElementById("precioCosto").value.trim();
            const prevenprod=document.getElementById("precioVenta").value.trim();
            const stocprod=document.getElementById("stockProducto").value.trim();


            if(codprod===""){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El código no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(codprod.length>10){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El código debe ser menor de 10 dígitos",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            const regexunistock=/^\d+$/;

            if(unicajaprod===""){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "Las unidades por caja no pueden estar vacía",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(!regexunistock.test(unicajaprod)){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "Las unidades por caja deben ser solamente numérico",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(parseInt(unicajaprod)<1){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "No pueden haber menos de 1 unidad en una caja",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            const regexnom = /^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

            if(nomprod===""){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El nombre no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(!regexnom.test(nomprod)){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El nombre debe empezar con mayúscula y contener solo letras",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            const regexpre=/^\d{1,10}(\.\d{1,2})?$/

            if(precosprod===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El precio de costo no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(!regexpre.test(precosprod)){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "Formato inválido. Por ejemplo: 1234567890.12 ",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(parseFloat(precosprod)<0){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El precio de costo no puede ser negativo",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(prevenprod===""){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El precio de venta no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(!regexpre.test(prevenprod)){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "Formato inválido. Por ejemplo: 1234567890.12 ",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(parseFloat(prevenprod)<0){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El precio de venta no puede ser negativo",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(stockprod===""){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El stock no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(!regexunistock.test(stockprod)){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El stock debe ser solamente numérico",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(parseInt(stockprod)<0){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El stock no puede ser menor negativo",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }
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