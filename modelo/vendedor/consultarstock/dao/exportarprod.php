<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class ExportarProdDao{
    public function exportar(){
        $conexion=Conexion::getConexion();

        // Construir consulta base
        $result = pg_query($conexion, "SELECT p.cod_producto, p.nombre, p.precio_caja, p.precio_venta, p.precio_compra_unidad,
                             p.unidades_por_caja, p.stock, c.nombre as categoria_nombre,
                             p.cod_categoria
                    FROM producto p
                    LEFT JOIN categoria c ON p.cod_categoria = c.cod_categoria");
        if(!$result){
            echo "Error al seleccionar los datos de los productos";
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