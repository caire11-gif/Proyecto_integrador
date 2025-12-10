<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarRolDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $result = pg_query($conexion, "SELECT cod_rol,nombre FROM rol");
        if(!$result){
            echo "Error al seleccionar el rol";
        }

        $rol=[];

        while($row=pg_fetch_assoc($result)){
            $rol[]=$row;
        }

        header('Content-Type: application/json');
        echo json_encode($rol);
    }
}
?>