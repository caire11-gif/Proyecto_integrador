<?php
class EmpleadoActualizarDto{
    private $codigo;
    private $nombre;
    private $apellido;
    private $dni;
    private $fecha;
    private $telefono;
    private $rol;

    public function __construct($codigo, $nombre, $apellido, $dni, $fecha, $telefono, $rol){
        $this->codigo=$codigo;
        $this->nombre=$nombre;
        $this->apellido=$apellido;
        $this->dni=$dni;
        $this->fecha=$fecha;
        $this->telefono=$telefono;
        $this->rol=$rol;
    }

    public function getCodigo(){
        return $this->codigo;
    }

    public function setCodigo($codigo){
        $this->codigo=$codigo;
    }

    public function getNombre(){
        return $this->nombre;
    }

    public function setNombre($nombre){
        $this->nombre=$nombre;
    }

    public function getApellido(){
        return $this->apellido;
    }

    public function setApellido($apellido){
        $this->apellido=$apellido;
    }

    public function getDni(){
        return $this->dni;
    }

    public function setDni($dni){
        $this->dni=$dni;
    }

    public function getFecha(){
        return $this->fecha;
    }

    public function setFecha($fecha){
        $this->fecha=$fecha;
    }

    public function getTelefono(){
        return $this->telefono;
    }

    public function setTelefono($telefono){
        $this->telefono=$telefono;
    }

    public function getRol(){
        return $this->rol;
    }

    public function setRol($rol){
        $this->rol=$rol;
    }
}
?>