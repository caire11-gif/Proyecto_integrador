<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarProveedorDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $result=pg_query($conexion, "SELECT cod_proveedor, razon_social AS proveedor_nombre FROM proveedor");
        if(!$result){
            echo "Error al seleccionar los proveedores";
        }

        $prove=[];

        while($row=pg_fetch_assoc($result)){
            $prove[]=$row;
        }

        header('Content-Type: application/json');
        echo json_encode($prove);
    }
}