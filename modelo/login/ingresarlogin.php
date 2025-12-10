<?php include('conexion.php') ?>

<?php
$conexion=Conexion::getConexion();

session_start();

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['accion']) && $_POST['accion']==='ingresar'){
    //Verificar que los campos no estén vacíos
    if(empty($_POST['usuario']) || empty($_POST['contraseña'])) {
        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Credenciales incorrectas',
                            text: 'Usuario y contraseña son requeridos',
                            width: '350px'
                        }).then(() => {
                            window.location.href = '../../vista/login.html';
                        });
                    });
                </script>
        ";
        exit;
    }
    
    //Almacenar el usuario y contraseña en sus variable
    $usuario = pg_escape_string($conexion, $_POST['usuario']);
    $contraseña = pg_escape_string($conexion, $_POST['contraseña']);

    //Buscar el usuario y contraseña en la base de datos
    $login = pg_query_params($conexion, "SELECT u.cod_usuario, u.usuario, u.contraseña, r.nombre AS rol_nombre, e.nombre AS empleado_nombre, 
                                         e.apellido AS empleado_apellido FROM usuario u 
                                         JOIN empleado e ON u.cod_empleado = e.cod_empleado 
                                         JOIN rol r ON e.cod_rol = r.cod_rol
                                         WHERE usuario = $1 AND contraseña = $2",array($usuario, $contraseña)
    );
    
    if(!$login) {
        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Credenciales incorrectas',
                            text: 'No se pudo iniciar sesión',
                            width: '350px'
                        }).then(() => {
                            window.location.href = '../../vista/login.html';
                        });
                    });
                </script>
        ";
        exit;
    }
    
    //Verificar si hay filas retornadas
    $num_rows = pg_num_rows($login);
    
    if($num_rows === 0) {
        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Credenciales incorrectas',
                            text: 'No se pudo iniciar sesión',
                            width: '350px'
                        }).then(() => {
                            window.location.href = '../../vista/login.html';
                        });
                    });
                </script>
        ";
        exit;
    }
    
    // Obtener los datos del usuario
    $con = pg_fetch_assoc($login);
    
    if(!$con) {
        echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Credenciales incorrectas',
                            text: 'No se pudo iniciar sesión',
                            width: '350px'
                        }).then(() => {
                            window.location.href = '../../vista/login.html';
                        });
                    });
                </script>
        ";
        exit;
    }
    
    // Asignar variables - usar valores por defecto si están vacíos
    $usu = isset($con['usuario']) ? trim($con['usuario']) : '';
    $contra = isset($con['contraseña']) ? trim($con['contraseña']) : '';
    $rol = isset($con['rol_nombre']) ? trim($con['rol_nombre']) : '';
    $cod_usuario = isset($con['cod_usuario']) ? trim($con['cod_usuario']) : '';
    $emp = isset($con['empleado_nombre']) ? trim($con['empleado_nombre']) : '';
    $ape = isset($con['empleado_apellido']) ? trim($con['empleado_apellido']) : '';
    
    // Comparación CORRECTA (== en lugar de =)
    if($usuario == $usu && $contraseña == $contra) {
        
        if(empty($rol)) {
            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Credenciales incorrectas',
                            text: 'No se encontró el rol del usuario',
                            width: '350px'
                        }).then(() => {
                            window.location.href = '../../vista/login.html';
                        });
                    });
                </script>
            ";
            exit;
        }
        
        // Redirigir según el rol
        $url = '';
        if($rol === 'Administrador'){
            $url="../../vista/administrador/dashboard.html";
            $_SESSION['nombreusuarioadmin']=$emp;
            $_SESSION['apellidousuarioadmin']=$ape;

            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function(){
                            Swal.fire({
                                icon: 'success',
                                title: 'Bienvenido/a $emp!',
                                text: 'Inicio sesión con éxito',
                                width: '350px'
                            }).then(() => {
                                window.location.href = '$url';
                            });
                        });
                </script>";
            exit;
        } else if($rol === 'Encargado'){
            $url="../../vista/almacen/dashboard.html";
            $_SESSION['usuarioencargado']=$cod_usuario;
            $_SESSION['nombreusuarioencargado']=$emp;
            $_SESSION['apellidousuarioencargado']=$ape;

            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function(){
                            Swal.fire({
                                icon: 'success',
                                title: 'Bienvenido/a $emp!',
                                text: 'Inicio sesión con éxito',
                                width: '350px'
                            }).then(() => {
                                window.location.href = '$url';
                            });
                        });
                    </script>";
            exit;
        } else if($rol === 'Vendedor'){
            $url="../../vista/vendedor/dashboard.php";
            $_SESSION['usuario']=$cod_usuario;
            $_SESSION['nombreusuariovendedor']=$emp;
            $_SESSION['apellidousuariovendedor']=$ape;

            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function(){
                            Swal.fire({
                                icon: 'success',
                                title: 'Bienvenido/a $emp!',
                                text: 'Inicio sesión con éxito',
                                width: '350px'
                            }).then(() => {
                                window.location.href = '$url';
                            });
                        });
                    </script>";
            exit;
        } else {
            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function(){
                            Swal.fire({
                                icon: 'warning',
                                title: 'Credenciales incorrectas',
                                text: 'Verifique sus credenciales',
                                width: '350px'
                            }).then(() => {
                                window.location.href = '../login.html';
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
                            title: 'Credenciales incorrectas',
                            text: 'No se pudo iniciar sesión',
                            width: '350px'
                        }).then(() => {
                            window.location.href = '../login.html';
                        });
                    });
                </script>
        ";
        exit;
    }
}
?>