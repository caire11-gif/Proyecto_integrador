<?php
session_start();

$conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");

if(!$conexion){
    echo "Un error de conexión ocurrió. <br>";
    exit;
}

// Procesar búsqueda
$resultadosBusqueda = [];
$filtroCategoria = '';
$filtroStock = '';

if(isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $termino = pg_escape_string($conexion, $_GET['buscar']);
    $filtroCategoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
    $filtroStock = isset($_GET['stock']) ? $_GET['stock'] : '';
    
    // Consulta con JOIN para obtener el nombre de la categoría
    $queryBusqueda = "SELECT p.cod_producto, p.nombre, p.precio_costo, p.precio_venta, 
                             p.unidades_por_caja, p.stock, c.nombre as categoria_nombre,
                             p.cod_categoria
                     FROM producto p
                     LEFT JOIN categoria c ON p.cod_categoria = c.cod_categoria
                     WHERE (p.nombre ILIKE '%$termino%' OR p.cod_producto ILIKE '%$termino%')";
    
    if(!empty($filtroCategoria)) {
        $queryBusqueda .= " AND p.cod_categoria = '$filtroCategoria'";
    }

    if($filtroStock === 'bajo') {
    $queryBusqueda .= " AND p.stock <= 10";
} elseif($filtroStock === 'agotado') {
    $queryBusqueda .= " AND p.stock = 0";
} elseif($filtroStock === 'normal') {
    $queryBusqueda .= " AND p.stock > 10";
}
    
    $queryBusqueda .= " ORDER BY p.nombre";
    
    $resultBusqueda = pg_query($conexion, $queryBusqueda);
    if($resultBusqueda) {
        $resultadosBusqueda = pg_fetch_all($resultBusqueda);
    }
}

// Obtener categorías para el filtro
$queryCategorias = "SELECT cod_categoria, nombre FROM categoria ORDER BY nombre";
$resultCategorias = pg_query($conexion, $queryCategorias);
$categorias = [];
if($resultCategorias) {
    $categorias = pg_fetch_all($resultCategorias);
}

// Obtener estadísticas
$queryTotal = "SELECT COUNT(*) as total FROM producto";
$queryBajo = "SELECT COUNT(*) as bajo FROM producto WHERE stock <= 10";
$queryAgotado = "SELECT COUNT(*) as agotado FROM producto WHERE stock = 0";

$resultTotal = pg_query($conexion, $queryTotal);
$resultBajo = pg_query($conexion, $queryBajo);
$resultAgotado = pg_query($conexion, $queryAgotado);

$totalProductos = pg_fetch_assoc($resultTotal)['total'];
$stockBajo = pg_fetch_assoc($resultBajo)['bajo'];
$stockAgotado = pg_fetch_assoc($resultAgotado)['agotado'];

