<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class InsertarProductoDao{
    public function insertar(ProductoDto $dto){
        $conexion=Conexion::getConexion();

        $nombre = $dto->getNombre();
        $precio_caja = $dto->getPreciocaja();
        $unidades_por_caja = $dto->getUnidades();
        $precio_venta = $dto->getPrecioventa();
        $cod_categoria = $dto->getCategoria();
        $cod_proveedor = $dto->getProveedor();

        function obtenerSiguienteCodigo($conexion, $tabla, $prefijo) {
            $configuraciones = [
                'producto' => ['columna' => 'cod_producto', 'formato' => 'PROD'],
            ];

            $config = $configuraciones[$tabla] ?? ['columna' => "cod_$tabla", 'formato' => $prefijo];
            $columna = $config['columna'];
            $formato_prefijo = $config['formato'];

            // ✅ Ordenar numéricamente
            $query = "
                SELECT $columna 
                FROM $tabla 
                    WHERE $columna LIKE '{$formato_prefijo}%'
                ORDER BY CAST(SUBSTRING($columna FROM '[0-9]+$') AS INTEGER) DESC
                LIMIT 1
            ";

            $result = pg_query($conexion, $query);
            if(!$result){
                throw new Exception("Error en la consulta: " . pg_last_error($conexion));
            }
            
            if(pg_num_rows($result) > 0) {
                $ultimo_cod = pg_fetch_assoc($result)[$columna];
                preg_match('/\d+$/', $ultimo_cod, $matches);
                $nuevo_numero = intval($matches[0]) + 1;
            } else {
                $nuevo_numero = 1;
            }

            // ✅ Ceros a la izquierda
            return sprintf("%s%03d", $formato_prefijo, $nuevo_numero);
        }

        $cod_producto = obtenerSiguienteCodigo($conexion, 'producto', 'PROD');

        // Calcular precio_compra_unidad automáticamente
        $precio_compra_unidad = ($unidades_por_caja > 0) ? $precio_caja / $unidades_por_caja : 0;

        // Validaciones básicas
        $errores = [];

        if (empty($nombre)) $errores[] = "El nombre del producto es requerido";
        if ($precio_caja <= 0) $errores[] = "El precio de costo debe ser mayor a 0";
        if ($precio_venta <= 0) $errores[] = "El precio de venta debe ser mayor a 0";
        if ($unidades_por_caja <= 0) $errores[] = "Las unidades por caja deben ser mayores a 0";
        if (empty($cod_categoria)) $errores[] = "Debe seleccionar una categoría";
        if (empty($cod_proveedor)) $errores[] = "Debe seleccionar un proveedor";

        // CORRECCIÓN: Validar que precio venta sea mayor al costo unitario, no al precio de caja
        if ($precio_venta <= $precio_compra_unidad) {
            $errores[] = "El precio de venta debe ser mayor al costo unitario (S/ " . number_format($precio_compra_unidad, 2) . ")";
        }

        if (empty($errores)) {
            // Verificar si el código ya existe
            $check_query = "SELECT COUNT(*) as count FROM producto WHERE cod_producto = $1";
            $check_result = pg_query_params($conexion, $check_query, array($cod_producto));
            $check_row = pg_fetch_assoc($check_result);

            if ($check_row['count'] > 0) {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'El código del producto ya existe',
                    });
                </script>";
            } else {
                // INSERTAR NUEVO PRODUCTO
                $query = "INSERT INTO producto (cod_producto, nombre, precio_caja, precio_compra_unidad, 
                            precio_venta, unidades_por_caja, stock, cod_categoria, cod_proveedor) 
                            VALUES ($1, $2, $3, $4, $5, $6, 0, $7, $8)";

                $params = array($cod_producto, $nombre, $precio_caja, $precio_compra_unidad, 
                                $precio_venta, $unidades_por_caja, $cod_categoria, $cod_proveedor);

                $result = pg_query_params($conexion, $query, $params);

                if ($result) {
                    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Producto insertado correctamente',
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
                            text: 'No se pudo insertar el producto. Error: ' + " . json_encode($error) . ",
                        }).then(() => {
                            window.location.href = '../../../../vista/almacen/gestionproductos.html';
                        });
                    </script>";
                }   
            }
        } else {
            // Mostrar errores
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