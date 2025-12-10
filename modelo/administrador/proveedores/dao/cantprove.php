<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarCantidadProveDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $result1=pg_query($conexion,"SELECT COUNT(cod_proveedor) AS cantidad_proveedor FROM proveedor");

        if(!$result1){
            echo "Error al contar los proveedores";
        }

        $row1=pg_fetch_assoc($result1);

        $sel1=(int) $row1['cantidad_proveedor'];

        $cantprove=0;

        if($sel1===0){
            $cantprove=0;
        } else {
            $cantprove=$sel1;
        }

        echo json_encode([
            'cantidad_proveedor'=>$cantprove
        ]);
    }
}
?>