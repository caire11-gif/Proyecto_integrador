<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class ExportarProveDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $result=pg_query($conexion,"SELECT * FROM proveedor ORDER BY cod_proveedor");

        if(!$result){
            echo "Error al seleccionar los proveedores para exportar";
        }

        $expprove=[];

        while($row=pg_fetch_assoc($result)){
            $expprove[]=$row;
        }

        header('Content-Type: application/json');
        echo json_encode($expprove);
    }
}
?>