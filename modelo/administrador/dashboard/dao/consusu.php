
<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarConUsuDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $result=pg_query($conexion,"SELECT e.nombre AS nombre_empleado,e.apellido AS apellido_empleado,r.nombre AS nombre_rol,eu.nombre AS estadousuario_nombre FROM usuario u
                            JOIN empleado e ON u.cod_empleado=e.cod_empleado
                            JOIN rol r ON e.cod_rol=r.cod_rol
                            JOIN estadousuario eu ON u.cod_estadousuario=eu.cod_estadousuario
                            WHERE eu.nombre='Activo'");
        if(!$result){
            echo "Error al seleccionar el usuario";
        }

        $usuario['data']='';

        while($row=pg_fetch_assoc($result)){
            $usuario['data'].='<div class="personal list-group-item d-flex justify-content-between align-items-center px-0">';
            $usuario['data'].='<div>';
            $usuario['data'].='<div class="fw-bold">'.$row['nombre_empleado'].' '.$row['apellido_empleado'].'</div>';
            $usuario['data'].='<small class="text-muted">'.$row['nombre_rol'].'</small>';
            $usuario['data'].='</div>';
            $usuario['data'].='<span class="badge bg-success">'.$row['estadousuario_nombre'].'</span>';
            $usuario['data'].='';
            $usuario['data'].='</div>';
        }

        header('Content-Type: application/json');
        echo json_encode($usuario);
    }
}
?>