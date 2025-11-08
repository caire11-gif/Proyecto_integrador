<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result=pg_query($conexion,"SELECT e.nombre AS nombre_empleado,e.apellido AS apellido_empleado,r.nombre AS nombre_rol,eu.nombre AS estadousuario_nombre FROM usuario u
                            JOIN empleado e ON u.cod_empleado=e.cod_empleado
                            JOIN rol r ON e.cod_rol=r.cod_rol
                            JOIN estadousuario eu ON u.cod_estadousuario=eu.cod_estadousuario");
if(!$result){
    echo "Error al seleccionar el usuario";
    exit;
}

$usuario=[];

while($row=pg_fetch_assoc($result)){
    $usuario[]=[
        'nombre_apellido'=>trim($row['nombre_empleado']),
        'apellido_empleado'=>trim($row['apellido_empleado']),
        'nombre_rol'=>trim($row['nombre_rol']),
        'nombre_estadousuario'=>trim($row['estadousuario_nombre'])
    ];
}

header('Content-Type: application/json');
echo json_encode($usuario);
?>