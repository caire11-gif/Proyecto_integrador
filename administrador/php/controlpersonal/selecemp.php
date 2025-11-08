<?php include('../../../login/ingresarlogin.php') ?>

<?php
$result = pg_query($conexion, "SELECT e.cod_empleado, e.nombre, e.apellido, e.dni, e.fecha_nacimiento, e.telefono, r.nombre as rol_nombre FROM empleado e 
                                JOIN rol r ON e.cod_rol = r.cod_rol ORDER BY e.cod_empleado");
if(!$result){
    echo "Error al seleccionar el empleado";
}

$empleado=[];

while($row=pg_fetch_assoc($result)){
    $empleado[]=[
        'cod_empleado'=>trim($row['cod_empleado']),
        'nombre'=>trim($row['nombre']),
        'apellido'=>trim($row['apellido']),
        'dni'=>trim($row['dni']),
        'fecha_nacimiento'=>trim($row['fecha_nacimiento']),
        'telefono'=>trim($row['telefono']),
        'rol_nombre'=>trim($row['rol_nombre'])
    ];
}

header('Content-Type: application/json');
echo json_encode($empleado);
?>