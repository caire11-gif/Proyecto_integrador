<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class SeleccionarAlertasDao {
    public function seleccionar() {
        $conexion=Conexion::getConexion();

        $result1=pg_query($conexion, "SELECT COUNT(*) AS stock_agotado FROM producto WHERE stock=0");
        if(!$result1){
            echo "Error al contar los productos con stock cero";
        }

        $result2=pg_query($conexion, "SELECT COUNT(*) AS stock_bajo FROM producto WHERE stock>0 AND stock<11");
        if(!$result2){
            echo "Error al contar los productos son stock entre 1 y 10";
        }

        //#############################################################################################################

        $row1=pg_fetch_assoc($result1);
        $stockago=(int) $row1['stock_agotado'];

        $row2=pg_fetch_assoc($result2);
        $stockbajo=(int) $row2['stock_bajo'];

        $stockcombi=$stockago+$stockbajo;

        $stockprod['data']='';

        if($stockago>0 || $stockbajo>0){
            $stockprod['data'].='
                            <div class="alertas-stock" id="alertasStock">
                                <div class="alerta-header">
                                    <h3><i class="fas fa-bell"></i> Alertas de Stock</h3>
                                    <span class="badge-alerta">'.$stockcombi.'</span>
                                </div>
            ';

            if($stockago>0){
                $stockprod['data'].='
                                <div class="lista-alertas" id="listaAlertas">
                                    <div class="alert alert-danger d-flex align-items-center">
                                        <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
                                        <div>
                                            <strong>'.$stockago.' productos con stock bajo</strong>
                                            <div class="small">Necesita reposición inmediata</div>
                                        </div>
                                    </div>
                                </div>
                ';
            }
                                
            if($stockbajo>0){
                $stockprod['data'].='
                                <div class="lista-alertas" id="listaAlertas">
                                    <div class="alert alert-warning d-flex align-items-center">
                                        <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
                                        <div>
                                            <strong>'.$stockbajo.' productos con stock bajo</strong>
                                            <div class="small">Considere reponer pronto (≤10 unidades)</div>
                                        </div>
                                    </div>
                                </div>
                ';
            }                
                      
            $stockprod['data'].='
                                </div>
            ';
                            
                
        } else {
            $stockprod['data'].='
                            <div class="alertas-stock" id="alertasStock">
                                <div class="alerta-header">
                                    <h3><i class="fas fa-bell"></i> Alertas de Stock</h3>
                                    <span class="badge-alerta">'.$stockcombi.'</span>
                                </div>

                                <div class="lista-alertas text-center" id="listaAlertas">
                                    <p>Los productos tienen excelente stock</p>
                                </div>
                            </div>
            ';
        }

        header('Content-Type: application/json');
        echo json_encode($stockprod);
    }
}
?>