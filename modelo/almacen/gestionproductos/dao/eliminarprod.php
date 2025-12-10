<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class EliminarEmpDao{
    public function eliminar($cod_producto){
        $conexion=Conexion::getConexion();

        $cod_producto = $_GET['cod_producto'];
        echo $cod_producto;
        $success = true;
                $error_message = '';

                // Lista de todas las tablas que podrían tener referencias al producto
                $tablas_cascade = array(
                    'detalleventa',
                    'detallecompra',
                    'historialproductos',
                    'movimiento',
                    'registroinventario',
                    'notificacion'
                );

                // Primero, eliminar registros relacionados en todas las tablas
                foreach ($tablas_cascade as $tabla) {
                    // Verificar si la tabla tiene la columna cod_producto
                    $check_sql = "SELECT EXISTS (
                        SELECT 1 FROM information_schema.columns 
                        WHERE table_name = '$tabla' AND column_name = 'cod_producto'
                    ) as tiene_columna";

                    $check_result = pg_query($conexion, $check_sql);
                    if ($check_result) {
                        $check_row = pg_fetch_assoc($check_result);
                        if ($check_row['tiene_columna']) {
                            // Esta tabla tiene columna cod_producto, eliminar registros
                            $selecdetven=pg_query_params($conexion, "SELECT cod_detalleventa FROM detalleventa WHERE cod_producto=$1",array($cod_producto));                                        

                            while($rowselecdetven=pg_fetch_assoc($selecdetven)){
                                $detven=trim($rowselecdetven['cod_detalleventa']);

                                $selecnotacred=pg_query_params($conexion, "SELECT cod_notacredito FROM notacredito WHERE cod_detalleventa=$1",array($detven));

                                while($rowselecnotacred=pg_fetch_assoc($selecnotacred)){
                                    $notacred=trim($rowselecnotacred['cod_notacredito']);

                                    pg_query_params($conexion, "DELETE FROM registroinventario WHERE cod_notacredito=$1",array($notacred));
                                    pg_query_params($conexion, "DELETE FROM notacredito WHERE cod_detalleventa=$1",array($detven));
                                }
                    }       

                            $delete_sql = "DELETE FROM $tabla WHERE cod_producto = $1";
                            $delete_result = pg_query_params($conexion, $delete_sql, array($cod_producto));

                            if (!$delete_result) {
                                $success = false;
                                $error_message = "Error al eliminar de $tabla: " . pg_last_error($conexion);
                                break;
                            }
                        }
                    }
                }
            
                // Manejar notacredito que tiene referencias indirectas
                if ($success) {
                    $delete_notacredito_sql = "
                        DELETE FROM notacredito 
                        WHERE cod_detalleventa IN (
                            SELECT cod_detalleventa FROM detalleventa WHERE cod_producto = $1
                        ) OR cod_detallecompra IN (
                            SELECT cod_detallecompra FROM detallecompra WHERE cod_producto = $1
                        )";

                    $delete_notacredito_result = pg_query_params($conexion, $delete_notacredito_sql, array($cod_producto));
                    if (!$delete_notacredito_result) {
                        $success = false;
                        $error_message = "Error al eliminar notacreditos: " . pg_last_error($conexion);
                    }
                }

                $delete_producto_sql = "DELETE FROM producto WHERE cod_producto = $1";
                $delete_producto_result = pg_query_params($conexion, $delete_producto_sql, array($cod_producto));

                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                        <script>
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: 'Producto y todos sus registros relacionados eliminados correctamente',
                                width: 350px,
                            }).then(() => {
                                window.location.href = '../../vista/almacen/gestionproductos.html';
                            });
                        </script>";
                        exit;    
    }
}
?>