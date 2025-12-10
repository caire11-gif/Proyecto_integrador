<?php require_once('../../../../modelo/login/ingresarlogin.php') ?>

<?php
class InsertarEmpDao{
    public function insertar(EmpleadoDto $dto){
        $conexion=Conexion::getConexion();

        $codemp=$dto->getCodigo();
        $nomemp=$dto->getNombre();
        $apeemp=$dto->getApellido();        
        $dniemp=$dto->getDni();
        $fecnacemp=$dto->getFecha();
        $telemp=$dto->getTelefono();
        $rolemp=$dto->getRol();

        echo $rolemp;

        function obtenerSiguienteCodigo($conexion, $tabla, $prefijo) {
            $configuraciones = [
                'empleado' => ['columna' => 'cod_empleado', 'formato' => 'EMP'],
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

        $codemp = obtenerSiguienteCodigo($conexion, 'empleado', 'EMP');
        $telemp = str_replace(' ', '', $telemp);
        $dniemp = str_replace(' ', '', $dniemp);

        $con=pg_query_params($conexion,"SELECT cod_rol FROM rol WHERE cod_rol=$1",array($rolemp));
        if(!$con){
            echo "Error al seleccionar el código del rol";
            exit;
        }
        
        $sel=pg_fetch_assoc($con);
        if(!$sel){
            echo "Error al ejecutar el rol";
            exit;
        }

        $codrolemp=trim($sel['cod_rol']);

        $vericodemp=pg_query_params($conexion, "SELECT COUNT(cod_empleado) FILTER(WHERE cod_empleado=$1) AS cantidad_codigo_empleado, COUNT(telefono) FILTER(WHERE telefono=$2) AS cantidad_telefono_empleado, COUNT(dni) FILTER(WHERE dni=$3) AS cantidad_dni_empleado from empleado",array($codemp,$telemp,$dniemp));
        if(!$vericodemp){
            echo "Error al verificar el código, teléfono y dni del empleado";
            exit;
        }

        $veri=pg_fetch_assoc($vericodemp);

        if($veri){
            $veric=(int) $veri['cantidad_codigo_empleado'];
            $verit=(int) $veri['cantidad_telefono_empleado'];
            $verid=(int) $veri['cantidad_dni_empleado'];
        } else {
            $veric=$verit=$verid=0;
        }

        if($veric !== 0){
            $this->alert("No pueden haber dos códigos iguales.");
        }

        if($verit !== 0){
            $this->alert("No pueden haber dos teléfonos iguales.");
        }

        if($verid !== 0){
            $this->alert("No pueden haber dos dni iguales.");
        }

        $insertar=pg_query_params($conexion,"INSERT INTO empleado(cod_empleado,nombre,apellido,dni,telefono,fecha_nacimiento,cod_rol) VALUES ($1,$2,$3,$4,$5,$6,$7)",array($codemp,$nomemp,$apeemp,$dniemp,$telemp,$fecnacemp,$codrolemp));

        function generarCodigoUsuario($conexion, $prefijo) {
            $result = pg_query($conexion, "SELECT COUNT(*) as total FROM usuario WHERE cod_usuario LIKE '$prefijo%'");
            $row = pg_fetch_assoc($result);
            $numero = $row['total'] + 1;
            return $prefijo . str_pad($numero, 3, '0', STR_PAD_LEFT);
        }

        function generarUsuario($nombre, $apellido) {
            $nombre = mb_strtolower(trim($nombre), 'UTF-8');
            $apellido = mb_strtolower(trim($apellido), 'UTF-8');

            $inicialNombre = substr($nombre, 0, 1);
            $usuario = $inicialNombre . '.' . $apellido;

            $usuario = iconv('UTF-8', 'ASCII//TRANSLIT', $usuario);
            $usuario = preg_replace('/[^a-zA-Z0-9.]/', '', $usuario);

            return $usuario;
        }

        if(!$insertar){
            echo "Error al insertar el empleado";
            exit;
        } else {
            $codigoUsuario = generarCodigoUsuario($conexion, 'USU');
            $usuarioGenerado = generarUsuario($nomemp, $apeemp);
            $contraseñaInicial = $dniemp;
            $codEstadoUsuario = 'est001';

            $sqlUsuario = "INSERT INTO usuario(cod_usuario, cod_empleado, usuario, contraseña, cod_estadousuario) 
                            VALUES ($1, $2, $3, $4, $5)";
            $resultUsuario = pg_query_params($conexion,$sqlUsuario,array($codigoUsuario, $codemp, $usuarioGenerado, $contraseñaInicial, $codEstadoUsuario)
            );
            
            if ($resultUsuario) {
                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function(){
                            Swal.fire({
                                icon: 'success',
                                title: 'Empleado registrado!',
                                html: 'Se registró el empleado correctamente<br><br>' +
                                        '<strong>Usuario generado:</strong> $usuarioGenerado<br>' +
                                        '<strong>Contraseña inicial:</strong> $dniemp<br><br>' +
                                        'El empleado debe cambiar su contraseña en el primer inicio.',
                                width: '450px'
                            }).then(() => {
                                window.location.href = '../../../../vista/administrador/controlpersonal.html';
                            });
                        });
                    </script>
                ";
            } else {
                echo "Error al crear usuario automático: " . pg_last_error($conexion);
            }
        }

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
}
?>