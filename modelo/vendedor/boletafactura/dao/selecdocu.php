<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarHistorialDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        // Columnas a mostrar en la tabla
        $columns = ['cod_venta', 'fecha_venta', 'dni', 'nombre'];

        // Nombre de la tabla
        $table = "venta";
        $tableUsuario="usuario";
        $tableMetodoPago="metodopago";
        $tableTipoDocumento="tipodocumento";

        // Clave principal de la tabla
        $id = 'cod_venta';

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
        $sql = "SELECT v.cod_venta, v.fecha_venta, v.dni, v.nombre as cliente_nombre, u.usuario, m.nombre as metodo_pago, td.nombre as tipo_documento, 
                                     td.serie, td.numero, (SELECT SUM(dv.total) FROM detalleventa dv WHERE dv.cod_venta = v.cod_venta) as total
        FROM $table v 
        LEFT JOIN $tableUsuario u ON v.cod_usuario = u.cod_usuario
        LEFT JOIN $tableMetodoPago m ON v.cod_metodopago = m.cod_metodopago
        LEFT JOIN $tableTipoDocumento td ON v.cod_tipodocumento = td.cod_tipodocumento
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

         if ($resultado && pg_num_rows($resultado) > 0){
            while($doc = pg_fetch_assoc($resultado)){
                $cod_venta=trim($doc['cod_venta']);

                $queryDetalles = "
                    SELECT 
                        dv.*,
                        p.nombre as producto_nombre,
                        p.precio_venta as precio_unitario
                    FROM detalleventa dv
                    LEFT JOIN producto p ON dv.cod_producto = p.cod_producto
                    WHERE dv.cod_venta = $1
                ";

                $resultDetalles = pg_query_params($conexion, $queryDetalles, array($cod_venta));
                $productos = array();
                $total_venta = 0;
                
                while ($detalle = pg_fetch_assoc($resultDetalles)) {
                    $productos[] = $detalle;
                    $total_venta += floatval($detalle['total']);
                }
                
                // CORRECCIÓN: En Perú, el IGV ya está incluido en el precio
                // El total_venta ya incluye el IGV, por lo tanto:
                // Base Imponible = Total / 1.18
                // IGV = Total - Base Imponible
                $base_imponible = $total_venta / 1.18;
                $igv = $total_venta - $base_imponible;

                
                $productos_info = [
                    "productos" => $productos,
                    "base" => $base_imponible,
                    "igv" => $igv
                ];

                $fecha=date('d/m/Y H:i', strtotime($doc['fecha_venta']));
                $hora = date("H:i:s", strtotime($doc['fecha_venta']));

                $d=$doc['tipo_documento'];

                $badge='';

                if($d==='Factura'){
                    $badge='bg-primary';
                } else {
                    $badge='bg-success';
                }

                $tipo=$doc['tipo_documento'] ?? 'Boleta';
                $usuario=$doc['usuario'] ?? 'Sistema';

                $esFactura = $doc['tipo_documento'];
                $cliente='';
                $ruc='';

                if($esFactura==='Factura'){
                    $cliente='SEÑOR(ES):';
                    $ruc='RUC';
                } else {
                    $cliente='CLIENTE:';
                    $ruc='DNI';
                }

                $output['data'].='
                            <tr>
                                <td><strong><'.$doc['cod_venta'].'</strong></td>
                                <td>'.$fecha.'</td>
                                <td>
                                    <span class="badge '.$badge.'">
                                        '.$tipo.'
                                    </span>
                                </td>
                                <td>
                                    '.$doc['cliente_nombre'].'
                                    <br>
                                    <small class="text-muted">DNI: '.$doc['dni'].'</small>
                                </td>
                                <td>'.$usuario.'</td>
                                <td>'.$doc['metodo_pago'].'</td>
                                <td class="text-success"><strong>S/ '. number_format($doc['total'], 2).'</strong></td>
                                <td>
                                    <button class="btn btn-info btn-sm btnVerDetalles" data-codven="'.$doc['cod_venta'].'" data-tipodocu="'.$tipo.'" data-serie="'.$doc['serie'].'" data-numero="'.$doc['numero'].'" data-fecha="'.$fecha.'" data-hora="'.$hora.'"
                                    data-tipocliente="'.$cliente.'" data-cliente="'.$doc['cliente_nombre'].'" data-tiporuc="'.$ruc.'" data-dni="'.$doc['dni'].'" data-metpago="'.$doc['metodo_pago'].'" data-usuario="'.$usuario.'" 
                                    data-productos="'.htmlspecialchars(json_encode($productos_info, JSON_UNESCAPED_UNICODE), ENT_QUOTES).'">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                ';
            }
        } else {
            $output['data'].='
                        <tr>
                            <td colspan="8" style="text-align: center;">No hay documentos registrados</td>
                        </tr>
            ';
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
        echo json_encode($output);
    }
}
?>