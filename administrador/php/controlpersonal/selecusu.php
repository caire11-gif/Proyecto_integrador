<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result = pg_query($conexion, "SELECT u.cod_usuario, u.cod_empleado, u.usuario, u.contraseña, eu.nombre as estadousuario_nombre FROM usuario u 
                                JOIN estadousuario eu ON u.cod_estadousuario = eu.cod_estadousuario");
if(!$result){
    echo "Error al seleccionar el usuario";
}

$usuario=[];

while($row=pg_fetch_assoc($result)){
    $usuario[]=[
        'cod_usuario'=>trim($row['cod_usuario']),
        'cod_empleado'=>trim($row['cod_empleado']),
        'usuario'=>trim($row['usuario']),
        'contraseña'=>trim($row['contraseña']),
        'estado_usuario'=>trim($row['estadousuario_nombre'])
    ];
}

header('Content-Type: application/json');
echo json_encode($usuario);
?>