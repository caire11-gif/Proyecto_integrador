<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarCardsDao {
    public function seleccionar() {
        $conexion=Conexion::getConexion();

        $result1=pg_query($conexion, "SELECT COUNT(cod_producto) AS cantidad_productos FROM producto");
        if(!$result1){
            echo "Error al contar los productos";
        }

        $row1=pg_fetch_assoc($result1);

        $sel1=(int) $row1['cantidad_productos'];

        $cantprod=0;

        if($sel1===0){
            $cantprod=0;
        } else {
            $cantprod=$sel1;
        }

        //#############################################################################################################

        $result2=pg_query($conexion, "SELECT COUNT(stock) AS producto_bajo FROM producto WHERE stock<11 AND stock>0");
        if(!$result2){
            echo "Error al contar los productos con stock bajo";
            exit;
        }

        $row2=pg_fetch_assoc($result2);

        $sel2=(int) $row2['producto_bajo'];

        $prodbajo=0;

        if($sel2===0){
            $prodbajo=0;
        } else {
            $prodbajo=$sel2;
        }

        //#############################################################################################################

        $result3=pg_query($conexion, "SELECT COUNT(stock) AS producto_agotado FROM producto WHERE stock=0");
        if(!$result3){
            echo "Error al contar los productos agotados";
        }

        $row3=pg_fetch_assoc($result3);

        $sel3=(int) $row3['producto_agotado'];

        $prodago=0;

        if($sel3===0){
            $prodago=0;
        } else {
            $prodago=$sel3;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'cantidad_productos'=>$cantprod,
            'producto_bajo'=>$prodbajo,
            'producto_agotado'=>$prodago
        ]);
    }
}
?>