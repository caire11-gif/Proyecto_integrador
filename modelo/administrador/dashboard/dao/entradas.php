<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarEntradasDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $result=pg_query($conexion,"SELECT p.nombre AS producto_nombre, tm.nombre AS tipomovimiento_nombre, dc.cantidad_unidades, mp.nombre AS metodopago_nombre,
                            c.fecha_compra FROM producto p
                            JOIN movimiento m ON p.cod_producto=m.cod_producto
                            JOIN tipomovimiento tm ON m.cod_tipomovimiento=tm.cod_tipomovimiento
                            JOIN detallecompra dc ON p.cod_producto=dc.cod_producto
                            JOIN compra c ON dc.cod_compra=c.cod_compra
                            JOIN metodopago mp ON c.cod_metodopago=mp.cod_metodopago
                            WHERE tm.nombre='Entrada'
                            GROUP BY p.nombre,tm.nombre,dc.cantidad_unidades,mp.nombre,c.fecha_compra
                            ORDER BY c.fecha_compra DESC
                            LIMIT 5");
        if(!$result){
            echo "Error al seleccionar las últimas entradas";
        }

        $entrada['data']='';

        while($row=pg_fetch_assoc($result)){
            $entrada['data'].='<div class="personal list-group-item d-flex justify-content-between align-items-center px-0">';
            $entrada['data'].='<div>';
            $entrada['data'].='<div class="fw-bold">'.$row['producto_nombre'].'</div>';
            $entrada['data'].='<small class="text-muted">'.$row['cantidad_unidades'].' unidades'.' - '.$row['fecha_compra'].'</small>';
            $entrada['data'].='</div>';
            $entrada['data'].='<span class="badge bg-success">'.$row['metodopago_nombre'].'</span>';
            $entrada['data'].='';
            $entrada['data'].='</div>';
        }

        header('Content-Type: application/json');
        echo json_encode($entrada);
    }
}
?>