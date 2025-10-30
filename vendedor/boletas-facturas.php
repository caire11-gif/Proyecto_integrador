<?php
session_start();

$conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");

if(!$conexion){
    echo "Un error de conexión ocurrió. <br>";
    exit;
}

// Obtener historial de documentos con datos reales - CORREGIDO
$resultHistorial = pg_query($conexion, "
    SELECT 
        v.cod_venta, 
        v.fecha_venta, 
        SUM(dv.total) as total,
        u.usuario, 
        m.nombre as metodo_pago,
        td.nombre as tipo_documento, 
        td.serie, 
        td.numero
    FROM venta v 
    LEFT JOIN usuario u ON v.cod_usuario = u.cod_usuario
    LEFT JOIN metodopago m ON v.cod_metodopago = m.cod_metodopago
    LEFT JOIN detalleventa dv ON v.cod_venta = dv.cod_venta
    LEFT JOIN reporte r ON r.cod_tipodocumento IS NOT NULL
    LEFT JOIN tipodocumento td ON r.cod_tipodocumento = td.cod_tipodocumento
    GROUP BY v.cod_venta, v.fecha_venta, u.usuario, m.nombre, td.nombre, td.serie, td.numero
    ORDER BY v.fecha_venta DESC 
    LIMIT 10
");

// Si hay error en el historial, inicializar como array vacío
if(!$resultHistorial) {
    $resultHistorial = null;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historial de Documentos - MAD MARKET</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/vendedor-estilo.css">
  <style>
    .historial-documentos {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border: 1px solid #e9ecef;
    }

    .filtros-historial {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .filtros-historial select,
    .filtros-historial input {
        flex: 1;
        min-width: 150px;
    }

    .documentos-table {
        width: 100%;
        border-collapse: collapse;
    }

    .documentos-table th,
    .documentos-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }

    .documentos-table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .btn-accion {
        margin: 2px;
    }
  </style>
</head>
<body>
  <div class="grid">
    <!-- Sidebar y Header -->
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
          <a href="boletas-facturas.php" class="nav-link active"><ul><i class="fas fa-receipt"></i>Historial Documentos</ul></a>
          <a href="consulta-stock.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Consulta-stock</ul></a>
          <a href="../login.php" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
        </div>
      </div>
    </main>

    <div class="secundario">
      <div class="header">
        <div class="caja-busqueda">
          <i class="fas fa-search"></i>
          <input type="text" class="form-control" placeholder="Buscar documentos..." id="globalSearch">
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

      <!-- Historial de Documentos -->
      <section class="historial-documentos">
        <h2><i class="fas fa-history"></i> Historial de Documentos</h2>
        
        <div class="filtros-historial">
          <select id="filtroTipoDoc" class="form-control">
            <option value="">Todos los tipos</option>
            <option value="factura">Facturas</option>
            <option value="boleta">Boletas</option>
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
                <th>Documento</th>
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
                    <td>
                      <span class="badge <?php echo ($doc['tipo_documento'] == 'Factura') ? 'bg-primary' : 'bg-success'; ?>">
                        <?php echo $doc['tipo_documento'] ?? 'Boleta'; ?>
                      </span>
                    </td>
                    <td>
                      <strong>
                        <?php echo ($doc['serie'] ?? 'B001') . '-' . ($doc['numero'] ?? '000001'); ?>
                      </strong>
                      <br>
                      <small class="text-muted"><?php echo $doc['cod_venta']; ?></small>
                    </td>
                    <td><?php echo $doc['usuario'] ?? 'Sistema'; ?></td>
                    <td><?php echo $doc['metodo_pago']; ?></td>
                    <td class="text-success"><strong>S/ <?php echo number_format($doc['total'], 2); ?></strong></td>
                    <td>
                      <button class="btn btn-info btn-sm btn-accion" onclick="verDetalles('<?php echo $doc['cod_venta']; ?>')">
                        <i class="fas fa-eye"></i> Detalles
                      </button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" style="text-align: center;">No hay documentos registrados</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function cerrarTurno() {
        if(confirm('¿Estás seguro de que deseas cerrar el turno?')) {
            window.location.href = '../login.html';
        }
    }

    // Búsqueda en tiempo real
    document.getElementById('globalSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#tbodyDocumentos tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Filtros del historial
    document.getElementById('btnBuscarHistorial').addEventListener('click', function() {
        const tipoDoc = document.getElementById('filtroTipoDoc').value;
        const fecha = document.getElementById('filtroFecha').value;
    });
  </script>
</body>
</html>