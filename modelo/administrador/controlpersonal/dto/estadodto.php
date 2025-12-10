<?php
class EstadoDto{
    private $codigo;
    private $estado;

    public function __construct($codigo, $estado){
        $this->codigo=$codigo;
        $this->estado=$estado;
    }

    public function getCodigo(){
        return $this->codigo;
    }

    public function setCodigo($codigo){
        $this->codigo=$codigo;
    }

    public function getEstado(){
        return $this->estado;
    }

    public function setEstado($estado){
        $this->estado=$estado;
    }
}
?>