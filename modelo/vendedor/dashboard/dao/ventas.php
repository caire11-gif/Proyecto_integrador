<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarVentasDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $query = "
                SELECT 
                    v.cod_venta as id,
                    v.fecha_venta as fecha,
                    SUM(dv.total) as total,
                    v.cod_metodopago as metodo_pago,
                    COUNT(dv.cod_detalleventa) as cantidad_productos,
                    STRING_AGG(p.nombre, ', ') as productos_nombres
                FROM venta v
                LEFT JOIN detalleventa dv ON v.cod_venta = dv.cod_venta
                LEFT JOIN producto p ON dv.cod_producto = p.cod_producto
                GROUP BY v.cod_venta, v.fecha_venta, v.cod_metodopago
                ORDER BY v.fecha_venta DESC
                LIMIT 5
            ";
        
        $result=pg_query($conexion, $query);
        if(!$result){
            echo "Error al seleccionar las últimas ventas";
        }

        $ven['data']='';

        if($result && pg_num_rows($result) > 0){
            while($row=pg_fetch_assoc($result)){
                $icono_metodo = $row['metodo_pago'] === 'mp001' ? 'money-bill-wave' : 
                              ($row['metodo_pago'] === 'mp002' ? 'credit-card' : 'mobile-alt');
                $clase_metodo = $row['metodo_pago'] === 'mp001' ? 'metodo-efectivo' : 
                              ($row['metodo_pago'] === 'mp002' ? 'metodo-tarjeta' : 'metodo-transferencia');
                $texto_metodo = $row['metodo_pago'] === 'mp001' ? 'Efectivo' : 
                              ($row['metodo_pago'] === 'mp002' ? 'Tarjeta' : 'Transferencia');

                $ven['data'].='
                            <div class="venta-item">
                                <div class="venta-header">
                                    <span class="venta-id">#'.$row['id'].'</span>
                                    <span class="venta-fecha">
                                        '.date('H:i', strtotime($row['fecha'])).' - 
                                        '.date('d/m', strtotime($row['fecha'])).'
                                    </span>
                                </div>

                                <div class="venta-info">
                                    <div class="venta-productos" title="'.$row['productos_nombres'].'">
                                        '.$row['productos_nombres'].'
                                    </div>

                                    <div class="venta-detalles">
                                        <span class="venta-metodo '.$clase_metodo.'">
                                            <i class="fas fa-'.$icono_metodo.'"></i>
                                            '.$texto_metodo.'
                                        </span>

                                        <span class="venta-unidades">
                                            <i class="fas fa-box"></i>
                                            '.$row['cantidad_productos'].' unidades
                                        </span>
                                    </div>
                                </div>

                                <div class="venta-total">
                                    S/ '.number_format($row['total'], 2).'
                                </div>
                            </div>                
                ';
            }
        } else{
            $ven['data'].='
                        <div class="empty-ventas">
                            <i class="fas fa-shopping-cart"></i>
                            <p>No hay ventas hoy</p>
                            <small>Las ventas aparecerán aquí automáticamente</small>
                        </div>
            ';
        }

        header('Content-Type: application/json');
        echo json_encode($ven);
    }
}
?>