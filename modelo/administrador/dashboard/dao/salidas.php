<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarSalidasDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $result=pg_query($conexion,"SELECT p.nombre AS producto_nombre, tm.nombre AS tipomovimiento_nombre, dv.cantidad_unidades, mp.nombre AS metodopago_nombre,
                            v.fecha_venta FROM producto p
                            JOIN movimiento m ON p.cod_producto=m.cod_producto
                            JOIN tipomovimiento tm ON m.cod_tipomovimiento=tm.cod_tipomovimiento
                            JOIN detalleventa dv ON p.cod_producto=dv.cod_producto
                            JOIN venta v ON dv.cod_venta=v.cod_venta
                            JOIN metodopago mp ON v.cod_metodopago=mp.cod_metodopago
                            WHERE tm.nombre='Salida'
                            GROUP BY p.nombre,tm.nombre,dv.cantidad_unidades,mp.nombre,v.fecha_venta
                            ORDER BY v.fecha_venta DESC
                            LIMIT 5");
        if(!$result){
            echo "Error al seleccionar las últimas salidas";
        }

        $salida['data']='';

        while($row=pg_fetch_assoc($result)){
            $salida['data'].='<div class="personal list-group-item d-flex justify-content-between align-items-center px-0">';
            $salida['data'].='<div>';
            $salida['data'].='<div class="fw-bold">'.$row['producto_nombre'].'</div>';
            $salida['data'].='<small class="text-muted">'.$row['cantidad_unidades'].' unidades'.' - '.$row['fecha_venta'].'</small>';
            $salida['data'].='</div>';
            $salida['data'].='<span class="badge bg-success">'.$row['metodopago_nombre'].'</span>';
            $salida['data'].='';
            $salida['data'].='</div>';
        }

        header('Content-Type: application/json');
        echo json_encode($salida);
    }
}
?>