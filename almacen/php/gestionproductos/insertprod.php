<?php include('../../../login/ingresarlogin.php') ?>

<?php
if($_SERVER['REQUEST_METHOD']==='POST'){
    $codprod=$_POST['codigoProducto'] ?? '';
    $nomprod=$_POST['nombreProducto'] ?? '';
    $precosto=$_POST['precioCosto'] ?? '';
    $preventa=$_POST['precioVenta'] ?? '';
    $unicaja=$_POST['unidadesCaja'] ?? '';
    $stockprod=$_POST['stockProducto'] ?? '';
    $cateprod=$_POST['categoriaProducto'] ?? '';
    $proveprod=$_POST['proveedorProducto'] ?? '';

    $timestamp = substr(time(), -6);
    $codprod = 'PROD' . $timestamp;

    $vericod=pg_query_params($conexion, "SELECT COUNT(cod_producto) AS cantidad_codigo_producto FROM producto WHERE cod_producto=$1", array($codprod));
    if(!$vericod){
        echo "Error al verificar el código del producto";
        exit;
    }

    $veri=pg_fetch_assoc($vericod);

    if($veri){
        $veric=(int) $veri['cantidad_codigo_producto'];
    } else {
        $veric=0;
    }

    if($veric===0){
        $insertar=pg_query_params($conexion, "INSERT INTO producto(cod_producto,nombre,precio_costo,precio_venta,unidades_por_caja,stock,cod_categoria,cod_proveedor)
                                  VALUES ($1,$2,$3,$4,$5,$6,$7,$8)",
                                  array($codprod,$nomprod,$precosto,$preventa,$unicaja,$stockprod,$cateprod,$proveprod));
        if(!$insertar){
            echo "Error al insertar elproducto";
        } else {
            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        Swal.fire({
                            icon: 'success',
                            title: 'Producto registrado',
                            text: 'Se registró el producto correctamente',
                            width: '350px'
                        }).then(() => {
                            window.location.href = '../../../almacen/gestionproductos.html';
                        });
                    });
                </script>
            ";
        }
    } else {
        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
                document.addEventListener('DOMContentLoaded', function(){
                    Swal.fire({
                        icon: 'warning',
                        title: 'Oops...',
                        text: 'No pueden haber dos códigos iguales. Intente con otro.',
                        width: '350px'
                    }).then(() => {
                        window.location.href = '../../../almacen/gestionproductos.html';
                    });
                });
            </script>
        ";
                        
        exit;
    }
}
?>