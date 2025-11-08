<?php include('../../../login/ingresarlogin.php') ?>

<?php
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='actualizar')){
    $codactprod=$_POST['codigoActualizarProducto'] ?? '';
    $nomactprod=$_POST['nombreActualizarProducto'] ?? '';
    $precostoactprod=$_POST['precioCostoActualizarProducto'] ?? '';
    $preventaactprod=$_POST['precioVentaActualizarProducto'] ?? '';
    $costouniactprod=$_POST['costoUnitarioActualizarProducto'] ?? '';
    $unicajaactprod=$_POST['unidadesCajaActualizarProducto'] ?? '';
    $stockactprod=$_POST['stockActualizarProducto'] ?? '';
    $cateactprod=$_POST['categoriaActualizarProducto'] ?? '';
    $proveactprod=$_POST['proveedorActualizarProducto'] ?? '';

    $actualizar="UPDATE producto SET nombre=$2, precio_costo=$3, precio_venta=$4, unidades_por_caja=$5, stock=$6, cod_categoria=$7, cod_proveedor=$8 WHERE cod_producto=$1";

    $result=pg_query_params($conexion,$actualizar,array($codactprod,$nomactprod,$precostoactprod,$preventaactprod,$unicajaactprod,$stockactprod,$cateactprod,$proveactprod));

    if(!$result){
        echo "Error al seleccionar proveedor.";
        exit;
    }

    echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                Swal.fire({
                    icon: 'success',
                    title: 'Producto actualizado',
                    text: 'Se actualizó el producto correctamente',
                    width: '350px'
                }).then(() => {
                    window.location.href = '../../../almacen/gestionproductos.html';
                });
            });
        </script>
    ";
}
?>