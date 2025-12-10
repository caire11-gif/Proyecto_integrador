<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarProveDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        // Columnas a mostrar en la tabla
        $columns = ['cod_proveedor', 'razon_social', 'ruc', 'telefono', 'direccion'];

        // Nombre de la tabla
        $table = "proveedor";

        // Clave principal de la tabla
        $id = 'cod_proveedor';

        // Campo a buscar
        $campo = isset($_POST['campo']) ? pg_escape_string($conexion, $_POST['campo']) : null;

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
        $sql = "SELECT " . implode(", ", $columns) . "
        FROM $table
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
                                    <td>' . $row['cod_proveedor'] . '</td>
                                    <td>' . $row['razon_social'] . '</td>
                                    <td>' . $row['ruc'] . '</td>
                                    <td>' . $row['telefono'] . '</td>
                                    <td>' . $row['direccion'] . '</td>
                                    <td>
                                        <button class="btn btn-primary me-1 btnActualizar" data-codprove="'.$row['cod_proveedor'].'" data-razonsocial="'.$row['razon_social'].'" data-ruc="'.$row['ruc'].'" data-telefono="'.$row['telefono'].'" data-direccion="'.$row['direccion'].'">
                                        <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button class="btn btn-danger me-2 btnEliminar" data-codprove="' . $row['cod_proveedor'] . '"><i class="fas fa-trash"></i></button>
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