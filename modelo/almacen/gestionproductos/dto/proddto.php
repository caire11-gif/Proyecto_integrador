<?php
class ProductoDto{
    private $nombre;
    private $preciocaja;
    private $unidades;
    private $precioventa;
    private $categoria;
    private $proveedor;

    public function __construct($nombre, $preciocaja, $unidades, $precioventa, $categoria, $proveedor){
        $this->nombre=$nombre;
        $this->preciocaja=$preciocaja;
        $this->unidades=$unidades;
        $this->precioventa=$precioventa;
        $this->categoria=$categoria;
        $this->proveedor=$proveedor;
    }

    public function getNombre(){
        return $this->nombre;
    }

    public function setNombre($nombre){
        $this->nombre=$nombre;
    }

    public function getPreciocaja(){
        return $this->preciocaja;
    }

    public function setPreciocaja($preciocaja){
        $this->preciocaja=$preciocaja;
    }

    public function getUnidades(){
        return $this->unidades;
    }

    public function setUnidades($unidades){
        $this->unidades=$unidades;
    }

    public function getPrecioventa(){
        return $this->precioventa;
    }

    public function setPrecioventa($precioventa){
        $this->precioventa=$precioventa;
    }

    public function getCategoria(){
        return $this->categoria;
    }

    public function setCategoria($categoria){
        $this->categoria=$categoria;
    }

    public function getProveedor(){
        return $this->proveedor;
    }

    public function setProveedor($proveedor){
        $this->proveedor=$proveedor;
    }
}
?>