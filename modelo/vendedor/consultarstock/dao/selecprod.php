<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>


<?php
class SeleccionarProductoDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        // Construir consulta base
        $queryBase = "SELECT p.cod_producto, p.nombre, p.precio_caja, p.precio_venta, p.precio_compra_unidad,
                             p.unidades_por_caja, p.stock, c.nombre as categoria_nombre,
                             p.cod_categoria
                    FROM producto p
                    LEFT JOIN categoria c ON p.cod_categoria = c.cod_categoria";

        // Ordenar y limitar
        $queryBase .= " ORDER BY p.cod_producto";
        $queryProductos = $queryBase;

        // Obtener resultados
        $resultProductos = pg_query($conexion, $queryProductos);

        $num_rows = pg_num_rows($resultProductos);

        $selecprod['data']='';

        if ($num_rows > 0){
            while ($row = pg_fetch_assoc($resultProductos)){
                $stock = $row['stock'];
                $precio_compra_unidad = floatval($row['precio_compra_unidad']);
                $precio_venta = floatval($row['precio_venta']);
                $margen = $precio_venta - $precio_compra_unidad;
                                        
                // Determinar clase del badge
                if($stock == 0) {
                    $badgeClass = 'badge-danger';
                    $estadoTexto = 'Agotado';
                    $estadoIcono = 'fa-times-circle';
                    $estadoColor = 'text-danger';
                } elseif($stock <= 10) {
                    $badgeClass = 'badge-warning';
                    $estadoTexto = 'Stock Bajo';
                    $estadoIcono = 'fa-exclamation-triangle';
                    $estadoColor = 'text-warning';
                } else {
                    $badgeClass = 'badge-success';
                    $estadoTexto = 'Disponible';
                    $estadoIcono = 'fa-check-circle';
                    $estadoColor = 'text-success';
                }
                                    
                $precio_compra_unidad=number_format($precio_compra_unidad, 2);

                $selecprod['data'].='
                                <tr>
                                    <td><strong>'.$row['cod_producto'].'</strong></td>
                                    <td>'.$row['nombre'].'</td>
                                    <td>'.$row['categoria_nombre'].'</td>
                                    <td>S/ '.$precio_compra_unidad.'</td>
                                    <td class="text-success"><strong>S/ '.number_format($precio_venta, 2).'</strong></td>
                                    <td class="text-margen">S/ '.number_format($margen, 2).'</td>
                                    <td>'.$row['unidades_por_caja'].'</td>
                                    <td><span class="badge-stock '.$badgeClass.'">'.$stock.' unidades</span></td>
                                    <td><span class="'.$estadoColor.'"><i class="fas '.$estadoIcono.'"></i>'.$estadoTexto.'</span></td>
                                </tr>
                ';
            }
        } else {
            $selecprod['data'].='
                            <tr>
                                <td colspan="9">
                                    <div class="sin-resultados">
                                        <i class="fas fa-search fa-3x mb-3"></i>
                                        <p class="mb-2">No se encontraron productos</p>
                                        <small class="text-muted">
                                            No hay productos registrados en el sistema
                                        </small>
                                    </div>
                                </td>
                            </tr>
            ';
        }

        header('Content-Type: application/json');
        echo json_encode($selecprod);
    }
}
?>