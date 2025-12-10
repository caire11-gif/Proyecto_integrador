<?php include('../../login/ingresarlogin.php') ?>

<?php
class ExportarEmpDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $result=pg_query($conexion,"SELECT e.cod_empleado, e.nombre, e.apellido, e.dni, e.fecha_nacimiento, e.telefono, r.nombre AS rolnombre FROM empleado e
                            JOIN rol r ON e.cod_rol=r.cod_rol
                            ORDER BY e.cod_empleado");

        if(!$result){
            echo "Error al seleccionar los empleados para exportar";
        }

        $expemp=[];

        while($row=pg_fetch_assoc($result)){
            $expemp[]=$row;
        }

        header('Content-Type: application/json');
        echo json_encode($expemp);
    }
}
?>