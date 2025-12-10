<?php include('../../login/ingresarlogin.php') ?>

<?php
$columns=['cod_venta','fecha_venta'];

$table='venta';
$tableDetalleVenta='detalleventa';
$tableUsuario='usuario';

$id='cod_venta';

$campo=isset($_POST['campo']) ? pg_escape_string($conexion, $_POST['campo']) : null;

$where='';

if($campo!=null){
    $where="WHERE (";

    $cont=count($columns);

    for($i=0; $i<$cont; $i++){
        $where .= $columns[$i]." LIKE '". $campo ."%' OR ";
    }

    $where=substr_replace($where, "", -3);
    $where.=")";
}

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
$sql = "SELECT v.cod_venta,v.fecha_venta,SUM(dv.cantidad_unidades) AS cantidad,SUM(dv.total) AS total,u.usuario
FROM $table v
LEFT JOIN $tableDetalleVenta dv ON v.cod_venta=dv.cod_venta
LEFT JOIN $tableUsuario u ON v.cod_usuario=u.cod_usuario
$where
GROUP by v.cod_venta,u.usuario
$sOrder
$sLimit
";
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
        $output['data'] .= '<tr>';
        $output['data'] .= '<td>' . $row['cod_venta'] . '</td>';
        $output['data'] .= '<td>' . $row['fecha_venta'] . '</td>';
        $output['data'] .= '<td>' . $row['usuario'] . '</td>';
        $output['data'] .= '<td>' . $row['cantidad'] . '</td>';
        $output['data'] .= '<td>' . $row['total'] . '</td>';
        $output['data'] .= '';
        $output['data'] .= '</tr>';
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

header('Content-Type: application/json');
echo json_encode($output, JSON_UNESCAPED_UNICODE);
?>