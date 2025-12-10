<?php
class ProveedorActualizarDto{
    private $codigo;
    private $razonsocial;
    private $ruc;
    private $telefono;
    private $direccion;

    public function __construct($codigo, $razonsocial, $ruc, $telefono, $direccion){
        $this->codigo=$codigo;
        $this->razonsocial=$razonsocial;
        $this->ruc=$ruc;
        $this->telefono=$telefono;
        $this->direccion=$direccion;
    }

    public function getCodigo(){
        return $this->codigo;
    }

    public function setCodigo($codigo){
        $this->codigo=$codigo;
    }

    public function getRazonsocial(){
        return $this->razonsocial;
    }

    public function setRazonsocial($razonsocial){
        $this->razonsocial=$razonsocial;
    }

    public function getRuc(){
        return $this->ruc;
    }

    public function setRuc($ruc){
        $this->ruc=$ruc;
    }

    public function getTelefono(){
        return $this->telefono;
    }

    public function setTelefono($telefono){
        $this->telefono=$telefono;
    }

    public function getDireccion(){
        return $this->direccion;
    }

    public function setDireccion($direccion){
        $this->direccion=$direccion;
    }
}
?>