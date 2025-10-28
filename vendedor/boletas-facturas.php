<?php
session_start();

$conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");

if(!$conexion){
    echo "Un error de conexión ocurrió. <br>";
    exit;
}

// Obtener productos
$resultProductos = pg_query($conexion, "SELECT cod_producto, nombre, precio_venta, stock FROM producto WHERE stock > 0 ORDER BY nombre");
if(!$resultProductos){
    echo "Error al cargar productos.";
}

// Obtener métodos de pago
$resultMetodos = pg_query($conexion, "SELECT cod_metodopago, nombre FROM metodopago");
if(!$resultMetodos){
    echo "Error al cargar métodos de pago.";
}

// Obtener siguiente número de venta
$resultNumero = pg_query($conexion, "SELECT COALESCE(MAX(CAST(SUBSTRING(cod_venta FROM 2) AS INTEGER)), 0) + 1 as siguiente_numero FROM venta WHERE cod_venta LIKE 'V%'");
$siguienteNumero = "V" . str_pad(pg_fetch_result($resultNumero, 0, 'siguiente_numero'), 6, '0', STR_PAD_LEFT);

// Obtener historial de documentos (consulta corregida)
$resultHistorial = pg_query($conexion, "
    SELECT v.cod_venta, v.fecha_venta, v.total,
           u.usuario, m.nombre as metodo_pago
    FROM venta v 
    LEFT JOIN usuario u ON v.cod_usuario = u.cod_usuario
    LEFT JOIN metodopago m ON v.cod_metodopago = m.cod_metodopago
    ORDER BY v.fecha_venta DESC 
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Boletas y Facturas - MAD MARKET</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/vendedor-estilo.css">
  <style>
    .documentos-main {
        padding: 20px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .configuracion-documento, .vista-previa, .orial-documentos {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border: 1px solid #e9ecef;
    }

    .config-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
    }

    .config-group {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .config-group label {
        font-weight: bold;
        color: #2c3e50;
        font-size: 1.1rem;
    }

    .tipo-documento {
        display: flex;
        gap: 15px;
    }

    .tipo-option {
        flex: 1;
    }

    .tipo-option input[type="radio"] {
        display: none;
    }

    .tipo-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
        background: #f8f9fa;
    }

    .tipo-option input[type="radio"]:checked + .tipo-label {
        border-color: #3498db;
        background: #e3f2fd;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
    }

    .tipo-icon {
        font-size: 2rem;
        margin-bottom: 8px;
    }

    .tipo-name {
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .tipo-desc {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .datos-cliente {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .datos-cliente input {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
    }

    .seleccion-productos {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .cantidad-producto {
        display: flex;
        gap: 10px;
    }

    .cantidad-producto input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
    }

    .config-impresion {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .impresion-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        background: #f8f9fa;
    }

    .impresion-option input[type="radio"] {
        margin: 0;
    }

    .documento-preview {
        border: 2px solid #2c3e50;
        border-radius: 12px;
        overflow: hidden;
        background: white;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .documento-header {
        background: linear-gradient(135deg, #2c3e50, #3498db);
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .doc-tipo {
        font-size: 1.5rem;
        font-weight: bold;
    }

    .doc-numero {
        font-size: 1.2rem;
        font-weight: bold;
    }

    .documento-body {
        padding: 25px;
    }

    .doc-cliente {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #3498db;
    }

    .doc-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .doc-table th {
        background: #2c3e50;
        color: white;
        padding: 12px;
        text-align: left;
        font-weight: bold;
    }

    .doc-table td {
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
    }

    .doc-table tr:hover {
        background: #f8f9fa;
    }

    .doc-totales {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .total-line {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 1.1rem;
    }

    .total-final {
        font-weight: bold;
        font-size: 1.3rem;
        color: #2c3e50;
        border-top: 2px solid #dee2e6;
        padding-top: 10px;
        margin-top: 10px;
    }

    .doc-acciones {
        display: flex;
        gap: 15px;
        justify-content: center;
    }

    .filtros-orial {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        align-items: center;
    }

    .filtros-orial select, .filtros-orial input {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
    }

    .tabla-documentos {
        overflow-x: auto;
    }

    .documentos-table {
        width: 100%;
        border-collapse: collapse;
    }

    .documentos-table th {
        background: #2c3e50;
        color: white;
        padding: 12px;
        text-align: left;
        font-weight: bold;
    }

    .documentos-table td {
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
    }

    .documentos-table tr:hover {
        background: #f8f9fa;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .btn-primary { background: #3498db; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-info { background: #17a2b8; color: white; }
    .btn-warning { background: #ffc107; color: #212529; }

    .form-control {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
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

      <main class="documentos-main">
        
        <!-- Configuración del Documento -->
        <section class="configuracion-documento">
          <h2><i class="fas fa-cog"></i> Configurar Documento</h2>
          <div class="config-grid">

            <!-- Tipo de Documento -->
            <div class="config-group">
              <label>Tipo de Documento:</label>
              <div class="tipo-documento">
                <div class="tipo-option">
                  <input type="radio" id="tipoBoleta" name="tipoDocumento" value="boleta" checked>
                  <label for="tipoBoleta" class="tipo-label">
                    <span class="tipo-icon"><i class="fas fa-receipt"></i></span>
                    <span class="tipo-name">BOLETA</span>
                    <span class="tipo-desc">Consumidor final</span>
                  </label>
                </div>
                <div class="tipo-option">
                  <input type="radio" id="tipoFactura" name="tipoDocumento" value="factura">
                  <label for="tipoFactura" class="tipo-label">
                    <span class="tipo-icon"><i class="fas fa-file-invoice"></i></span>
                    <span class="tipo-name">FACTURA</span>
                    <span class="tipo-desc">Con RUC</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Datos del Cliente (solo para facturas) -->
            <div class="config-group" id="grupoCliente" style="display:none;">
              <label><i class="fas fa-user"></i> Datos del Cliente:</label>
              <div class="datos-cliente">
                <input type="text" id="inputRUC" placeholder="RUC (11 dígitos)" maxlength="11" class="form-control">
                <input type="text" id="inputRazonSocial" placeholder="Razón Social" class="form-control">
                <input type="text" id="inputDireccion" placeholder="Dirección" class="form-control">
                <button id="btnBuscarSunat" class="btn btn-info">
                  <i class="fas fa-search"></i> Buscar en SUNAT
                </button>
              </div>
            </div>

            <!-- Selección de Productos -->
            <div class="config-group">
              <label><i class="fas fa-boxes"></i> Seleccionar Productos:</label>
              <div class="seleccion-productos">
                <select id="selectProducto" class="form-control">
                  <option value="">Seleccione un producto</option>
                  <?php while ($producto = pg_fetch_assoc($resultProductos)): ?>
                    <option value="<?php echo $producto['cod_producto']; ?>" 
                            data-precio="<?php echo $producto['precio_venta']; ?>"
                            data-stock="<?php echo $producto['stock']; ?>">
                      <?php echo $producto['nombre']; ?> - S/ <?php echo number_format($producto['precio_venta'], 2); ?> (Stock: <?php echo $producto['stock']; ?>)
                    </option>
                  <?php endwhile; ?>
                </select>
                <div class="cantidad-producto">
                  <input type="number" id="inputCantidad" placeholder="Cantidad" min="1" value="1" class="form-control">
                  <button id="btnAgregarProducto" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Agregar
                  </button>
                </div>
              </div>
            </div>

            <!-- Método de Pago -->
            <div class="config-group">
              <label><i class="fas fa-credit-card"></i> Método de Pago:</label>
              <select id="selectMetodoPago" class="form-control">
                <?php while ($metodo = pg_fetch_assoc($resultMetodos)): ?>
                  <option value="<?php echo $metodo['cod_metodopago']; ?>"><?php echo $metodo['nombre']; ?></option>
                <?php endwhile; ?>
              </select>
            </div>

            <!-- Configuración de Impresión -->
            <div class="config-group">
              <label><i class="fas fa-print"></i> Configuración de Impresión:</label>
              <div class="config-impresion">
                <div class="impresion-option">
                  <input type="radio" id="impresionAuto" name="impresion" value="auto" checked>
                  <label for="impresionAuto"><i class="fas fa-print"></i> Imprimir automáticamente</label>
                </div>
                <div class="impresion-option">
                  <input type="radio" id="impresionEmail" name="impresion" value="email">
                  <label for="impresionEmail"><i class="fas fa-envelope"></i> Enviar por email</label>
                  <input type="email" id="inputEmail" placeholder="correo@ejemplo.com" class="form-control" style="display:none;">
                </div>
              </div>
            </div>

          </div>
        </section>

        <!-- Vista Previa -->
        <section class="vista-previa">
          <h2><i class="fas fa-eye"></i> Vista Previa del Documento</h2>
          <div class="documento-preview">
            <div class="documento-header">
              <div class="doc-tipo" id="previewTipo">BOLETA DE VENTA</div>
              <div class="doc-numero">N° <span id="previewNumero"><?php echo $siguienteNumero; ?></span></div>
            </div>
            
            <div class="documento-body">
              <div class="doc-cliente" id="previewCliente">
                <strong>Cliente:</strong> CONSUMIDOR FINAL
              </div>

              <div class="doc-detalles">
                <table class="doc-table">
                  <thead>
                    <tr>
                      <th>Cant</th>
                      <th>Descripción</th>
                      <th>P. Unit.</th>
                      <th>Total</th>
                      <th>Acción</th>
                    </tr>
                  </thead>
                  <tbody id="previewProductos">
                    <tr id="sinProductos">
                      <td colspan="5" style="text-align: center;">No hay productos agregados</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="doc-totales">
                <div class="total-line">
                  <span>Subtotal:</span>
                  <span id="previewSubtotal">S/ 0.00</span>
                </div>
                <div class="total-line">
                  <span>IGV (18%):</span>
                  <span id="previewIGV">S/ 0.00</span>
                </div>
                <div class="total-line total-final">
                  <span>TOTAL:</span>
                  <span id="previewTotal">S/ 0.00</span>
                </div>
              </div>
              
              <div class="doc-acciones">
                <button id="btnGenerarDocumento" class="btn btn-success" disabled>
                  <i class="fas fa-file-export"></i> Generar Documento
                </button>
                <button id="btnLimpiar" class="btn btn-secondary">
                  <i class="fas fa-broom"></i> Limpiar Todo
                </button>
              </div>
            </div>
          </div>
        </section>

        <!-- Historial de Documentos -->
<section class="historial-documentos">
  <h2><i class="fas fa-history"></i> Historial de Documentos</h2>
  <div class="filtros-historial">
    <select id="filtroTipoDoc" class="form-control">
      <option value="">Todos los tipos</option>
      <option value="boleta">Boletas</option>
      <option value="factura">Facturas</option>
    </select>
    <input type="date" id="filtroFecha" class="form-control">
    <button id="btnBuscarHistorial" class="btn btn-primary">
      <i class="fas fa-search"></i> Buscar
    </button>
  </div>

  <div class="tabla-documentos">
    <table class="documentos-table">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Número</th>
          <th>Usuario</th>
          <th>Método Pago</th>
          <th>Total</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tbodyDocumentos">
        <?php if ($resultHistorial && pg_num_rows($resultHistorial) > 0): ?>
          <?php while ($doc = pg_fetch_assoc($resultHistorial)): ?>
            <tr>
              <td><?php echo date('d/m/Y', strtotime($doc['fecha_venta'])); ?></td>
              <td><strong><?php echo $doc['cod_venta']; ?></strong></td>
              <td><?php echo $doc['usuario']; ?></td>
              <td><?php echo $doc['metodo_pago']; ?></td>
              <td class="text-success"><strong>S/ <?php echo number_format($doc['total'], 2); ?></strong></td>
              <td>
                <button class='btn btn-primary btn-sm' onclick='verDocumento("<?php echo $doc['cod_venta']; ?>")'>
                  <i class="fas fa-eye"></i> Ver
                </button>
                <button class='btn btn-success btn-sm' onclick='imprimirDocumento("<?php echo $doc['cod_venta']; ?>")'>
                  <i class="fas fa-print"></i> Imprimir
                </button>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align: center;">No hay documentos registrados</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

  <script>
    function cerrarTurno() {
      if(confirm('¿Estás seguro de que deseas cerrar el turno?')) {
        window.location.href = '../login.html';
      }
    }

    // Array para almacenar productos seleccionados
    let productosSeleccionados = [];
    let subtotal = 0;
    let igv = 0;
    let total = 0;

    // Mostrar/ocultar datos del cliente según tipo de documento
    document.querySelectorAll('input[name="tipoDocumento"]').forEach(radio => {
      radio.addEventListener('change', function() {
        const grupoCliente = document.getElementById('grupoCliente');
        const previewTipo = document.getElementById('previewTipo');
        const previewCliente = document.getElementById('previewCliente');
        
        if (this.value === 'factura') {
          grupoCliente.style.display = 'block';
          previewTipo.textContent = 'FACTURA';
          previewCliente.innerHTML = '<strong>Cliente:</strong> <span id="clientePreview">INGRESE RUC</span>';
        } else {
          grupoCliente.style.display = 'none';
          previewTipo.textContent = 'BOLETA DE VENTA';
          previewCliente.innerHTML = '<strong>Cliente:</strong> CONSUMIDOR FINAL';
        }
      });
    });

    // Mostrar/ocultar campo email
    document.querySelectorAll('input[name="impresion"]').forEach(radio => {
      radio.addEventListener('change', function() {
        const inputEmail = document.getElementById('inputEmail');
        if (this.value === 'email') {
          inputEmail.style.display = 'block';
        } else {
          inputEmail.style.display = 'none';
        }
      });
    });

    // Agregar producto al documento
    document.getElementById('btnAgregarProducto').addEventListener('click', function() {
      const select = document.getElementById('selectProducto');
      const cantidad = parseInt(document.getElementById('inputCantidad').value);
      const productoSeleccionado = select.options[select.selectedIndex];
      
      if (!productoSeleccionado.value) {
        alert('Seleccione un producto');
        return;
      }
      
      if (cantidad < 1) {
        alert('La cantidad debe ser mayor a 0');
        return;
      }
      
      const stock = parseInt(productoSeleccionado.getAttribute('data-stock'));
      if (cantidad > stock) {
        alert('No hay suficiente stock. Stock disponible: ' + stock);
        return;
      }
      
      const producto = {
        codigo: productoSeleccionado.value,
        nombre: productoSeleccionado.text.split(' - ')[0],
        precio: parseFloat(productoSeleccionado.getAttribute('data-precio')),
        cantidad: cantidad,
        total: parseFloat(productoSeleccionado.getAttribute('data-precio')) * cantidad
      };
      
      productosSeleccionados.push(producto);
      actualizarVistaPrevia();
    });

    // Actualizar vista previa
    function actualizarVistaPrevia() {
      const tbody = document.getElementById('previewProductos');
      const sinProductos = document.getElementById('sinProductos');
      
      if (productosSeleccionados.length === 0) {
        sinProductos.style.display = 'table-row';
        tbody.innerHTML = '<tr id="sinProductos"><td colspan="5" style="text-align: center;">No hay productos agregados</td></tr>';
      } else {
        sinProductos.style.display = 'none';
        
        let html = '';
        subtotal = 0;
        
        productosSeleccionados.forEach((producto, index) => {
          subtotal += producto.total;
          html += `
            <tr>
              <td>${producto.cantidad}</td>
              <td>${producto.nombre}</td>
              <td>S/ ${producto.precio.toFixed(2)}</td>
              <td>S/ ${producto.total.toFixed(2)}</td>
              <td><button class="btn btn-secondary btn-sm" onclick="eliminarProducto(${index})"><i class="fas fa-trash"></i></button></td>
            </tr>
          `;
        });
        
        tbody.innerHTML = html;
      }
      
      // Calcular totales
      igv = subtotal * 0.18;
      total = subtotal + igv;
      
      document.getElementById('previewSubtotal').textContent = 'S/ ' + subtotal.toFixed(2);
      document.getElementById('previewIGV').textContent = 'S/ ' + igv.toFixed(2);
      document.getElementById('previewTotal').textContent = 'S/ ' + total.toFixed(2);
      
      // Habilitar/deshabilitar botón generar
      document.getElementById('btnGenerarDocumento').disabled = productosSeleccionados.length === 0;
    }

    // Eliminar producto
    function eliminarProducto(index) {
      productosSeleccionados.splice(index, 1);
      actualizarVistaPrevia();
    }

    // Limpiar todo
    document.getElementById('btnLimpiar').addEventListener('click', function() {
      productosSeleccionados = [];
      actualizarVistaPrevia();
    });

    // Generar documento
    document.getElementById('btnGenerarDocumento').addEventListener('click', function() {
      if (productosSeleccionados.length === 0) {
        alert('Agregue al menos un producto');
        return;
      }
      
      const tipoDocumento = document.querySelector('input[name="tipoDocumento"]:checked').value;
      const metodoPago = document.getElementById('selectMetodoPago').value;
      
      // Aquí iría la lógica para guardar en la base de datos
      alert('Documento generado correctamente');
    });

    function verDocumento(codVenta) {
      alert('Ver documento: ' + codVenta);
    }

    function imprimirDocumento(codVenta) {
      alert('Imprimir documento: ' + codVenta);
    }
  </script>
</body>
</html>