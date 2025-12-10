<?php require_once('../../login/ingresarlogin.php') ?>

<?php
// Columnas a mostrar en la tabla
$columns = ['cod_compra', 'fecha_compra'];

// Nombre de la tabla
$table = "compra";
$tableUsuario="usuario";
$tableProveedor="proveedor";
$tableMetodoPago="metodopago";
$tableDetalleCompra="detallecompra";

// Clave principal de la tabla
$id = 'cod_compra';

// Campo a buscar
$campo = isset($_POST['buscarHistorial']) ? pg_escape_string($conexion, $_POST['buscarHistorial']) : null;

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
$sql = "SELECT c.cod_compra, c.fecha_compra AS fecha, pr.razon_social AS proveedor_nombre, u.usuario AS usuario_registro,
                            SUM(dc.cantidad_unidades) AS total_productos, SUM(dc.total) AS total_compra, mp.nombre AS metodo_pago
FROM $table c
JOIN $tableMetodoPago mp ON c.cod_metodopago = mp.cod_metodopago
JOIN $tableProveedor pr ON c.cod_proveedor = pr.cod_proveedor
JOIN $tableUsuario u ON c.cod_usuario = u.cod_usuario
JOIN $tableDetalleCompra dc ON c.cod_compra = dc.cod_compra
GROUP BY c.cod_compra,pr.razon_social,u.usuario,mp.nombre
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
        $output['data'] .= '<td><strong>' . $row['cod_compra'] . '</strong></td>';
        $output['data'] .= '<td>' . $row['fecha'] . '</td>';
        $output['data'] .= '<td>' . $row['proveedor_nombre'] . '</td>';
        $output['data'] .= '<td><span class="badge bg-info">' . $row['total_productos'] . ' productos</span></td>';
        $output['data'] .= '<td><strong>S/.' . number_format($row['total_compra'],2) . '</strong></td>';
        $output['data'] .= '<td>' . $row['metodo_pago'] . '</td>';
        $output['data'] .= '<td>' . $row['usuario_registro'] . '</td>';
        $output['data'] .= '
        <td>
            <button class="btn btn-sm btn-outline-primary action-btn btnVerDetalles" title="Ver detalles" data-codcompra="'.$row['cod_compra'].'">
                <i class="fas fa-eye"></i>
            </button>
        </td>
        ';
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