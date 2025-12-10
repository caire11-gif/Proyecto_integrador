<?php require_once('../../login/ingresarlogin.php') ?>

<?php
$tab_activa = $_GET['tab'] ?? 'nuevaEntrada';
echo $tab_activa;

$entrada['data']='';

$ent=$tab_activa === 'nuevaEntrada' ? 'active' : '';
$his=$tab_activa === 'historialEntradas' ? 'active' : '';


    $entrada['data'].='<li class="nav-item">';
        $entrada['data'].='<a class="nav-link '.$ent.'" data-bs-toggle="tab" href="#nuevaEntrada" style="color:black" onclick="cambiarTab(\'nuevaEntrada\')">';
            $entrada['data'].='<i class="fas fa-plus-circle me-1"></i>Nueva Entrada';
        $entrada['data'].='</a>';

        $entrada['data'].='<a class="nav-link '.$his.'" data-bs-toggle="tab" href="#nuevaEntradas" style="color:black" onclick="cambiarTab(\'historialEntrada\')">';
            $entrada['data'].='<i class="fas fa-history me-1"></i>Historial';
        $entrada['data'].='</a>';
    $entrada['data'].='</li>';


header('Content-Type: application/json');
echo json_encode($entrada);
?>