<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Sistema de Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/vendedor-estilo.css">
</head>
<body>
    <?php
        $conexion=pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");

        if(!$conexion){
            echo "Un error de conexión ocurrió. <br>";
            exit;
        }

        $result1=pg_query($conexion, "SELECT cod_producto,nombre,precio,stock FROM producto");
        if(!$result1){
            echo "Un error de conexión ocurrió.";
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
                    <a href="#" class="nav-link"><ul><i class="fas fa-receipt"></i>Boletas/Facturas</ul></a>
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

            <div class="contenedor-venta">
                <section class="panel-busqueda">
                    <div class="busqueda-header">
                        <h3>🔍 Buscar Producto</h3>
                    </div>

                    <div class="busqueda-input">
                        <input type="text" id="inputBusqueda" placeholder="Nombre o código..." autofocus>
                        <button id="btnBuscar" class="btn btn-primary">Buscar</button>
                    </div>

                    <div class="resultados-busqueda" id="resultadosBusqueda">
                
                    </div>
                
                    <div class="productos-frecuentes">
                        <div>
                            <h4>Lista de Producto</h4>
                        </div>
                        
                        <div id="gridFrecuentes">
                            <table class="table table-hover mb-0 text-center">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Precio</th>
                                        <th>Stock</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    while($row1=pg_fetch_assoc($result1)){
                                        echo "
                                        <tr>
                                            <td>$row1[cod_producto]</td>
                                            <td>$row1[nombre]</td>
                                            <td>$row1[precio]</td>
                                            <td>$row1[stock]</td>
                                        </tr>
                                        ";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

        
                <section class="panel-venta">
                    <div class="venta-header">
                        <h3>Venta Actual</h3>
                        <button id="btnLimpiar" class="btn btn-secondary">Limpiar Todo</button>
                    </div>

                    <div class="lista-productos" id="listaProductosVenta">
                        <div class="empty-state">
                            <p>No hay productos agregados</p>
                            <small>Busca y agrega productos para comenzar</small>
                        </div>
                    </div>

                    <div class="resumen-venta">
                        <div class="resumen-linea">
                            <span>Subtotal:</span>
                            <span id="subtotal">$0.00</span>
                        </div>
                        <div class="resumen-linea">
                            <span>IGV (18%):</span>
                            <span id="igv">$0.00</span>
                        </div>
                        <div class="resumen-linea total">
                            <span>TOTAL:</span>
                            <span id="totalVenta">$0.00</span>
                        </div>

                        <div class="metodos-pago">
                            <h4>💳 Método de Pago</h4>
                            <div class="metodos-grid">
                                <button class="metodo-btn" data-metodo="efectivo">💵 Efectivo</button>
                                <button class="metodo-btn" data-metodo="tarjeta">💳 Tarjeta</button>
                                <button class="metodo-btn" data-metodo="transferencia">📱 Transferencia</button>
                            </div>

                            <div class="monto-efectivo" id="montoEfectivo">
                                <label for="inputEfectivo">Efectivo recibido:</label>
                                <input type="number" id="inputEfectivo" placeholder="0.00" step="0.01">
                                <div class="cambio-info">
                                    Cambio: <span id="cambio">$0.00</span>
                                </div>
                            </div>
                        </div>

                        <button id="btnFinalizar" class="btn btn-success btn-large" disabled>Finalizar venta</button>
                    </div>
                </section>

                <div id="modalConfirmacion" class="modal">
                    <div class="modal-content">
                        <h3>✅ Venta Registrada Exitosamente</h3>
                        <div class="venta-resumen" id="resumenFinal"></div>
                        <div class="modal-actions">
                            <button id="btnNuevaVenta" class="btn btn-success">🧾 Nueva Venta</button>
                            <button id="btnCerrarModal" class="btn btn-secondary">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>