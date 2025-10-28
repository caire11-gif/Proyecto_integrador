<?php
    session_start();
    
    $conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");

    if(!$conexion){
        echo "Un error de conexión ocurrió. <br>";
        exit;
    }

    // Procesar búsqueda de documentos
    $resultadosBusqueda = [];
    if(isset($_GET['buscar']) && !empty($_GET['buscar'])) {
        $termino = pg_escape_string($conexion, $_GET['buscar']);
        $queryBusqueda = "SELECT * FROM tipodocumento 
                         WHERE nombre ILIKE '%$termino%' 
                         OR cod_tipodocumento ILIKE '%$termino%'
                         OR serie ILIKE '%$termino%'
                         ORDER BY nombre";
        $resultBusqueda = pg_query($conexion, $queryBusqueda);
        if($resultBusqueda) {
            $resultadosBusqueda = pg_fetch_all($resultBusqueda);
        }
    }

    // Obtener todos los tipos de documento para mostrar inicialmente
    $queryDocumentos = "SELECT * FROM tipodocumento ORDER BY nombre";
    $resultDocumentos = pg_query($conexion, $queryDocumentos);
    $todosDocumentos = [];
    if($resultDocumentos) {
        $todosDocumentos = pg_fetch_all($resultDocumentos);
    }
    ?>
    
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Registrar Devolución</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/vendedor-estilo.css">
    <style>
        /* Estilos específicos para devoluciones */
        .devoluciones-main {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .buscar-venta, .info-venta, .seleccion-productos, .registro-devolucion {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .busqueda-venta {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .busqueda-opciones {
            display: flex;
            gap: 20px;
        }

        .opcion-group {
            flex: 1;
        }

        .busqueda-input {
            display: flex;
            gap: 10px;
        }

        .busqueda-input input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .venta-detalle {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .productos-venta {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .producto-devolucion {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .producto-info {
            flex: 1;
        }

        .producto-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-devolucion {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            font-weight: bold;
            color: #2c3e50;
        }

        .form-group select, .form-group textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .resumen-devolucion {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #d4edda;
        }

        .total-devolucion {
            font-weight: bold;
            font-size: 1.2rem;
            color: #28a745;
            text-align: right;
            margin-top: 10px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .resultados-busqueda {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 10px;
            background: #f8f9fa;
        }

        .resultado-item {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .resultado-item:hover {
            background: #3498db;
            color: white;
        }

        .resultado-item:last-child {
            border-bottom: none;
        }

        .documento-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .documento-datos {
            flex: 1;
        }

        .documento-numero {
            background: #3498db;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
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
                    <a href="registrodevolucion.php" class="nav-link active"><ul><i class="fas fa-undo-alt"></i>Registrar Devolución</ul></a>
                    <a href="boletas-facturas.php" class="nav-link"><ul><i class="fas fa-receipt"></i>Boletas/Facturas</ul></a>
                     <a href="consulta-stock.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Consulta-stock</ul></a>
                    <a href="../login.html" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
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
                </div>
            </div>

            <main class="devoluciones-main">
                
                <section class="buscar-venta">
                    <h3><i class="fas fa-search"></i> Buscar Documentos</h3>
                    <div class="busqueda-venta">
                        <!-- FORMULARIO DE BÚSQUEDA CON PHP -->
                        <form method="GET" action="">
                            <div class="busqueda-input">
                                <input type="text" name="buscar" id="inputBusquedaVenta" 
                                       placeholder="Buscar por nombre, código o serie..." 
                                       value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>" 
                                       autofocus>
                                <button type="submit" id="btnBuscarVenta" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </form>

                        <!-- RESULTADOS DE BÚSQUEDA -->
                        <div class="resultados-busqueda" id="resultadosBusqueda">
                            <?php if(isset($_GET['buscar']) && !empty($_GET['buscar'])): ?>
                                <?php if(!empty($resultadosBusqueda)): ?>
                                    <h5>Resultados para: "<?php echo htmlspecialchars($_GET['buscar']); ?>"</h5>
                                    <?php foreach($resultadosBusqueda as $documento): ?>
                                        <div class="resultado-item" onclick="seleccionarDocumento(
                                            '<?php echo $documento['cod_tipodocumento']; ?>', 
                                            '<?php echo addslashes($documento['nombre']); ?>',
                                            '<?php echo $documento['serie']; ?>',
                                            <?php echo $documento['numero']; ?>
                                        )">
                                            <div class="documento-info">
                                                <div class="documento-datos">
                                                    <strong><?php echo $documento['nombre']; ?></strong>
                                                    <br>
                                                    <small>Código: <?php echo $documento['cod_tipodocumento']; ?></small>
                                                    <br>
                                                    <small>Serie: <?php echo $documento['serie']; ?></small>
                                                </div>
                                                <div class="documento-numero">
                                                    #<?php echo $documento['numero']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-search fa-2x mb-2"></i>
                                        <p>No se encontraron documentos</p>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- MOSTRAR TODOS LOS DOCUMENTOS AL INICIO -->
                                <h5>Todos los Tipos de Documento</h5>
                                <?php if(!empty($todosDocumentos)): ?>
                                    <?php foreach($todosDocumentos as $documento): ?>
                                        <div class="resultado-item" onclick="seleccionarDocumento(
                                            '<?php echo $documento['cod_tipodocumento']; ?>', 
                                            '<?php echo addslashes($documento['nombre']); ?>',
                                            '<?php echo $documento['serie']; ?>',
                                            <?php echo $documento['numero']; ?>
                                        )">
                                            <div class="documento-info">
                                                <div class="documento-datos">
                                                    <strong><?php echo $documento['nombre']; ?></strong>
                                                    <br>
                                                    <small>Código: <?php echo $documento['cod_tipodocumento']; ?></small>
                                                    <br>
                                                    <small>Serie: <?php echo $documento['serie']; ?></small>
                                                </div>
                                                <div class="documento-numero">
                                                    #<?php echo $documento['numero']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-file-alt fa-2x mb-2"></i>
                                        <p>No hay tipos de documento registrados</p>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                
                <section class="info-venta" id="seccionInfoVenta" style="display: none;">
                    <h3><i class="fas fa-file-invoice"></i> Información del Documento Seleccionado</h3>
                    <div class="venta-detalle" id="detalleVenta">
                        <!-- Información del documento aparecerá aquí -->
                    </div>
                </section>

                
                <section class="seleccion-productos" id="seccionProductos" style="display: none;">
                    <h3><i class="fas fa-shopping-cart"></i> Seleccionar Productos a Devolver</h3>
                    <div class="productos-venta" id="listaProductosVenta">
                        <!-- Productos aparecerán aquí -->
                    </div>
                </section>

                
                <section class="registro-devolucion" id="seccionRegistro" style="display: none;">
                    <h3><i class="fas fa-edit"></i> Registrar Devolución</h3>
                    <form id="formDevolucion" class="form-devolucion">
                        <div class="form-group">
                            <label for="selectMotivo">Motivo de la devolución:</label>
                            <select id="selectMotivo" required>
                                <option value="">Seleccionar motivo...</option>
                                <option value="defectuoso">Producto defectuoso</option>
                                <option value="insatisfecho">Cliente insatisfecho</option>
                                <option value="error">Error en la venta</option>
                                <option value="otro">Otro motivo</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="textareaObservaciones">Observaciones:</label>
                            <textarea id="textareaObservaciones" placeholder="Detalles adicionales..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="inputFoto">Subir foto (opcional):</label>
                            <input type="file" id="inputFoto" accept="image/*">
                        </div>

                        <div class="resumen-devolucion">
                            <h4>Resumen de Devolución</h4>
                            <div id="resumenProductosDevolucion"></div>
                            <div class="total-devolucion">
                                Total a devolver: <span id="totalDevolucion">S/ 0.00</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check-circle"></i> Procesar Devolución
                        </button>
                    </form>
                </section>
            </main>
        </div>
    </div>

    <!-- Modal de Confirmación -->
    <div id="modalDevolucion" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-check-circle"></i> Devolución Registrada</h3>
            <div id="resumenDevolucionFinal"></div>
            <div class="modal-actions">
                <button id="btnNuevaDevolucion" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Nueva Devolución
                </button>
                <button id="btnVolverDashboard" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Volver al Dashboard
                </button>
            </div>
        </div>
    </div>

    <script>
        function seleccionarDocumento(codigo, nombre, serie, numero) {
            // Mostrar información del documento seleccionado
            document.getElementById('seccionInfoVenta').style.display = 'block';
            document.getElementById('detalleVenta').innerHTML = `
                <p><strong>Documento:</strong> ${nombre}</p>
                <p><strong>Código:</strong> ${codigo}</p>
                <p><strong>Serie:</strong> ${serie}</p>
                <p><strong>Número:</strong> ${numero}</p>
                <p><strong>Fecha de selección:</strong> ${new Date().toLocaleString()}</p>
            `;

            // Mostrar sección de productos (simulada)
            document.getElementById('seccionProductos').style.display = 'block';
            document.getElementById('listaProductosVenta').innerHTML = `
                <div class="producto-devolucion">
                    <div class="producto-info">
                        <strong>Producto asociado al documento</strong>
                        <br>
                        <small>Documento: ${nombre} - ${serie}-${numero}</small>
                        <br>
                        <small>Precio: S/ 50.00</small>
                    </div>
                    <div class="producto-controls">
                        <button class="btn btn-sm btn-outline-secondary">-</button>
                        <span class="badge bg-primary">1</span>
                        <button class="btn btn-sm btn-outline-secondary">+</button>
                        <span class="text-success"><strong>S/ 50.00</strong></span>
                    </div>
                </div>
            `;

            // Mostrar sección de registro
            document.getElementById('seccionRegistro').style.display = 'block';
            document.getElementById('totalDevolucion').textContent = 'S/ 50.00';
            document.getElementById('resumenProductosDevolucion').innerHTML = `
                <p>1 producto - ${nombre}</p>
                <p><strong>Total:</strong> S/ 50.00</p>
            `;
        }

        function cerrarTurno() {
            if(confirm('¿Estás seguro de que deseas cerrar el turno?')) {
                window.location.href = '../login.html';
            }
        }

        // Manejar el formulario de devolución
        document.getElementById('formDevolucion').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Mostrar modal de confirmación
            document.getElementById('modalDevolucion').style.display = 'flex';
            document.getElementById('resumenDevolucionFinal').innerHTML = `
                <p>Devolución procesada exitosamente</p>
                <p><strong>Documento:</strong> ${document.querySelector('#detalleVenta p:first-child strong').nextSibling.textContent.trim()}</p>
                <p><strong>Total devuelto:</strong> S/ 50.00</p>
                <p><strong>Motivo:</strong> ${document.getElementById('selectMotivo').options[document.getElementById('selectMotivo').selectedIndex].text}</p>
            `;
        });

        // Botones del modal
        document.getElementById('btnNuevaDevolucion').addEventListener('click', function() {
            document.getElementById('modalDevolucion').style.display = 'none';
            // Recargar la página para nueva búsqueda
            window.location.href = 'registrodevolucion.php';
        });

        document.getElementById('btnVolverDashboard').addEventListener('click', function() {
            window.location.href = 'dashboard.php';
        });
    </script>
</body>
</html>