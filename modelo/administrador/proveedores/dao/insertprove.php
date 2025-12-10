<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class InsertarProveDao{
    public function insertar(ProveedorDto $dto){
        $conexion=Conexion::getConexion();

        $rucprove=$dto->getRuc();
        $nomprove=$dto->getRazonsocial();
        $telprove=$dto->getTelefono();
        $dirprove=$dto->getDireccion();

        //#######################################################################################################################

        // Obtener el último código de proveedor
        function obtenerSiguienteCodigo($conexion, $tabla, $prefijo) {
            $configuraciones = [
                'proveedor' => ['columna' => 'cod_proveedor', 'formato' => 'PROVE'],
            ];

            $config = $configuraciones[$tabla] ?? ['columna' => "cod_$tabla", 'formato' => $prefijo];
            $columna = $config['columna'];
            $formato_prefijo = $config['formato'];

            // ✅ Ordenar numéricamente
            $query = "
                SELECT $columna 
                FROM $tabla 
                WHERE $columna LIKE '{$formato_prefijo}%'
                ORDER BY CAST(SUBSTRING($columna FROM '[0-9]+$') AS INTEGER) DESC
                LIMIT 1
            ";

            $result = pg_query($conexion, $query);
            if(!$result){
                throw new Exception("Error en la consulta: " . pg_last_error($conexion));
            }

            if(pg_num_rows($result) > 0) {
                $ultimo_cod = pg_fetch_assoc($result)[$columna];
                preg_match('/\d+$/', $ultimo_cod, $matches);
                $nuevo_numero = intval($matches[0]) + 1;
            } else {
                $nuevo_numero = 1;
            }

            // ✅ Ceros a la izquierda
            return sprintf("%s%03d", $formato_prefijo, $nuevo_numero);
        }

        $codprove = obtenerSiguienteCodigo($conexion, 'proveedor', 'PROD');

        $nomprove=ucwords(strtolower($nomprove));
        $telprove = str_replace(' ', '', $telprove);
        $dirprove = mb_convert_encoding($dirprove, 'UTF-8', 'auto');
        
        $dirprove = ucwords(strtolower($dirprove));

        //#######################################################################################################################

        $vericodprove=pg_query_params($conexion, "SELECT COUNT(cod_proveedor) FILTER(WHERE cod_proveedor=$1) AS cantidad_codigo_proveedor, COUNT(ruc) FILTER(WHERE ruc=$2) AS ruc_proveedor, COUNT(telefono) FILTER(WHERE telefono=$3) AS cantidad_telefono_proveedor from proveedor",array($codprove,$rucprove,$telprove));
        if(!$vericodprove){
            echo "Error al verificar el código, teléfono y ruc del proveedor";
        }

        $veri=pg_fetch_assoc($vericodprove);
        if($veri){
            $veric=(int)$veri['cantidad_codigo_proveedor'];
            $verit=(int)$veri['cantidad_telefono_proveedor'];
            $verir=(int)$veri['ruc_proveedor'];
        } else {
            $veric=$verit=$verir=0;
        }

        //#######################################################################################################################

        if($veric!==0){
            $this->alert("No pueden haber dos códigos iguales");
        }

        if($verit!==0){
            $this->alert("No pueden haber dos teléfonos iguales");
        }

        if($verir){
            $this->alert("No pueden haber dos ruc iguales");
        }

        $sql1="INSERT INTO proveedor(cod_proveedor,razon_social,ruc,telefono,direccion) VALUES ($1,$2,$3,$4,$5)";
        $resul=pg_query_params($conexion,$sql1,array($codprove,$nomprove,$rucprove,$telprove,$dirprove));

        if(!$resul){
            echo "Un error de conexión ocurrió.";
        }
        $this->alertSuccess("Se registró el proveedor correctamente");
        exit;
    }
    
    private function alert($msg){
        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops...',
                    text: '$msg'
                }).then(() => {
                    history.back();
                });
            </script>
        ";
        exit;
    }

    private function alertSuccess($msg){
        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: '$msg'
                }).then(() => {
                    window.location.href = '../../../../vista/administrador/proveedores.html';
                });
            </script>
        ";
        exit;
    }
}
?>