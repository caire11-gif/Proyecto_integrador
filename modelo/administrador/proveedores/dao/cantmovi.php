<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarCantidadMoviDao{
    public function seleccionar(){
        $conexion=Conexion::getConexion();

        $result2=pg_query($conexion,"SELECT COUNT(cod_movimiento) AS cantidad_movimiento FROM movimiento m
                                     JOIN tipomovimiento tm ON m.cod_tipomovimiento=tm.cod_tipomovimiento
                                     WHERE tm.nombre='Entrada'");
        if(!$result2){
            echo "Error al contar los producto entrantes.". pg_last_error($conexion);
        }

        $row=pg_fetch_assoc($result2);

        $sel=(int) $row['cantidad_movimiento'];

        $cantmovi=0;

        if($sel===0){
            $cantmovi=0;
        } else {
            $cantmovi=$sel;
        }

        echo json_encode([
            'cantidad_movimiento'=>$cantmovi
        ]);
            }
        }
?>