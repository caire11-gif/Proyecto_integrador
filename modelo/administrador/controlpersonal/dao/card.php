<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarCardsDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $result1 = pg_query($conexion, "SELECT COUNT(cod_empleado) AS cantidad_empleado FROM empleado");
        if(!$result1){
            echo "Error al contar los empleados";
            exit;
        }

        $row1=pg_fetch_assoc($result1);

        $sel1=(int) $row1['cantidad_empleado'];

        $cantemp=0;

        if($sel1===0){
            $cantemp=0;
        } else {
            $cantemp=$sel1;
        }

        //#############################################################################################################

        $result2 = pg_query($conexion, "SELECT COUNT(cod_usuario) AS cantidad_usuario FROM usuario");
        if(!$result2){
            echo "Error al contar los usuarios";
            exit;
        }

        $row2=pg_fetch_assoc($result2);

        $sel2=(int) $row2['cantidad_usuario'];

        $cantusu=0;

        if($sel2===0){
            $cantusu=0;
        } else {
            $cantusu=$sel2;
        }

        //#############################################################################################################

        $result3=pg_query($conexion, "SELECT COUNT(cod_usuario) AS usuario_activo FROM USUARIO WHERE cod_estadousuario='est001'");
        if(!$result3){
            echo "Error al contar los usuarios activos";
        }

        $row3=pg_fetch_assoc($result3);

        $sel3=(int) $row3['usuario_activo'];

        $usuacti=0;

        if($sel3===0){
            $usuacti=0;
        } else {
            $usuacti=$sel3;
        }

        //#############################################################################################################

        $result4=pg_query($conexion, "SELECT COUNT(cod_usuario) AS usuario_inactivo FROM USUARIO WHERE cod_estadousuario='est002'");
        if(!$result4){
            echo "Error al contar los usuarios inactivos";
        }

        $row4=pg_fetch_assoc($result4);

        $sel4=(int) $row4['usuario_inactivo'];

        $usuinacti=0;

        if($sel4===0){
            $usuinacti=0;
        } else {
            $usuinacti=$sel4;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'cantidad_empleado'=>$cantemp,
            'cantidad_usuario'=>$cantusu,
            'usuario_activo'=>$usuacti,
            'usuario_inactivo'=>$usuinacti
        ]);
    }
}
?>