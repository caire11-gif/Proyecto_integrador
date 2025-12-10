<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>


<?php
class VerDetallesDao{
    public function ver(){
        $conexion=Conexion::getConexion();

        $codven = $_GET['codven'];

        echo $codven;

        // Obtener información general de la venta
        $queryVenta = "
            SELECT 
                v.*,
                u.usuario,
                m.nombre as metodo_pago,
                td.nombre as tipo_documento,
                td.serie,
                td.numero
            FROM venta v
            LEFT JOIN usuario u ON v.cod_usuario = u.cod_usuario
            LEFT JOIN metodopago m ON v.cod_metodopago = m.cod_metodopago
            LEFT JOIN tipodocumento td ON v.cod_tipodocumento = td.cod_tipodocumento
            WHERE v.cod_venta = $1
        ";
        
        $resultVenta = pg_query_params($conexion, $queryVenta, array($codven));
        
        //#############################################################################################################

        // Obtener detalles de productos vendidos
        $queryDetalles = "
            SELECT 
                dv.*,
                p.nombre as producto_nombre,
                p.precio_venta as precio_unitario
            FROM detalleventa dv
            LEFT JOIN producto p ON dv.cod_producto = p.cod_producto
            WHERE dv.cod_venta = $1
        ";
        
        $resultDetalles = pg_query_params($conexion, $queryDetalles, array($codven));
        $productos = array();
        $total_venta = 0;
        
        while ($detalle = pg_fetch_assoc($resultDetalles)) {
            $productos[] = $detalle;
            $total_venta += floatval($detalle['total']);
        }

        $base_imponible = $total_venta / 1.18;
        $igv = $total_venta - $base_imponible;

        //#############################################################################################################

        $detalleventa['data']='';

        while($venta = pg_fetch_assoc($resultVenta)){
            $fecha=date('d/m/Y', strtotime($venta['fecha_venta']));
            $hora=date('H:i', strtotime($venta['fecha_venta']));
            $esFactura = $venta['tipo_documento'] == 'Factura';
            $cliente='';
            $ruc='';

            if($esFactura==='Factura'){
                $cliente='SEÑOR(ES):';
                $ruc='RUC';
            } else {
                $cliente='CLIENTE:';
                $ruc='DNI';
            }
echo $fecha;
            $detalleventa['data'].='
                                
                                    <div class="documento-empresa">
                                        <!-- Header del Documento -->
                                        <div class="header-documento">
                                            <div class="logo-empresa">MAD MARKET</div>
                                            <div class="ruc-empresa">RUC: 20605467891</div>
                                            <div class="tipo-documento"><'.$venta['tipo_documento'].'</div>
                                            <div class="numero-documento">
                                                '.$venta['serie'] ?? 'B001'. '-' . sprintf('%06d', $venta['numero'] ?? '000001').'
                                            </div>
                                        </div>

                                        <!-- Información de la Empresa -->
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <strong>Razón Social:</strong><br>
                                                MAD MARKET <br>
                                                <strong>Dirección:</strong><br>
                                                las flores 20, Lima - Perú<br>
                                                <strong>Teléfono:</strong> +51 983 858 371
                                            </div>
                                            <div class="col-6 text-end">
                                                <strong>Fecha de Emisión:</strong><br>
                                                '.$fecha.'<br>
                                                <strong>Hora:</strong><br>
                                                '.$hora.'
                                            </div>
                                        </div>

                                        <!-- Información del Cliente -->
                                        <div class="info-cliente">
                                            <div class="row">
                                                <div class="col-6">
                                                    <strong>'.$cliente.'</strong><br>
                                                    '.$venta['nombre'].'
                                                </div>
                                                <div class="col-6">
                                                    <strong>'.$ruc.'</strong><br>
                                                    '.$venta['dni'].'
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tabla de Productos -->
                                        <table class="tabla-productos">
                                            <thead>
                                                <tr>
                                                    <th width="5%">Cant.</th>
                                                    <th width="50%">Descripción</th>
                                                    <th width="15%">P. Unit.</th>
                                                    <th width="15%">Importe</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($productos as $producto): ?>
                                                <tr>
                                                    <td>'.$producto['cantidad_unidades'].'</td>
                                                    <td>'.$producto['producto_nombre'] ?: 'Producto'.'</td>
                                                    <!-- Precio unitario YA incluye IGV -->
                                                    <td>S/ '.number_format($producto['precio_unitario'], 2).'</td>
                                                    <!-- Total por producto YA incluye IGV -->
                                                    <td>S/ '.number_format($producto['total'], 2).'</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                            <!-- CORRECCIÓN: Mostrar Base Imponible en lugar de Subtotal -->
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>OP. GRAVADA:</strong></td>
                                                    <td><strong>S/ '.number_format($base_imponible, 2).'</strong></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>I.G.V. (18%):</strong></td>
                                                    <td><strong>S/ '.number_format($igv, 2).'</strong></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="3" class="text-end total-documento">IMPORTE TOTAL:</td>
                                                    <!-- El total YA incluye IGV - NO se le suma nada adicional -->
                                                    <td class="total-documento">S/ '.number_format($tota_venta, 2).'</td>
                                                </tr>
                                            </tfoot>
                                        </table>

                                        <!-- Información de Pago -->
                                        <div class="row mt-3">
                                            <div class="col-6">
                                                <strong>FORMA DE PAGO:</strong><br>
                                                '.$venta['metodo_pago'].'
                                            </div>
                                            <div class="col-6 text-end">
                                                <strong>VENDEDOR:</strong><br>
                                                '.$venta['usuario'].'
                                            </div>
                                        </div>

                                        <!-- Footer del documento -->
                                        <div class="footer-documento">
                                            <div>
                                                <strong>¡Gracias por su compra!</strong><br>
                                                Representación impresa de la '.strtolower($venta['tipo_documento']).' electrónica<br>
                                                <small>Los precios incluyen IGV</small>
                                            </div>
                                        </div>
                                    </div>
                                
            ';
        }

        $this->alertSuccess("Ok");

        header('Content-Type: application/json');
        echo json_encode($detalleventa);
    }

    private function alertSuccess($msg){
        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: '$msg'
                }).then(() => {
                    history.back();
                });
            </script>
        ";
        exit;
    }
}
?>