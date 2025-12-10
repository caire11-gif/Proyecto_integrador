<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarPrecioProductoDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $precios_productos = array();

        $result = pg_query($conexion, "SELECT p.cod_producto, p.nombre, p.precio_caja, p.unidades_por_caja, p.cod_proveedor, pr.razon_social 
                                  FROM producto p 
                                  LEFT JOIN proveedor pr ON p.cod_proveedor = pr.cod_proveedor");

        if(!$result){
            error_log("Error al cargar productos: " . pg_last_error($conexion));
        } else {
            pg_result_seek($result, 0);
            while($row = pg_fetch_assoc($result)){
                $precios_productos[$row['cod_producto']] = $row['precio_caja'];
                $unidades_por_caja[$row['cod_producto']] = $row['unidades_por_caja'];
                
                // Organizar productos por proveedor
                $cod_proveedor = $row['cod_proveedor'];
                if (!isset($productos_por_proveedor[$cod_proveedor])) {
                    $productos_por_proveedor[$cod_proveedor] = array();
                }
                $productos_por_proveedor[$cod_proveedor][] = array(
                    'cod_producto' => $row['cod_producto'],
                    'nombre' => $row['nombre'],
                    'precio_caja' => $row['precio_caja'],
                    'unidades_por_caja' => $row['unidades_por_caja']
                );
            }
        }

        header('Content-Type: application/json');
        echo json_encode($precios_productos);
    }
}
?>