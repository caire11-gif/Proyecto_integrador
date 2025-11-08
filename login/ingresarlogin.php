<?php include('conexion.php') ?>

<?php
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['accion']) && $_POST['accion']==='ingresar'){
    $usuario = pg_escape_string($conexion, $_POST['usuario']);
    $contraseña = pg_escape_string($conexion, $_POST['contraseña']);

    $login=pg_query_params($conexion,"SELECT u.cod_usuario,u.usuario,u.contraseña,r.nombre AS rol_nombre,e.nombre AS empleado_nombre,e.apellido AS empleado_apellido from usuario u JOIN empleado e ON u.cod_empleado=e.cod_empleado JOIN rol r ON e.cod_rol=r.cod_rol
                            WHERE usuario=$1 AND contraseña=$2",array($usuario,$contraseña));
    if(!$login){
        echo "Error al iniciar sesión";
    }

    $con=pg_fetch_assoc($login);

    $rol=trim($con['rol_nombre']);
    if(!$rol){
        echo "No se encontró el rol del usuario";
    }

    $usuario=trim($con['cod_usuario']);
    $emp=trim($con['empleado_nombre']);
    $ape=trim($con['empleado_apellido']);

    $veri=pg_num_rows($login);
    if($veri>0){
        $url=' ';
        if($rol==='Administrador'){
            $url="../administrador/dashboard.html";
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
        } else if($rol==='Encargado'){
            $url="../almacen/dashboard.html";
            $_SESSION['usuarioencargado']=$usuario;
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
        } else if($rol==='Vendedor'){
            $url="../vendedor/dashboard.html";
            $_SESSION['usuario']=$usuario;
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
                                icon: 'error',
                                title: 'Credenciales incorrectas',
                                text: 'Verifique sus credenciales',
                                width: '350px'
                            }).then(() => {
                                window.location.href = '" . $_SERVER['PHP_SELF'] . "';
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
                            icon: 'error',
                            title: 'Fallo en el inicio de sesión',
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