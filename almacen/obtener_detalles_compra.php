<?php
$conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
if(!$conexion){
    echo "Error de conexión";
    exit;
}

$cod_compra = $_GET['cod_compra'] ?? '';

if(empty($cod_compra)) {
    echo "<p>Código de compra no especificado</p>";
    exit;
}

// CORRECCIÓN: Obtener información general de la compra - cambiar pr.nombre por pr.razon_social
$query_compra = "SELECT c.*, pr.razon_social as proveedor_nombre, u.usuario, mp.nombre as metodo_pago 
                 FROM compra c 
                 JOIN proveedor pr ON c.cod_proveedor = pr.cod_proveedor 
                 JOIN usuario u ON c.cod_usuario = u.cod_usuario 
                 JOIN metodopago mp ON c.cod_metodopago = mp.cod_metodopago 
                 WHERE c.cod_compra = '$cod_compra'";
$result_compra = pg_query($conexion, $query_compra);
$compra = pg_fetch_assoc($result_compra);

// CORRECCIÓN: Obtener detalles de la compra - la tabla detallecompra no tiene precio_unitario
$query_detalles = "SELECT dc.*, p.nombre as producto_nombre, p.unidades_por_caja, p.precio_caja
                   FROM detallecompra dc 
                   JOIN producto p ON dc.cod_producto = p.cod_producto 
                   WHERE dc.cod_compra = '$cod_compra'";
$result_detalles = pg_query($conexion, $query_detalles);

if($compra) {
    echo "
    <div class='mb-4'>
        <h6>Información de la Compra</h6>
        <div class='row'>
            <div class='col-md-6'>
                <p><strong>Código:</strong> {$compra['cod_compra']}</p>
                <p><strong>Fecha:</strong> {$compra['fecha_compra']}</p>
                <p><strong>Proveedor:</strong> {$compra['proveedor_nombre']}</p>
            </div>
            <div class='col-md-6'>
                <p><strong>Método de Pago:</strong> {$compra['metodo_pago']}</p>
                <p><strong>Registrado por:</strong> {$compra['usuario']}</p>
            </div>
        </div>
    </div>
    ";
}

echo "<h6>Productos de la Compra</h6>";
echo "<div class='table-responsive'>";
echo "<table class='table table-bordered'>";
echo "<thead class='table-light'>
        <tr>
            <th>Producto</th>
            <th>Cantidad (Cajas)</th>
            <th>Unidades por Caja</th>
            <th>Total Unidades</th>
            <th>Precio por Caja</th>
            <th>Total</th>
        </tr>
      </thead>
      <tbody>";

$total_general = 0;
while($detalle = pg_fetch_assoc($result_detalles)) {
    $total_unidades = $detalle['cantidad_cajas'] * $detalle['unidades_por_caja'];
    $total_producto = $detalle['total'];
    $precio_por_caja = $detalle['precio_caja'];
    
    echo "
    <tr>
        <td>{$detalle['producto_nombre']}</td>
        <td>{$detalle['cantidad_cajas']}</td>
        <td>{$detalle['unidades_por_caja']}</td>
        <td>{$total_unidades}</td>
        <td>S/ " . number_format($precio_por_caja, 2) . "</td>
        <td>S/ " . number_format($total_producto, 2) . "</td>
    </tr>
    ";
    $total_general += $total_producto;
}

echo "</tbody>";
echo "<tfoot>
        <tr class='table-active'>
            <td colspan='5' class='text-end'><strong>Total General:</strong></td>
            <td><strong>S/ " . number_format($total_general, 2) . "</strong></td>
        </tr>
      </tfoot>";
echo "</table>";
echo "</div>";

// También mostrar un resumen por si acaso
echo "<div class='mt-3 p-3 bg-light rounded'>";
echo "<h6>Resumen de la Compra</h6>";
echo "<p><strong>Total de productos diferentes:</strong> " . pg_num_rows($result_detalles) . "</p>";
echo "<p><strong>Total general de la compra:</strong> S/ " . number_format($total_general, 2) . "</p>";
echo "</div>";
?>

<script src="html2pdf.bundle.min.js"></script>
<script setup>
    import {convertHtmlPdf} from  '../js/descarga.js'

    const generarPdf=()=>{
        convertHtmlPdf('detalleCompra')
    }
</script>