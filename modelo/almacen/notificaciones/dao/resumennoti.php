<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarResumenNotiDao {
    public function seleccionar() {
        $conexion=Conexion::getConexion();

        $result1=pg_query($conexion, "SELECT COUNT(cod_producto) AS cantidad_productos_bajo FROM producto WHERE stock>=10 AND stock<=20");
        if(!$result1){
            echo "Error al contar los productos entre 10 y 20";
        }

        $row1=pg_fetch_assoc($result1);

        $notibajo=0;

        $sel1=(int) $row1['cantidad_productos_bajo'];

        if($sel1===0){
            $notibajo=0;
        } else {
            $notibajo=$sel1;
        }

        //#############################################################################################################

        $result2=pg_query($conexion, "SELECT COUNT(cod_producto) AS cantidad_productos_medio FROM producto WHERE stock>=5 AND stock<=10");
        if(!$result2){
            echo "Error al contar los productos entre 5 y 10";
        }

        $row2=pg_fetch_assoc($result2);

        $notimedio=0;

        $sel2=(int) $row2['cantidad_productos_medio'];

        if($sel2===0){
            $notimedio=0;
        } else {
            $notimedio=$sel2;
        }

        //#############################################################################################################

        $result3=pg_query($conexion, "SELECT COUNT(cod_producto) AS cantidad_productos_alto FROM producto WHERE stock<=5");
        if(!$result3){
            echo "Error al contar los productos menores de 5";
        }

        $row3=pg_fetch_assoc($result3);

        $notialto=0;

        $sel3=(int) $row3['cantidad_productos_alto'];

        if($sel3===0){
            $notialto=0;
        } else {
            $notialto=$sel3;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'cantidad_productos_bajo'=>$notibajo,
            'cantidad_productos_medio'=>$notimedio,
            'cantidad_productos_alto'=>$notialto
        ]);
    }
}
?>