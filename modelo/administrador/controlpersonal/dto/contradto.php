<?php
class ContraseñaDto{
    private $codigo;
    private $contraseña;
    private $nuevacontraseña;
    private $confirmacontraseña;

    public function __construct($codigo, $contraseña, $nuevacontraseña, $confirmacontraseña){
        $this->codigo=$codigo;
        $this->contraseña=$contraseña;
        $this->nuevacontraseña=$nuevacontraseña;
        $this->confirmacontraseña=$confirmacontraseña;
    }

    public function getCodigo(){
        return $this->codigo;
    }

    public function setCodigo($codigo){
        $this->codigo=$codigo;
    }

    public function getContraseña(){
        return $this->contraseña;
    }

    public function setContraseña($contraseña){
        $this->contraseña=$contraseña;
    }

    public function getNuevacontraseña(){
        return $this->nuevacontraseña;
    }

    public function setNuevacontraseña($nuevacontraseña){
        $this->nuevacontraseña=$nuevacontraseña;
    }

    public function getConfirmacontraseña(){
        return $this->confirmacontraseña;
    }

    public function setConfirmacontraseña($confirmacontraseña){
        $this->confirmacontraseña=$confirmacontraseña;
    }
}
?>