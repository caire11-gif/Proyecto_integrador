
<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarCardsDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $result1 = pg_query($conexion, "SELECT COUNT(dv.cod_detalleventa) AS cantidad_salidas FROM detalleventa dv
                                JOIN venta v ON dv.cod_venta=v.cod_venta
                                GROUP BY TO_CHAR(v.fecha_venta, 'MM')");
        if(!$result1){
            echo "Error al contar las salidas";
        }

        $row1=pg_fetch_assoc($result1);

        $sel1=(int) $row1['cantidad_salidas'];

        $cantven=0;

        if($sel1===0){
            $cantven=0;
        } else {
            $cantven=$sel1;
        }

        /*------------------------------------------------------------------------------------------------*/

        $result2 = pg_query($conexion, "SELECT COUNT(dc.cod_detallecompra) AS cantidad_entradas FROM detallecompra dc
                                        JOIN compra c ON dc.cod_compra=c.cod_compra
                                        GROUP BY TO_CHAR(c.fecha_compra, 'MM')");
        if(!$result2){
            echo "Error al contar las entradas";
        }

        $row2=pg_fetch_assoc($result2);

        $sel2=(int) $row2['cantidad_entradas'];

        $cantcom=0;

        if($sel2===0){
            $cantcom=0;
        } else {
            $cantcom=$sel2;
        }

        /*------------------------------------------------------------------------------------------------*/

        $result3 = pg_query($conexion, "SELECT COUNT(cod_notacredito) AS cantidad_devoluciones FROM notacredito GROUP BY TO_CHAR(fecha_notacredito, 'MM')");

        if(!$result3){
            echo "Error al contar las devoluciones";
        }

        $row3=pg_fetch_assoc($result3);

        $sel3=(int) $row3['cantidad_devoluciones'];

        $cantdevo=0;

        if($sel3===0){
            $cantdevo=0;
        } else {
            $cantdevo=$sel3;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'cantidad_salidas'=>$cantven,
            'cantidad_entradas'=>$cantcom,
            'cantidad_devoluciones'=>$cantdevo
        ]);
    }
}
?>