<?php include('../../login/ingresarlogin.php') ?>

<?php
class ExportarEmpDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $result=pg_query($conexion,"SELECT u.cod_usuario, u.cod_empleado, u.usuario, u.contraseña, eu.nombre AS estadousuarionombre FROM usuario u
                            JOIN estadousuario eu ON u.cod_estadousuario=eu.cod_estadousuario
                            ORDER BY u.cod_usuario");

        if(!$result){
            echo "Error al seleccionar los usuarios para exportar";
        }

        $expusu=[];

        while($row=pg_fetch_assoc($result)){
            $expusu[]=$row;
        }

        header('Content-Type: application/json');
        echo json_encode($expusu);
    }
}
?>