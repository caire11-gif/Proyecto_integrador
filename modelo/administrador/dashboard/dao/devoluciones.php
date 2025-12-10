<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarDevolucionesDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $result=pg_query($conexion,"SELECT nc.cantidad_unidades, nc.fecha_notacredito, nc.monto_devolucion, nc.cod_detalleventa, p.nombre FROM notacredito nc
                            JOIN detalleventa dv ON nc.cod_detalleventa=dv.cod_detalleventa
                            JOIN producto p ON dv.cod_producto=p.cod_producto
                            ORDER BY nc.fecha_notacredito DESC
                            LIMIT 5");
        if(!$result){
            echo "Error al seleccionar las últimas salidas";
        }

        $devolucion['data']='';

        while($row=pg_fetch_assoc($result)){
            $devolucion['data'].='<div class="personal list-group-item d-flex justify-content-between align-items-center px-0">';
            $devolucion['data'].='<div>';
            $devolucion['data'].='<div class="fw-bold">'.$row['nombre'].'</div>';
            $devolucion['data'].='<small class="text-muted">'.$row['cantidad_unidades'].' unidades'.' - '.$row['fecha_notacredito'].'</small>';
            $devolucion['data'].='</div>';
            $devolucion['data'].='<span class="badge bg-success">S/. '.$row['monto_devolucion'].'</span>';
            $devolucion['data'].='';
            $devolucion['data'].='</div>';
        }

        header('Content-Type: application/json');
        echo json_encode($devolucion);
    }
}
?>