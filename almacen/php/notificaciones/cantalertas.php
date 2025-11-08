<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result=pg_query($conexion, "SELECT COUNT(cod_notificacion) FROM notificacion");
?>