<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class ActualizarProdDao{
    public function actualizar(ProductoActualizarDto $dto){
        $conexion=Conexion::getConexion();

        $cod_producto_editar=$dto->getCodigo();
        $nombre=$dto->getNombre();
        $precio_caja=$dto->getPreciocaja();
        $unidades_por_caja=$dto->getUnidades();
        $precio_venta=$dto->getPrecioventa();
        $cod_categoria=$dto->getCategoria();
        $cod_proveedor=$dto->getProveedor();

        // Calcular precio_compra_unidad automáticamente
        $precio_compra_unidad = ($unidades_por_caja > 0) ? $precio_caja / $unidades_por_caja : 0;
        
        // Validaciones
        $errores = [];
            
        if (empty($nombre)) $errores[] = "El nombre del producto es requerido";
        if ($precio_caja <= 0) $errores[] = "El precio de costo debe ser mayor a 0";
        if ($precio_venta <= 0) $errores[] = "El precio de venta debe ser mayor a 0";
        if ($unidades_por_caja <= 0) $errores[] = "Las unidades por caja deben ser mayores a 0";
        if (empty($cod_categoria)) $errores[] = "Debe seleccionar una categoría";
        if (empty($cod_proveedor)) $errores[] = "Debe seleccionar un proveedor";

        // CORRECCIÓN: Validar que precio venta sea mayor al costo unitario
        if ($precio_venta <= $precio_compra_unidad) {
            $errores[] = "El precio de venta debe ser mayor al costo unitario (S/ " . number_format($precio_compra_unidad, 2) . ")";
        }

        if (empty($errores)) {
            $query = "UPDATE producto SET 
                        nombre = $1,
                        precio_caja = $2,
                        precio_compra_unidad = $3,
                        precio_venta = $4,
                        unidades_por_caja = $5,
                        cod_categoria = $6,
                        cod_proveedor = $7
                        WHERE cod_producto = $8";

            $params = array($nombre, $precio_caja, $precio_compra_unidad, $precio_venta,
                            $unidades_por_caja, $cod_categoria, $cod_proveedor, $cod_producto_editar);

            $result = pg_query_params($conexion, $query, $params);

            if ($result && pg_affected_rows($result) > 0) {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Producto actualizado correctamente',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '../../../../vista/almacen/gestionproductos.html';
                    });
                </script>";
            } else {
                $error = pg_last_error($conexion);
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo actualizar el producto. Error: ' + " . json_encode($error) . ",
                    }).then(() => {
                        window.location.href = '../../../../vista/almacen/gestionproductos.html';
                    });
                </script>";
            }
        } else {
            $mensaje_errores = implode('\n', $errores);
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Errores de validación',
                    text: '$mensaje_errores',
                }).then(() => {
                    window.location.href = '../../../../vista/almacen/gestionproductos.html';
                });
            </script>";
        }
    }
}
?>