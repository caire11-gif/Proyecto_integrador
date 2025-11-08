<?php
$conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
if(!$conexion){
    echo "Un error de conexión ocurrió.";
    exit;
}

session_start();
?>