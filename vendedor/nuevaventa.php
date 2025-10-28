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
    session_start();
    
    $conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");

    if(!$conexion){
        echo "Un error de conexión ocurrió. <br>";
        exit;
    }

    // CONSULTA CORREGIDA - usar precio_venta en lugar de precio
    $result1 = pg_query($conexion, "SELECT cod_producto, nombre, precio_venta, stock FROM producto WHERE stock > 0 ORDER BY nombre");
    if(!$result1){
        echo "Error al cargar productos: " . pg_last_error($conexion);
        exit;
    }

    // Obtener métodos de pago
    $resultMetodos = pg_query($conexion, "SELECT cod_metodopago, nombre FROM metodopago");
    if(!$resultMetodos){
        echo "Error al cargar métodos de pago";
    }

    // Procesar búsqueda si se envió el formulario
    $resultadosBusqueda = [];
    if(isset($_GET['buscar']) && !empty($_GET['buscar'])) {
        $termino = pg_escape_string($conexion, $_GET['buscar']);
        $queryBusqueda = "SELECT cod_producto, nombre, precio_venta, stock 
                         FROM producto 
                         WHERE (nombre ILIKE '%$termino%' OR cod_producto ILIKE '%$termino%') 
                         AND stock > 0 
                         ORDER BY nombre";
        $resultBusqueda = pg_query($conexion, $queryBusqueda);
        if($resultBusqueda) {
            $resultadosBusqueda = pg_fetch_all($resultBusqueda);
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
                    <small id="userRole">Vendedor</small>
                </div>

                <div class="turno-info">
                    <div class="fw-bold">Carlos Rodríguez</div>
                    <small>Turno: 08:00 - 16:00</small><br>
                    <small id="tiempoActivoSidebar">0h 0m activo</small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.php" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="nuevaventa.php" class="nav-link active"><ul><i class="fas fa-cash-register"></i>Nueva Venta</ul></a>
                    <a href="registrodevolucion.php" class="nav-link"><ul><i class="fas fa-undo-alt"></i>Registrar Devolución</ul></a>
                    <a href="boletas-facturas.php" class="nav-link"><ul><i class="fas fa-receipt"></i>Boletas/Facturas</ul></a>
                     <a href="consulta-stock.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Consulta-stock</ul></a>
                    <a href="../login.html" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
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

            <div class="contenedor-venta">
                <section class="panel-busqueda">
                    <div class="busqueda-header">
                        <h3><i class="fas fa-search"></i> Buscar Producto</h3>
                    </div>

                    <!-- FORMULARIO DE BÚSQUEDA CON PHP -->
                    <form method="GET" action="">
                        <div class="busqueda-input">
                            <input type="text" name="buscar" id="inputBusqueda" 
                                   placeholder="Nombre o código del producto..." 
                                   value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>" 
                                   autofocus>
                            <button type="submit" id="btnBuscar" class="btn btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </form>

                    <div class="resultados-busqueda" id="resultadosBusqueda">
                        <?php if(isset($_GET['buscar']) && !empty($_GET['buscar'])): ?>
                            <?php if(!empty($resultadosBusqueda)): ?>
                                <h5>Resultados para: "<?php echo htmlspecialchars($_GET['buscar']); ?>"</h5>
                                <?php foreach($resultadosBusqueda as $producto): ?>
                                    <div class="resultado-item" onclick="agregarProductoDesdeBusqueda('<?php echo $producto['cod_producto']; ?>', '<?php echo addslashes($producto['nombre']); ?>', <?php echo $producto['precio_venta']; ?>, <?php echo $producto['stock']; ?>)">
                                        <div>
                                            <strong><?php echo $producto['nombre']; ?></strong>
                                            <br>
                                            <small>Código: <?php echo $producto['cod_producto']; ?></small>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-success">S/ <?php echo number_format($producto['precio_venta'], 2); ?></strong>
                                            <br>
                                            <span class="badge <?php echo $producto['stock'] > 10 ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo $producto['stock']; ?> unidades
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-search fa-2x mb-2"></i>
                                    <p>No se encontraron productos</p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-search fa-2x mb-2"></i>
                                <p>Ingresa un término de búsqueda</p>
                            </div>
                        <?php endif; ?>
                    </div>
                
                    <div class="productos-frecuentes">
                        <div class="mb-3">
                            <h4><i class="fas fa-boxes"></i> Productos Disponibles</h4>
                            <small class="text-muted">Haz clic en 'Agregar' para incluir en la venta</small>
                        </div>
                        
                        <!-- FILTRO PARA PRODUCTOS DISPONIBLES -->
                        <div class="filtro-productos">
                            <input type="text" id="filtroProductos" placeholder="Filtrar productos por código o nombre...">
                        </div>
                        
                        <div id="gridFrecuentes">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th>Precio Venta</th>
                                            <th>Stock</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaProductos">
                                        <?php
                                        // Reiniciar el puntero del resultado
                                        pg_result_seek($result1, 0);
                                        while($row1 = pg_fetch_assoc($result1)){
                                            echo "
                                            <tr>
                                                <td><strong>{$row1['cod_producto']}</strong></td>
                                                <td>{$row1['nombre']}</td>
                                                <td class='text-success'><strong>S/ " . number_format($row1['precio_venta'], 2) . "</strong></td>
                                                <td>
                                                    <span class='badge " . ($row1['stock'] > 10 ? 'bg-success' : 'bg-warning') . "'>
                                                        {$row1['stock']} unidades
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class='btn btn-sm btn-primary' onclick='agregarProducto(\"{$row1['cod_producto']}\", \"{$row1['nombre']}\", {$row1['precio_venta']}, {$row1['stock']})'>
                                                        <i class='fas fa-plus'></i> Agregar
                                                    </button>
                                                </td>
                                            </tr>
                                            ";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel-venta">
                    <div class="venta-header">
                        <h3><i class="fas fa-shopping-cart"></i> Venta Actual</h3>
                        <button id="btnLimpiar" class="btn btn-secondary">
                            <i class="fas fa-trash"></i> Limpiar Todo
                        </button>
                    </div>

                    <div class="lista-productos" id="listaProductosVenta">
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart fa-3x mb-3 text-muted"></i>
                            <p>No hay productos agregados</p>
                            <small class="text-muted">Busca y agrega productos para comenzar</small>
                        </div>
                    </div>

                    <div class="resumen-venta">
                        <div class="resumen-linea">
                            <span>Subtotal:</span>
                            <span id="subtotal">S/ 0.00</span>
                        </div>
                        <div class="resumen-linea">
                            <span>IGV (18%):</span>
                            <span id="igv">S/ 0.00</span>
                        </div>
                        <div class="resumen-linea total">
                            <span>TOTAL:</span>
                            <span id="totalVenta">S/ 0.00</span>
                        </div>

                        <div class="metodos-pago">
                            <h4><i class="fas fa-credit-card"></i> Método de Pago</h4>
                            <div class="metodos-grid">
                                <?php
                                if ($resultMetodos && pg_num_rows($resultMetodos) > 0) {
                                    while($metodo = pg_fetch_assoc($resultMetodos)) {
                                        $active = $metodo['cod_metodopago'] === 'EFE' ? 'active' : '';
                                        $icon = $metodo['cod_metodopago'] === 'EFE' ? 'fa-money-bill-wave' : 
                                               ($metodo['cod_metodopago'] === 'TAR' ? 'fa-credit-card' : 'fa-mobile-alt');
                                        echo "
                                        <button class='metodo-btn $active' data-metodo='{$metodo['cod_metodopago']}'>
                                            <i class='fas $icon'></i> {$metodo['nombre']}
                                        </button>
                                        ";
                                    }
                                }
                                ?>
                            </div>

                            <div class="monto-efectivo" id="montoEfectivo">
                                <label for="inputEfectivo">Efectivo recibido:</label>
                                <input type="number" id="inputEfectivo" placeholder="0.00" step="0.01" min="0">
                                <div class="cambio-info">
                                    Cambio: <span id="cambio">S/ 0.00</span>
                                </div>
                            </div>
                        </div>

                        <button id="btnFinalizar" class="btn btn-success btn-lg" disabled>
                            <i class="fas fa-check-circle"></i> Finalizar Venta
                        </button>
                    </div>
                </section>

                <!-- Modal de Confirmación -->
                <div id="modalConfirmacion" class="modal fade" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title"><i class="fas fa-check-circle"></i> Venta Exitosa</h5>
                            </div>
                            <div class="modal-body">
                                <div class="venta-resumen" id="resumenFinal"></div>
                            </div>
                            <div class="modal-footer">
                                <button id="btnNuevaVenta" class="btn btn-success">
                                    <i class="fas fa-receipt"></i> Nueva Venta
                                </button>
                                <button id="btnCerrarModal" class="btn btn-secondary" data-bs-dismiss="modal">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>
    // JavaScript para el filtro de productos disponibles
    document.addEventListener('DOMContentLoaded', function() {
        const filtroProductos = document.getElementById('filtroProductos');
        const tablaProductos = document.getElementById('tablaProductos');
        const filasProductos = tablaProductos.getElementsByTagName('tr');
        
        filtroProductos.addEventListener('input', function() {
            const filtro = this.value.toLowerCase();
            
            for (let i = 0; i < filasProductos.length; i++) {
                const fila = filasProductos[i];
                const celdas = fila.getElementsByTagName('td');
                let mostrarFila = false;
                
                if (celdas.length >= 2) {
                    const codigo = celdas[0].textContent.toLowerCase();
                    const nombre = celdas[1].textContent.toLowerCase();
                    
                    if (codigo.includes(filtro) || nombre.includes(filtro)) {
                        mostrarFila = true;
                    }
                }
                
                fila.style.display = mostrarFila ? '' : 'none';
            }
        });

        // Inicializar monto efectivo
        const montoEfectivo = document.getElementById('montoEfectivo');
        if (metodoPagoSeleccionado !== 'EFE') {
            montoEfectivo.style.display = 'none';
        }
    });

    // Variables globales para la venta
    let productosVenta = [];
    let metodoPagoSeleccionado = 'EFE';
    let subtotal = 0;
    let igv = 0;
    let total = 0;

    // Función mejorada para agregar productos
    function agregarProducto(codigo, nombre, precio, stock) {
        console.log('Agregando producto:', codigo, nombre, precio, stock);
        
        const productoExistente = productosVenta.find(p => p.codigo === codigo);
        
        if (productoExistente) {
            if (productoExistente.cantidad < stock) {
                productoExistente.cantidad++;
                productoExistente.total = productoExistente.cantidad * precio;
                console.log('Producto existente, cantidad aumentada:', productoExistente.cantidad);
            } else {
                alert('❌ No hay suficiente stock disponible');
                return;
            }
        } else {
            // Verificar stock antes de agregar
            if (stock <= 0) {
                alert('❌ Producto sin stock disponible');
                return;
            }
            
            productosVenta.push({
                codigo: codigo,
                nombre: nombre,
                precio: parseFloat(precio),
                cantidad: 1,
                total: parseFloat(precio),
                stock: stock
            });
            console.log('Nuevo producto agregado:', productosVenta[productosVenta.length - 1]);
        }
        
        actualizarVenta();
    }

    function agregarProductoDesdeBusqueda(codigo, nombre, precio, stock) {
        agregarProducto(codigo, nombre, precio, stock);
    }

    // Función mejorada para actualizar la venta
    function actualizarVenta() {
        const listaProductos = document.getElementById('listaProductosVenta');
        
        console.log('Actualizando venta, productos:', productosVenta);
        
        if (productosVenta.length === 0) {
            listaProductos.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-shopping-cart fa-3x mb-3 text-muted"></i>
                    <p>No hay productos agregados</p>
                    <small class="text-muted">Busca y agrega productos para comenzar</small>
                </div>
            `;
        } else {
            let html = '';
            subtotal = 0;
            
            productosVenta.forEach((producto, index) => {
                subtotal += producto.total;
                html += `
                    <div class="producto-venta">
                        <div class="producto-info">
                            <span class="producto-nombre"><strong>${producto.nombre}</strong></span>
                            <small class="text-muted">Código: ${producto.codigo}</small>
                            <br>
                            <span class="producto-precio">S/ ${producto.precio.toFixed(2)} c/u</span>
                        </div>
                        <div class="producto-controls">
                            <button class="btn btn-sm btn-outline-secondary" onclick="modificarCantidad(${index}, -1)">-</button>
                            <span class="cantidad badge bg-primary mx-2">${producto.cantidad}</span>
                            <button class="btn btn-sm btn-outline-secondary" onclick="modificarCantidad(${index}, 1)">+</button>
                            <span class="producto-total text-success mx-2"><strong>S/ ${producto.total.toFixed(2)}</strong></span>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarProducto(${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            listaProductos.innerHTML = html;
        }
        
        // Calcular totales
        igv = subtotal * 0.18;
        total = subtotal + igv;
        
        document.getElementById('subtotal').textContent = 'S/ ' + subtotal.toFixed(2);
        document.getElementById('igv').textContent = 'S/ ' + igv.toFixed(2);
        document.getElementById('totalVenta').textContent = 'S/ ' + total.toFixed(2);
        
        // Habilitar/deshabilitar botón finalizar
        const btnFinalizar = document.getElementById('btnFinalizar');
        btnFinalizar.disabled = productosVenta.length === 0;
        
        console.log('Totales calculados - Subtotal:', subtotal, 'IGV:', igv, 'Total:', total);
        
        // Actualizar cambio si es pago en efectivo
        if (metodoPagoSeleccionado === 'EFE') {
            actualizarCambio();
        }
    }

    function modificarCantidad(index, cambio) {
        const producto = productosVenta[index];
        const nuevaCantidad = producto.cantidad + cambio;
        
        console.log('Modificando cantidad:', producto.nombre, 'de', producto.cantidad, 'a', nuevaCantidad);
        
        if (nuevaCantidad <= 0) {
            eliminarProducto(index);
            return;
        }
        
        // Verificar stock máximo
        if (nuevaCantidad > producto.stock) {
            alert(`❌ No hay suficiente stock. Stock disponible: ${producto.stock}`);
            return;
        }
        
        producto.cantidad = nuevaCantidad;
        producto.total = producto.cantidad * producto.precio;
        actualizarVenta();
    }

    function eliminarProducto(index) {
        console.log('Eliminando producto:', productosVenta[index].nombre);
        productosVenta.splice(index, 1);
        actualizarVenta();
    }

    // Evento para limpiar venta
    document.getElementById('btnLimpiar').addEventListener('click', function() {
        if(productosVenta.length === 0) {
            alert('⚠️ No hay productos en la venta');
            return;
        }
        
        if(confirm('¿Estás seguro de que deseas limpiar toda la venta?')) {
            console.log('Limpiando toda la venta');
            productosVenta = [];
            actualizarVenta();
        }
    });

    // Configurar métodos de pago
    document.querySelectorAll('.metodo-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.metodo-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            metodoPagoSeleccionado = this.getAttribute('data-metodo');
            
            console.log('Método de pago seleccionado:', metodoPagoSeleccionado);
            
            const montoEfectivo = document.getElementById('montoEfectivo');
            if (metodoPagoSeleccionado === 'EFE') {
                montoEfectivo.style.display = 'block';
                actualizarCambio();
            } else {
                montoEfectivo.style.display = 'none';
            }
        });
    });

    function actualizarCambio() {
        const efectivo = parseFloat(document.getElementById('inputEfectivo').value) || 0;
        const cambio = efectivo - total;
        document.getElementById('cambio').textContent = 'S/ ' + (cambio > 0 ? cambio.toFixed(2) : '0.00');
        
        console.log('Cambio actualizado - Efectivo:', efectivo, 'Total:', total, 'Cambio:', cambio);
    }

    document.getElementById('inputEfectivo').addEventListener('input', actualizarCambio);

    // ===== FUNCIONES MEJORADAS PARA REGISTRAR VENTAS =====
    
    function generarIdVenta() {
        const ventasExistentes = JSON.parse(localStorage.getItem('ventasRecientes') || '[]');
        const nuevoId = 'VEN' + Date.now(); // ID único basado en timestamp
        return nuevoId;
    }

    function registrarVentaEnDashboard(totalVenta, productos) {
        try {
            console.log('Registrando venta en dashboard...');
            
            const ventasRecientes = JSON.parse(localStorage.getItem('ventasRecientes') || '[]');
            
            const nuevaVenta = {
                id: generarIdVenta(),
                total: parseFloat(totalVenta),
                productos: productos.map(p => `${p.nombre} (x${p.cantidad})`).join(', '),
                cantidad_productos: productos.reduce((sum, p) => sum + p.cantidad, 0),
                fecha: new Date().toLocaleString('es-PE'),
                metodo_pago: metodoPagoSeleccionado,
                hora: new Date().toLocaleTimeString('es-PE')
            };
            
            console.log('Nueva venta a registrar:', nuevaVenta);
            
            // Agregar al inicio del array
            ventasRecientes.unshift(nuevaVenta);
            
            // Mantener solo las últimas 10 ventas
            const ventasLimitadas = ventasRecientes.slice(0, 10);
            localStorage.setItem('ventasRecientes', JSON.stringify(ventasLimitadas));
            
            // Actualizar estadísticas del turno
            const estadisticasTurno = JSON.parse(localStorage.getItem('estadisticasTurno') || '{"ventasHoy": 0, "totalVendido": 0, "productosVendidos": 0}');
            estadisticasTurno.ventasHoy += 1;
            estadisticasTurno.totalVendido += parseFloat(totalVenta);
            estadisticasTurno.productosVendidos += nuevaVenta.cantidad_productos;
            estadisticasTurno.ultimaVenta = nuevaVenta.fecha;
            
            localStorage.setItem('estadisticasTurno', JSON.stringify(estadisticasTurno));
            
            console.log('✅ Venta registrada exitosamente en dashboard');
            console.log('Estadísticas actualizadas:', estadisticasTurno);
            
            return true;
            
        } catch (error) {
            console.error('❌ Error al registrar venta en dashboard:', error);
            return false;
        }
    }

    // EVENTO PRINCIPAL MEJORADO PARA FINALIZAR VENTA
    document.getElementById('btnFinalizar').addEventListener('click', function() {
        console.log('=== INICIANDO PROCESO DE VENTA ===');
        
        // Validaciones básicas
        if (productosVenta.length === 0) {
            alert('❌ No hay productos en la venta');
            return;
        }
        
        console.log('Productos en venta:', productosVenta);
        console.log('Método de pago:', metodoPagoSeleccionado);
        console.log('Total a pagar:', total);
        
        // Validar método de pago
        if (metodoPagoSeleccionado === 'EFE') {
            const efectivo = parseFloat(document.getElementById('inputEfectivo').value) || 0;
            console.log('Efectivo recibido:', efectivo);
            
            if (efectivo <= 0) {
                alert('❌ Ingrese el monto en efectivo recibido');
                document.getElementById('inputEfectivo').focus();
                return;
            }
            
            if (efectivo < total) {
                alert(`❌ El efectivo recibido (S/ ${efectivo.toFixed(2)}) es menor al total de la venta (S/ ${total.toFixed(2)})`);
                document.getElementById('inputEfectivo').focus();
                return;
            }
        }
        
        // Deshabilitar botón para evitar múltiples clics
        const btnFinalizar = this;
        btnFinalizar.disabled = true;
        btnFinalizar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        
        try {
            console.log('Procesando venta...');
            
            // Registrar venta en el dashboard
            const registroExitoso = registrarVentaEnDashboard(total, productosVenta);
            
            if (!registroExitoso) {
                throw new Error('Error al registrar la venta en el sistema');
            }
            
            // Preparar resumen para el modal
            const metodoPagoTexto = document.querySelector('.metodo-btn.active').textContent.trim();
            const cambio = metodoPagoSeleccionado === 'EFE' ? 
                (parseFloat(document.getElementById('inputEfectivo').value) - total).toFixed(2) : '0.00';
            
            document.getElementById('resumenFinal').innerHTML = `
                <div class="text-center">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h4 class="text-success">¡Venta Exitosa!</h4>
                    
                    <div class="mt-4 text-start">
                        <p><strong>Total Venta:</strong> S/ ${total.toFixed(2)}</p>
                        <p><strong>Método de Pago:</strong> ${metodoPagoTexto}</p>
                        ${metodoPagoSeleccionado === 'EFE' ? `<p><strong>Cambio:</strong> S/ ${cambio}</p>` : ''}
                        <p><strong>Productos Vendidos:</strong> ${productosVenta.length}</p>
                        <p><strong>Unidades Totales:</strong> ${productosVenta.reduce((sum, p) => sum + p.cantidad, 0)}</p>
                    </div>
                    
                    <div class="mt-3 p-2 bg-light rounded">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            La venta se ha registrado correctamente en el sistema
                        </small>
                    </div>
                </div>
            `;
            
            // Mostrar modal de confirmación
            const modalElement = document.getElementById('modalConfirmacion');
            const modal = new bootstrap.Modal(modalElement);
            
            // Configurar evento para cuando se cierre el modal
            const handleModalClose = function() {
                reiniciarVenta();
                modalElement.removeEventListener('hidden.bs.modal', handleModalClose);
            };
            
            modalElement.addEventListener('hidden.bs.modal', handleModalClose);
            
            // Mostrar modal
            modal.show();
            
            console.log('✅ Venta procesada exitosamente');
            
        } catch (error) {
            console.error('❌ Error al procesar la venta:', error);
            alert('❌ Error al procesar la venta: ' + error.message);
            
            // Rehabilitar botón
            btnFinalizar.disabled = false;
            btnFinalizar.innerHTML = '<i class="fas fa-check-circle"></i> Finalizar Venta';
        }
    });

    // Función para reiniciar completamente la venta
    function reiniciarVenta() {
        console.log('Reiniciando venta...');
        
        productosVenta = [];
        subtotal = 0;
        igv = 0;
        total = 0;
        
        // Actualizar interfaz
        actualizarVenta();
        document.getElementById('inputEfectivo').value = '';
        actualizarCambio();
        
        // Reactivar botón finalizar
        const btnFinalizar = document.getElementById('btnFinalizar');
        btnFinalizar.disabled = false;
        btnFinalizar.innerHTML = '<i class="fas fa-check-circle"></i> Finalizar Venta';
        
        // Limpiar filtro de búsqueda
        document.getElementById('filtroProductos').value = '';
        
        // Mostrar todas las filas de la tabla
        const filas = document.querySelectorAll('#tablaProductos tr');
        filas.forEach(fila => {
            fila.style.display = '';
        });
        
        console.log('✅ Venta reiniciada correctamente');
    }

    // Eventos para los botones del modal
    document.getElementById('btnNuevaVenta').addEventListener('click', function() {
        console.log('Iniciando nueva venta desde modal...');
        reiniciarVenta();
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmacion'));
        if (modal) {
            modal.hide();
        }
    });

    document.getElementById('btnCerrarModal').addEventListener('click', function() {
        console.log('Cerrando modal...');
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmacion'));
        if (modal) {
            modal.hide();
        }
    });

    // Función para cerrar turno
    function cerrarTurno() {
        if(productosVenta.length > 0) {
            if(!confirm('⚠️ Tienes una venta en proceso. ¿Estás seguro de que deseas cerrar el turno? Se perderá la venta actual.')) {
                return;
            }
        } else {
            if(!confirm('¿Estás seguro de que deseas cerrar el turno?')) {
                return;
            }
        }
        
        reiniciarVenta();
        localStorage.removeItem('estadisticasTurno');
        localStorage.removeItem('ventasRecientes');
        window.location.href = '../login.html';
    }

    // Función para limpiar modales bloqueantes (por si acaso)
    function verificarModalesBloqueantes() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            backdrop.remove();
        });
        
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }

    // Verificar cada 1 segundos por si aparece modal fantasma
    setInterval(verificarModalesBloqueantes, 1000);
</script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>