// Obtener productos para mostrar inicialmente
if(empty($resultadosBusqueda)) {
    $queryProductos = "SELECT p.cod_producto, p.nombre, p.precio_costo, p.precio_venta, 
                              p.unidades_por_caja, p.stock, c.nombre as categoria_nombre,
                              p.cod_categoria
                       FROM producto p
                       LEFT JOIN categoria c ON p.cod_categoria = c.cod_categoria
                       ORDER BY p.nombre 
                       LIMIT 50";
    $resultProductos = pg_query($conexion, $queryProductos);
    if($resultProductos) {
        $resultadosBusqueda = pg_fetch_all($resultProductos);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Stock - MAD MARKET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/vendedor-estilo.css">
    <style>
        .stock-main {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .stock-controls {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .filtros-row {
            display: flex;
            gap: 20px;
            align-items: end;
        }

        .filtro-group {
            flex: 1;
        }

        .filtro-group label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
            display: block;
        }

        .filtro-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .btn-notificar-almacen {
            background: #ffc107;
            color: #212529;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        .busqueda-stock {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .search-box-stock {
            position: relative;
            max-width: 600px;
        }

        .search-box-stock i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .search-box-stock input {
            width: 100%;
            padding: 12px 45px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 1rem;
        }

        .estadisticas-stock {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 5px solid #3498db;
        }

        .stat-card.warning {
            border-left-color: #ffc107;
        }

        .stat-card.danger {
            border-left-color: #dc3545;
        }

        .stat-icon {
            font-size: 2.5rem;
            color: #3498db;
        }

        .stat-card.warning .stat-icon {
            color: #ffc107;
        }

        .stat-card.danger .stat-icon {
            color: #dc3545;
        }

        .stat-number {
            display: block;
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .tabla-stock-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .tabla-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-exportar {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        .tabla-stock {
            overflow-x: auto;
        }

        .tabla-stock table {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-stock th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }

        .tabla-stock td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .tabla-stock tr:hover {
            background: #f8f9fa;
        }

        .badge-stock {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }

        .alertas-stock {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .alerta-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .badge-alerta {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: bold;
        }

        .text-margen {
            color: #17a2b8;
            font-weight: bold;
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
                    <small id="tiempoActivoSidebar">0h 0m activo</small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.php" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="nuevaventa.php" class="nav-link"><ul><i class="fas fa-cash-register"></i>Nueva Venta</ul></a>
                    <a href="registrodevolucion.php" class="nav-link"><ul><i class="fas fa-undo-alt"></i>Registrar Devolución</ul></a>
                   <a href="boletas-facturas.php" class="nav-link active"><ul><i class="fas fa-receipt"></i>Boletas/Facturas</ul></a>
                    <a href="consultastock.php" class="nav-link active"><ul><i class="fas fa-boxes"></i>Consultar Stock</ul></a>
                    <a href="../login.php" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
                </div>
            </div>
        </main>

        <div class="secundario">
            <div class="header">
              
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

            <div class="stock-main">
                <!-- Controles y Filtros -->
                <div class="stock-controls">
                    <form method="GET" action="">
                        <div class="filtros-row">
                            <div class="filtro-group">
                                <label for="filtroCategoria">
                                    <i class="fas fa-tags"></i> Categoría:
                                </label>
                                <select id="filtroCategoria" name="categoria">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['cod_categoria']; ?>" 
                                                <?php echo ($filtroCategoria == $categoria['cod_categoria']) ? 'selected' : ''; ?>>
                                            <?php echo $categoria['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filtro-group">
                                <label for="filtroStock">
                                    <i class="fas fa-filter"></i> Estado Stock:
                                </label>
                                <select id="filtroStock" name="stock">
                                    <option value="">Todos</option>
                                    <option value="bajo" <?php echo ($filtroStock == 'bajo') ? 'selected' : ''; ?>>Stock Bajo</option>
                                    <option value="agotado" <?php echo ($filtroStock == 'agotado') ? 'selected' : ''; ?>>Agotados</option>
                                    <option value="normal" <?php echo ($filtroStock == 'normal') ? 'selected' : ''; ?>>Stock Normal</option>
                                </select>
                            </div>

                            <button type="submit" class="btn-notificar-almacen">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Búsqueda -->
                <div class="busqueda-stock">
                    <form method="GET" action="">
                        <div class="search-box-stock">
                            <i class="fas fa-search"></i>
                            <input type="text" name="buscar" id="buscarProducto" 
                                   placeholder="Buscar producto por nombre, código o categoría..."
                                   value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
                        </div>
                    </form>
                </div>

                <!-- Estadísticas -->
                <div class="estadisticas-stock">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number" id="totalProductos"><?php echo $totalProductos; ?></span>
                            <span class="stat-label">Total Productos</span>
                        </div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number" id="stockBajo"><?php echo $stockBajo; ?></span>
                            <span class="stat-label">Stock Bajo</span>
                        </div>
                    </div>
                    
                    <div class="stat-card danger">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number" id="stockAgotado"><?php echo $stockAgotado; ?></span>
                            <span class="stat-label">Agotados</span>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Productos -->
                <div class="tabla-stock-container">
                    <div class="tabla-header">
                        <h3><i class="fas fa-list"></i> Inventario de Productos</h3>
                        <button id="btnExportar" class="btn-exportar">
                            <i class="fas fa-download"></i> Exportar CSV
                        </button>
                    </div>
                    
                    <div class="tabla-stock">
                        <table>
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Precio Costo</th>
                                    <th>Precio Venta</th>
                                    <th>Margen</th>
                                    <th>Unid. x Caja</th>
                                    <th>Stock</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tablaProductos">
                                <?php if(!empty($resultadosBusqueda)): ?>
                                    <?php foreach($resultadosBusqueda as $producto): ?>
                                        <tr>
                                            <td><strong><?php echo $producto['cod_producto']; ?></strong></td>
                                            <td><?php echo $producto['nombre']; ?></td>
                                            <td><?php echo $producto['categoria_nombre'] ?? 'Sin categoría'; ?></td>
                                            <td>S/ <?php echo number_format($producto['precio_costo'], 2); ?></td>
                                            <td class="text-success"><strong>S/ <?php echo number_format($producto['precio_venta'], 2); ?></strong></td>
                                            <td class="text-margen">
                                                <?php 
                                                $margen = $producto['precio_venta'] - $producto['precio_costo'];
                                                echo 'S/ ' . number_format($margen, 2);
                                                ?>
                                            </td>
                                            <td><?php echo $producto['unidades_por_caja']; ?></td>
                                            <td>
                                                <?php 
                                                $stock = $producto['stock'];
                                                $badgeClass = 'badge-success';
                                                if($stock == 0) {
                                                    $badgeClass = 'badge-danger';
                                                } elseif($stock <= 10) {
                                                    $badgeClass = 'badge-warning';
                                                }
                                                ?>
                                                <span class="badge-stock <?php echo $badgeClass; ?>">
                                                    <?php echo $stock; ?> unidades
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                if($stock == 0) {
                                                    echo '<span class="text-danger"><i class="fas fa-times-circle"></i> Agotado</span>';
                                                } elseif($stock <= 10) {
                                                    echo '<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Stock Bajo</span>';
                                                } else {
                                                    echo '<span class="text-success"><i class="fas fa-check-circle"></i> Disponible</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            <i class="fas fa-search fa-2x mb-2"></i>
                                            <p>No se encontraron productos</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Alertas de Stock -->
                <?php if($stockBajo > 0 || $stockAgotado > 0): ?>
                <div class="alertas-stock" id="alertasStock">
                    <div class="alerta-header">
                        <h3><i class="fas fa-bell"></i> Alertas de Stock</h3>
                        <span class="badge-alerta" id="contadorAlertas"><?php echo $stockBajo + $stockAgotado; ?></span>
                    </div>
                    <div class="lista-alertas" id="listaAlertas">
                        <?php if($stockAgotado > 0): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle"></i> 
                                <strong><?php echo $stockAgotado; ?> productos agotados</strong> - Necesita reposición inmediata
                            </div>
                        <?php endif; ?>
                        <?php if($stockBajo > 0): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong><?php echo $stockBajo; ?> productos con stock bajo</strong> - Considere reponer pronto
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function cerrarTurno() {
            if(confirm('¿Estás seguro de que deseas cerrar el turno?')) {
                window.location.href = '../login.html';
            }
        }

        // Exportar a CSV
        document.getElementById('btnExportar').addEventListener('click', function() {
            alert('Funcionalidad de exportación CSV - Próximamente');
        });
    </script>
</body>
</html>