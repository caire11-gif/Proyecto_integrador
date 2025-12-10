<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarCardsDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $result1=pg_query($conexion, "SELECT COUNT(DISTINCT v.cod_venta) AS total_ventas FROM venta v
                                      LEFT JOIN detalleventa dv ON v.cod_venta=dv.cod_venta
                                      WHERE DATE(v.fecha_venta)=CURRENT_DATE");
        if(!$result1){
            echo "Error al contar los empleados";
            exit;
        }
        
        $row1=pg_fetch_assoc($result1);

        $sel1=(int) $row1['total_ventas'];

        $cantven=0;

        if($sel1===0){
            $cantven=0;
        } else {
            $cantven=$sel1;
        }

        //#############################################################################################################

        $result2=pg_query($conexion, "SELECT COALESCE(SUM(dv.total),0) AS total_vendido FROM venta v 
                                      JOIN detalleventa dv ON v.cod_venta=dv.cod_venta
                                      WHERE DATE(v.fecha_venta)=CURRENT_DATE");
        if(!$result2){
            echo "Error al contar los usuarios";
            exit;
        }

        $row2=pg_fetch_assoc($result2);

        $sel2=(int) $row2['total_vendido'];

        $totven=0;

        if($sel2===0){
            $totven=0;
        } else {
            $totven=$sel2;
        }

        //#############################################################################################################

        $result3=pg_query($conexion, "SELECT COALESCE(SUM(dv.cantidad_unidades),0) AS total_productos FROM venta v 
                                      LEFT JOIN detalleventa dv ON v.cod_venta=dv.cod_venta
                                      WHERE DATE (v.fecha_venta)=CURRENT_DATE");
        if(!$result3){
            echo "Error al contar los usuarios activos";
        }

        $row3=pg_fetch_assoc($result3);

        $sel3=(int) $row3['total_productos'];

        $cantuni=0;

        if($sel3===0){
            $cantuni=0;
        } else {
            $cantuni=$sel3;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'total_ventas'=>$cantven,
            'total_vendido'=>$totven,
            'total_productos'=>$cantuni
        ]);
    }
}
?>