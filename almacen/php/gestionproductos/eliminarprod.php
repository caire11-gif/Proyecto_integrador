<?php include('../../../login/ingresarlogin.php') ?>

<?php
$codprod = $_GET['codigo_producto'];

if($_SERVER['REQUEST_METHOD']==='GET'){
    pg_query_params($conexion,"DELETE FROM registroinventario WHERE cod_producto=$1",array($codprod));
    pg_query_params($conexion,"DELETE FROM detalleventa WHERE cod_producto=$1",array($codprod));
    pg_query_params($conexion,"DELETE FROM detallecompra WHERE cod_producto=$1",array($codprod));
    pg_query_params($conexion,"DELETE FROM lote WHERE cod_producto=$1",array($codprod));
    pg_query_params($conexion,"DELETE FROM movimiento WHERE cod_producto=$1",array($codprod));
    pg_query_params($conexion,"DELETE FROM notificacion WHERE cod_producto=$1",array($codprod));
    pg_query_params($conexion,"DELETE FROM historialproductos WHERE cod_producto=$1",array($codprod));

    $borrarproducto=pg_query_params($conexion,"DELETE FROM producto WHERE cod_producto=$1",array($codprod));
    if(!$borrarproducto){
        echo "Error al borrar proveedor de producto.". pg_last_error($conexion);
    }

    echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                Swal.fire({
                    icon: 'success',
                    title: 'Producto eliminado',
                    text: 'Se eliminó el producto correctamente',
                    width: '350px'
                }).then(() => {
                    window.location.href = '../../../almacen/gestionproductos.html';
                });
            });
        </script>
    ";
}
?>