<?php
$nombre = $_POST['nombreProducto'] ?? '';
$precio_caja = $_POST['precioCosto'] ?? 0;
$unidades_por_caja = $_POST['unidadesCaja'] ?? 1;
$precio_venta = $_POST['precioVenta'] ?? 0;
$cod_categoria = $_POST['categoriaProducto'] ?? '';
$cod_proveedor = $_POST['proveedorProducto'] ?? '';

echo $nombre.' - '.$precio_caja.' - '.$unidades_por_caja.' - '.$precio_venta.' - '.$cod_categoria.' - '.$cod_proveedor;

require_once("../../../../modelo/almacen/gestionproductos/dto/proddto.php");

$productoDto=new ProductoDto(
    $nombre,
    $precio_caja,
    $unidades_por_caja,
    $precio_venta,
    $cod_categoria,
    $cod_proveedor
);

require '../../../../modelo/almacen/gestionproductos/dao/insertarprod.php';
require_once ("../../../../modelo/almacen/gestionproductos/dao/insertarprod.php");

$dao=new InsertarProductoDao();
$dao->insertar($productoDto,$conexion);
?>