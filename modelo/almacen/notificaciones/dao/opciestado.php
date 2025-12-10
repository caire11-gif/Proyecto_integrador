<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarEstadoDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $result=pg_query($conexion, "SELECT cod_estadonotificacion, nombre FROM estadonotificacion");
        if(!$result){
            echo "Error al seleccionar el estado de las notificaciones";
        }

        $estnoti=[];

        while($row=pg_fetch_assoc($result)){
            $estnoti[]=$row;
        }

        header('Content-Type: application/json');
        echo json_encode($estnoti);
    }
}