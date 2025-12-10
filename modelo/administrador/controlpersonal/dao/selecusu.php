<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarUsuDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $columns=['cod_usuario','cod_empleado','usuario','contraseña','cod_estadousuario'];

        $table='usuario';
        $tableEstadoUsuario='estadousuario';

        $id='cod_usuario';

        $campo=isset($_POST['campo_usu']) ? pg_escape_string($conexion, $_POST['campo_usu']) : null;

        $where='';

        if($campo!=null){
            $where="WHERE (";

            $cont=count($columns);

            for($i=0; $i<$cont; $i++){
                $where .= $columns[$i]." LIKE '&". $campo ."%' OR ";
            }

            $where=substr_replace($where, "", -3);
            $where.=")";
        }

        $limit = isset($_POST['registros_usu']) ? pg_escape_string($conexion, $_POST['registros_usu']) : 10;
        $pagina = isset($_POST['pagina_usu']) ? pg_escape_string($conexion, $_POST['pagina_usu']) : 1;

        if (!$pagina) {
            $inicio = 0;
            $pagina = 1;
        } else {
            $inicio = ($pagina - 1) * $limit;
        }

        $sLimit = "LIMIT $limit OFFSET $inicio";

        // Ordenamiento
        $sOrder = "";
        if (isset($_POST['orderCol_usu'])) {
            $orderCol = $_POST['orderCol_usu'];
            $orderType = isset($_POST['orderType_usu']) ? $_POST['orderType_usu'] : 'asc';

            $sOrder = "ORDER BY " . $columns[intval($orderCol)] . ' ' . $orderType;
        }

        // Consulta
        $sql = "SELECT u.cod_usuario, u.cod_empleado, u.usuario, u.contraseña, u.cod_estadousuario, eu.nombre AS estadousuarionombre
        FROM $table u
        LEFT JOIN $tableEstadoUsuario eu ON u.cod_estadousuario=eu.cod_estadousuario
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
                                    <td>' . $row['cod_usuario'] . '</td>
                                    <td>' . $row['cod_empleado'] . '</td>
                                    <td>' . $row['usuario'] . '</td>
                                    <td>' . $row['contraseña'] . '</td>
                                    <td>' . $row['estadousuarionombre'] . '</td>
                                    <td>
                                        <button class="btn btn-primary me-1 btnActualizar" data-codusu="'.$row['cod_usuario'].'" data-codestado="'.$row['cod_estadousuario'].'">
                                            <i class="fas fa-edit"></i>
                                        </button>
                    
                                        <button class="btn btn-warning me-2 btnCambiarContraseña" data-codusucont="' . $row['cod_usuario'] . '" data-contraseña="'.$row['contraseña'].'"><i class="fas fa-key"></i></button>
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