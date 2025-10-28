<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="login/login.css">
</head>
<body>
    <?php
    $conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
    if(!$conexion){
        echo "Un error de conexión ocurrió.";
        exit;
    }

    if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='ingresar')){
        $usuario = pg_escape_string($conexion, $_POST['usuario']);
        $contraseña = pg_escape_string($conexion, $_POST['contraseña']);

        $login=pg_query_params($conexion,"SELECT u.usuario,u.contraseña,r.nombre AS rol_nombre from usuario u JOIN empleado e ON u.cod_empleado=e.cod_empleado JOIN rol r ON e.cod_rol=r.cod_rol
                               WHERE usuario=$1 AND contraseña=$2",array($usuario,$contraseña));
        if(!$login){
            echo "Error al iniciar sesión";
        }

        $con=pg_fetch_assoc($login);

        $rol=$con['rol_nombre'];

        $veri=pg_num_rows($login);
        if($veri>0){
            $url=' ';
            if($rol==='Administrador'){
                $url="administrador/dashboard.php";

                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                        <script>
                            document.addEventListener('DOMContentLoaded', function(){
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Bienvenido administrador!',
                                    text: 'Inicio sesión con éxito',
                                    width: '350px'
                                }).then(() => {
                                    window.location.href = '$url';
                                });
                            });
                        </script>";
                exit;
            } else if($rol==='Encargado'){
                $url="./almacen/dashboard.php";
                
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                        <script>
                            document.addEventListener('DOMContentLoaded', function(){
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Bienvenido encargado!',
                                    text: 'Inicio sesión con éxito',
                                    width: '350px'
                                }).then(() => {
                                    window.location.href = '$url';
                                });
                            });
                        </script>";
                exit;
            } else if($rol==='Vendedor'){
                $url="vendedor/dashboard.php";
                
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                        <script>
                            document.addEventListener('DOMContentLoaded', function(){
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Bienvenido vendedor!',
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
                                window.location.href = '" . $_SERVER['PHP_SELF'] . "';
                            });
                        });
                    </script>
            ";
            exit;
        }
    }
    ?>

    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-store me-2"></i>MAD MARKET</h1>
            <p>Sistema de Gestión Integral</p>
        </div>
        
        <div class="login-body">
            <div id="loginAlert" class="alert alert-danger d-none">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <span id="alertMessage"></span>
            </div>
            
            <form id="loginForm" method="POST">
                <div class="mb-3">
                    <label class="form-label" for="usuario">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label" for="contraseña">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="contraseña" name="contraseña" placeholder="Ingresa tu contraseña" required>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">Recordar sesión</label>
                </div>
                
                <button type="submit" class="btn btn-login w-100" name="accion" value="ingresar">
                    <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                </button>
            </form>
            
            
            <div class="credential-hint">
                <h6><i class="fas fa-key me-2"></i>Credenciales de Prueba</h6>
                <div class="small">
                    <strong>Admin:</strong> <code>admin</code> / <code>madmarket2025</code><br>
                    <strong>Almacén:</strong> <code>maria.alvarez</code> / <code>madmarket2025</code><br>
                    <strong>Vendedor:</strong> <code>carlos.rodriguez</code> / <code>madmarket2025</code>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event){
            const username = document.getElementById('usuario').value.trim();
            const password = document.getElementById('contraseña').value.trim();

            if(username===""){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El usuario no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(password===""){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "La contraseña no puede estar vacía",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>