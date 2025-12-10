<?php
$cod_producto_editar = $_POST['codigoProductoEdit'] ?? '';
$nombre = $_POST['nombreProductoEdit'] ?? '';
$precio_caja = $_POST['precioCostoEdit'] ?? 0;
$unidades_por_caja = $_POST['unidadesCajaEdit'] ?? 1;
$precio_venta = $_POST['precioVentaEdit'] ?? 0;
$cod_categoria = $_POST['categoriaProductoEdit'] ?? '';
$cod_proveedor = $_POST['proveedorProductoEdit'] ?? '';

echo $cod_producto_editar.' - '.$nombre.' - '.$precio_caja.' - '.$unidades_por_caja;

require_once("../../../../modelo/almacen/gestionproductos/dto/prodactudto.php");

$productoActualizarDto=new ProductoActualizarDto(
    $cod_producto_editar,
    $nombre,
    $precio_caja,
    $unidades_por_caja,
    $precio_venta,
    $cod_categoria,
    $cod_proveedor
);

require '../../../../modelo/almacen/gestionproductos/dao/actualizarprod.php';
require_once ("../../../../modelo/almacen/gestionproductos/dao/actualizarprod.php");

$dao=new ActualizarProdDao();
$dao->actualizar($productoActualizarDto,$conexion);
?>