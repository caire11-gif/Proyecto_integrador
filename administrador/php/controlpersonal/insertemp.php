<?php include('../../../login/ingresarlogin.php') ?>

<?php
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='insertar')){
    $codemp=$_POST['codigoEmpleado'] ?? '';
    $nomemp=$_POST['nombreEmpleado'] ?? '';
    $apeemp=$_POST['apellidoEmpleado'] ?? '';
    $dniemp=$_POST['dniEmpleado'] ?? '';
    $telemp=$_POST['telefonoEmpleado'] ?? '';
    $fecnacemp=$_POST['fechaNacEmpleado'] ?? '';
    $rolemp=$_POST['rolEmpleado'] ?? '';

    $timestamp = substr(time(), -6);
    $codemp = 'EMP' . $timestamp;

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

    if($veric===0){
        if($verit===0){
            if($verid===0){
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
                                        window.location.href = '../../../administrador/controlpersonal.html';
                                    });
                                });
                            </script>
                        ";
                    } else {
                        echo "Error al crear usuario automático: " . pg_last_error($conexion);
                    }
                }

                exit;
            } else {
                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function(){
                            Swal.fire({
                                icon: 'warning',
                                title: 'Oops...',
                                text: 'No pueden haber dos dni iguales. Intente con otro.',
                                width: '350px'
                            }).then(() => {
                                window.location.href = '../../../administrador/controlpersonal.html';
                            });
                        });
                    </script>
                ";
                        
                exit;
            }
        } else {
            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Oops...',
                            text: 'No pueden haber dos teléfonos iguales. Intente con otro.',
                            width: '350px'
                        }).then(() => {
                            window.location.href = '../../../administrador/controlpersonal.html';
                        });
                    });
                </script>
            ";
                        
            exit;
        }
    } else {
        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
                document.addEventListener('DOMContentLoaded', function(){
                    Swal.fire({
                        icon: 'warning',
                        title: 'Oops...',
                        text: 'No pueden haber dos códigos iguales. Intente con otro.',
                        width: '350px'
                    }).then(() => {
                        window.location.href = '../../../administrador/controlpersonal.html';
                    });
                });
            </script>
        ";
                        
        exit;
    }
}
?>