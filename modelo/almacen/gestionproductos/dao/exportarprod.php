<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class ExportarProdDao{
    public function exportar(){
        $conexion=Conexion::getConexion();

        $result=pg_query($conexion,"SELECT p.cod_producto, p.nombre AS producto_nombre, p.precio_caja AS precio_costo, p.precio_venta, 
                            p.stock, p.unidades_por_caja, c.nombre AS categoria_nombre, pro.razon_social AS proveedor_nombre FROM producto p
                            JOIN categoria c ON p.cod_categoria = c.cod_categoria
                            JOIN proveedor pro ON p.cod_proveedor = pro.cod_proveedor");

        if(!$result){
            echo "Error al seleccionar los productos para exportar";
        }

        $expprod=[];

        while($row=pg_fetch_assoc($result)){
            $expprod[]=$row;
        }

        header('Content-Type: application/json');
        echo json_encode($expprod);
    }
}
?>