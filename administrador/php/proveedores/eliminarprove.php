<?php include('../../../login/ingresarlogin.php') ?>

<?php
$codprove = $_GET['codprove'];

if($_SERVER['REQUEST_METHOD']==='GET'){
    $prodprove=pg_query_params($conexion,"SELECT cod_producto FROM producto WHERE cod_proveedor=$1",array($codprove));

    while($conprodprove=pg_fetch_assoc($prodprove)){
        $codprod=trim($conprodprove['cod_producto']);

        pg_query_params($conexion,"DELETE FROM registroinventario WHERE cod_producto=$1",array($codprod));
        pg_query_params($conexion,"DELETE FROM detalleventa WHERE cod_producto=$1",array($codprod));
        pg_query_params($conexion,"DELETE FROM detallecompra WHERE cod_producto=$1",array($codprod));
        pg_query_params($conexion,"DELETE FROM lote WHERE cod_producto=$1",array($codprod));
        pg_query_params($conexion,"DELETE FROM movimiento WHERE cod_producto=$1",array($codprod));
        pg_query_params($conexion,"DELETE FROM notificacion WHERE cod_producto=$1",array($codprod));
        pg_query_params($conexion,"DELETE FROM historialproductos WHERE cod_producto=$1",array($codprod));

        $borrarproducto=pg_query_params($conexion,"DELETE FROM producto WHERE cod_proveedor=$1",array($codprove));
        if(!$borrarproducto){
            echo "Error al borrar proveedor de producto.". pg_last_error($conexion);
        }
    }

    $sql2="DELETE FROM proveedor WHERE cod_proveedor=$1";
    $result7=pg_query_params($conexion,$sql2,array($codprove));

    if(!$result7){
        echo "Error al borrar proveedor.". pg_last_error($conexion);
        exit;
    }

    echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                Swal.fire({
                    icon: 'success',
                    title: 'Proveedor eliminado',
                    text: 'Se eliminó el proveedor correctamente',
                    width: '350px'
                }).then(() => {
                    window.location.href = '../../../administrador/proveedores.html';
                });
            });
        </script>
    ";
}
?>