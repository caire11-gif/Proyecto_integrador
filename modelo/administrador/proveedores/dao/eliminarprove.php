<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
if (isset($_GET['codprove'])) {  // Verifica si 'codprove' está presente en la URL
    $codprove = $_GET['codprove'];
    echo "Codprove recibido: " . htmlspecialchars($codprove);  // Muestra el valor
} else {
    echo "No se recibió el parámetro codprove";  // Mensaje de error si no se pasa el parámetro
}

if($_SERVER['REQUEST_METHOD']==='GET'){
    $prodprove=pg_query_params($conexion,"SELECT cod_producto FROM producto WHERE cod_proveedor=$1",array($codprove));

    while($conprodprove=pg_fetch_assoc($prodprove)){
        $codprod=trim($conprodprove['cod_producto']);

        $result1=pg_query_params($conexion,"SELECT cod_detalleventa FROM detalleventa WHERE cod_producto=$1",array($codprod));
        while($row1=pg_fetch_assoc($result1)){
            $coddetven=trim($row1['cod_detalleventa']);

            pg_query_params($conexion,"DELETE FROM notacredito WHERE cod_detalleventa=$1",array($coddetven));
        }

        pg_query_params($conexion,"DELETE FROM registroinventario WHERE cod_producto=$1",array($codprod));
        pg_query_params($conexion,"DELETE FROM detalleventa WHERE cod_producto=$1",array($codprod));
        pg_query_params($conexion,"DELETE FROM detallecompra WHERE cod_producto=$1",array($codprod));
        pg_query_params($conexion,"DELETE FROM movimiento WHERE cod_producto=$1",array($codprod));
        pg_query_params($conexion,"DELETE FROM notificacion WHERE cod_producto=$1",array($codprod));
        pg_query_params($conexion,"DELETE FROM historialproductos WHERE cod_producto=$1",array($codprod));

        pg_query_params($conexion,"DELETE FROM compra WHERE cod_proveedor=$1",array($codprove));
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
                    window.location.href = '../../../../vista/administrador/proveedores.html';
                });
            });
        </script>
    ";
}
?>