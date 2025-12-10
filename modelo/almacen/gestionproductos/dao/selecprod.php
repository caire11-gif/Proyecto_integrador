<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarProdDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        // Columnas a mostrar en la tabla
        $columns = ['cod_producto', 'nombre', 'precio_caja', 'precio_compra_unidad', 'precio_venta','stock','unidades_por_caja'];

        // Nombre de la tabla
        $table = "producto";
        $tableCategoria="categoria";
        $tableProveedor="proveedor";

        // Clave principal de la tabla
        $id = 'cod_producto';

        // Campo a buscar
        $campo = isset($_POST['buscarProducto']) ? pg_escape_string($conexion, $_POST['buscarProducto']) : null;

        // Filtrado
        $where = '';

        if ($campo != null) {
            $where = "WHERE (";

            $cont = count($columns);
            for ($i = 0; $i < $cont; $i++) {
                $where .= $columns[$i] . " LIKE '%" . $campo . "%' OR ";
            }
            $where = substr_replace($where, "", -3);  // Elimina el último 'OR'
            $where .= ")";
        }

        // Limites
        $limit = isset($_POST['registros']) ? pg_escape_string($conexion, $_POST['registros']) : 10;
        $pagina = isset($_POST['pagina']) ? pg_escape_string($conexion, $_POST['pagina']) : 1;

        if (!$pagina) {
            $inicio = 0;
            $pagina = 1;
        } else {
            $inicio = ($pagina - 1) * $limit;
        }

        $sLimit = "LIMIT $limit OFFSET $inicio";

        // Ordenamiento
        $sOrder = "";
        if (isset($_POST['orderCol'])) {
            $orderCol = $_POST['orderCol'];
            $orderType = isset($_POST['orderType']) ? $_POST['orderType'] : 'asc';

            $sOrder = "ORDER BY " . $columns[intval($orderCol)] . ' ' . $orderType;
        }

        // Consulta
        $sql = "SELECT p.cod_producto, p.nombre AS producto_nombre, p.precio_caja AS precio_costo, p.precio_compra_unidad, p.precio_venta, p.stock, p.unidades_por_caja,
                            c.nombre AS categoria_nombre, pro.razon_social AS proveedor_nombre, p.cod_categoria AS categoria_codigo, p.cod_proveedor AS proveedor_codigo
        FROM $table p
        JOIN $tableCategoria c ON p.cod_categoria = c.cod_categoria
        JOIN $tableProveedor pro ON p.cod_proveedor = pro.cod_proveedor
        $where
        $sOrder
        $sLimit";
        $resultado = pg_query($conexion, $sql);
        $num_rows = pg_num_rows($resultado);

        // Consulta para total de registro filtrados
        $sqlFiltro = "SELECT COUNT($id) AS num FROM $table $where";
        $resFiltro = pg_query($conexion, $sqlFiltro);
        $row_filtro = pg_fetch_assoc($resFiltro);
        $totalFiltro = $row_filtro['num'];

        // Consulta para total de registros
        $sqlTotal = "SELECT COUNT($id) FROM $table";
        $resTotal = pg_query($conexion, $sqlTotal);
        $row_total = pg_fetch_assoc($resTotal);
        $totalRegistros = $row_total['count'];

        // Mostrando resultados
        $output = [];
        $output['totalRegistros'] = $totalRegistros;
        $output['totalFiltro'] = $totalFiltro;
        $output['data'] = '';
        $output['paginacion'] = '';

        if ($num_rows > 0) {
            while ($row = pg_fetch_assoc($resultado)) {
                $output['data'] .= '
                                <tr>
                                    <td>' . $row['cod_producto'] . '</td>
                                    <td>' . $row['producto_nombre'] . '</td>
                                    <td>' . $row['precio_costo'] . '</td>
                                    <td>'. $row['precio_compra_unidad'] .'</td>
                                    <td>' . $row['precio_venta'] . '</td>
                                    <td>' . $row['stock'] . '</td>
                                    <td>' . $row['unidades_por_caja'] . '</td>
                                    <td>' . $row['categoria_nombre'] . '</td>
                                    <td>' . $row['proveedor_nombre'] . '</td>
                                    <td>
                                        <button class="btn btn-primary me-1 btnActualizar" data-codprod="'.$row['cod_producto'].'" data-nombre="'.$row['producto_nombre'].'" data-precosto="'.$row['precio_costo'].'" data-preventa="'.$row['precio_venta'].'" data-preciounidad="'.$row['precio_compra_unidad'].'"
                                        data-stock="'.$row['stock'].'" data-unidades="'.$row['unidades_por_caja'].'" data-codcate="'.$row['categoria_codigo'].'" data-categoria="'.$row['categoria_nombre'].'" data-codprove="'.$row['proveedor_codigo'].'" data-proveedor="'.$row['proveedor_nombre'].'">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button class="btn btn-danger me-2 btnEliminar" data-codprod="' . $row['cod_producto'] . '"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                ';
            }
        } else {
            $output['data'] .= '<tr>';
            $output['data'] .= '<td colspan="7">Sin resultados</td>';
            $output['data'] .= '</tr>';
        }

        // Paginación
        if ($totalRegistros > 0) {
            $totalPaginas = ceil($totalFiltro / $limit);

            $output['paginacion'] .= '<nav>';
            $output['paginacion'] .= '<ul class="pagination">';

            $numeroInicio = max(1, $pagina - 4);
            $numeroFin = min($totalPaginas, $numeroInicio + 9);

            for ($i = $numeroInicio; $i <= $numeroFin; $i++) {
                $output['paginacion'] .= '<li class="page-item' . ($pagina == $i ? ' active' : '') . '">';
                $output['paginacion'] .= '<a class="page-link" href="#" onclick="nextPage(' . $i . ')">' . $i . '</a>';
                $output['paginacion'] .= '</li>';
            }

            $output['paginacion'] .= '</ul>';
            $output['paginacion'] .= '</nav>';
        }

        echo json_encode($output, JSON_UNESCAPED_UNICODE);
    }
}
?>