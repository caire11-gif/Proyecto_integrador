<?php include('../../login/ingresarlogin.php') ?>

<?php
// Columnas a mostrar en la tabla
$columns = ['cod_movimiento', 'fecha_movimiento', 'cod_tipomovimiento'];

// Nombre de la tabla
$table = "movimiento";
$tableProducto="producto";
$tableTipoMovimiento="tipomovimiento";
$tableUsuario="usuario";

// Clave principal de la tabla
$id = 'cod_movimiento';

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
$sql = "SELECT m.cod_movimiento, m.fecha_movimiento, p.nombre as producto_nombre, tm.nombre as tipo_movimiento, m.cod_tipomovimiento, m.observacion, u.usuario, p.stock
FROM $table m
JOIN $tableProducto p ON m.cod_producto = p.cod_producto
JOIN $tableTipoMovimiento tm ON m.cod_tipomovimiento = tm.cod_tipomovimiento
JOIN $tableUsuario u ON m.cod_usuario = u.cod_usuario
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
        $output['data'] .= '<tr>';
        $output['data'] .= '<td>' . $row['cod_movimiento'] . '</td>';
        $output['data'] .= '<td>' . $row['fecha_movimiento'] . '</td>';
        $output['data'] .= '<td>' . $row['producto_nombre'] . '</td>';
        $output['data'] .= '<td>' . $row['tipo_movimiento'] . '</td>';
        $output['data'] .= '<td>' . $row['stock'] . '</td>';
        $output['data'] .= '<td>' . $row['usuario'] . '</td>';
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

echo json_encode($output, JSON_UNESCAPED_UNICODE);
?>
