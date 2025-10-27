<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Sistema de Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/administrador-controlpersonal/datos.css">
    <link rel="stylesheet" href="css/administrador-estilo.css">
</head>
<body>
    <?php
    $conexion=pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
    if(!$conexion){
        echo "Un error de conexión ocurrió.";
    }

    $result1=pg_query($conexion,"SELECT COUNT(cod_empleado) AS cantidad_empleado FROM empleado");
    if(!$result1){
        echo "Error al contar los empleados.";
    }

    $cantusu=pg_query($conexion,"SELECT COUNT(cod_usuario) AS cantidad_usuario FROM usuario");
    if(!$cantusu){
        echo "Error al contar los usuario.";
    }

    $result2=pg_query($conexion,"SELECT e.cod_empleado AS codigo_empleado,e.nombre AS empleado_nombre,e.apellido AS apellido,e.dni AS dni,e.fecha_nacimiento AS fec_nac,
                                 e.telefono AS telefono,r.nombre AS rol_nombre FROM empleado e 
                                 JOIN rol r ON e.cod_rol=r.cod_rol
                                 ORDER BY codigo_empleado");
    if(!$result2){
        echo "Error al seleccionar los empleados.";
    }

    $result3=pg_query($conexion,"SELECT nombre FROM rol");
    if(!$result3){
        echo "Error al seleccionar el rol.";
    }

    $result4=pg_query($conexion,"SELECT u.cod_usuario AS codigo_usuario,u.cod_empleado AS codigo_empleado,u.usuario AS usuario,u.clave AS clave,eu.nombre AS 
                      estadousuario_nombre FROM usuario u
                      JOIN estadousuario eu ON u.cod_estadousuario=eu.cod_estadousuario");
    if(!$result4){
        echo "Error al seleccionar el usuario.";
    }

    if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='insertar')){
        if($_POST['formulario'] === 'empleado'){
            $codemp=$_POST['codigoEmpleado'] ?? '';
            $nomeemp=$_POST['nombreEmpleado'] ?? '';
            $apelemp=$_POST['apellidoEmpleado'] ?? '';
            $fecnacemp=$_POST['fechaNacEmpleado'] ?? '';
            $dniemp=$_POST['dniEmpleado'] ?? '';
            $telemp=$_POST['telefonoEmpleado'] ?? '';
            $rolemp=$_POST['rolEmpleado'] ?? '';
            $codigorol=$_POST['codigoRolEmpleado'] ?? '';

            $dniemp = str_replace(' ', '', $dniemp);
            $telemp = str_replace(' ', '', $telemp);

            $vericodemp = pg_query_params($conexion, "SELECT COUNT(cod_empleado) FILTER(WHERE cod_empleado=$1) AS cantidad_codigo_empleado,COUNT(dni) FILTER (WHERE dni=$2) AS cantidad_dni,COUNT(telefono) FILTER (WHERE telefono=$3) AS cantidad_telefono FROM empleado", array($codemp,$dniemp,$telemp));
            if (!$vericodemp) {
                echo "Error al verificar el código del empleado: " . pg_last_error($conexion);
                exit;
            }  
            
            $veri=pg_fetch_assoc($vericodemp);
            if($veri){
                $veric=(int)$veri['cantidad_codigo_empleado'];
                $verid=(int)$veri['cantidad_dni'];
                $verit=(int)$veri['cantidad_telefono'];
            } else {
                $veric=$verid=$verit=0;
            }
            
            if ($veric === 0) {
                if($verid===0){
                    if($verit===0){
                        if($rolemp==='Administrador'){
                            $codigorol='rol1';
                        } else if($rolemp==='Encargado'){
                            $codigorol='rol2';
                        } else if($rolemp==='Vendedor'){
                            $codigorol='rol3';
                        }

                        $sql2="INSERT INTO empleado(cod_empleado,nombre,apellido,fecha_nacimiento,dni,telefono,cod_rol) VALUES ($1,$2,$3,$4,$5,$6,$7)";
                        $result5=pg_query_params($conexion,$sql2,array($codemp,$nomeemp,$apelemp,$fecnacemp,$dniemp,$telemp,$codigorol));
                        if(!$result5){
                            echo "Error al insertar el empleado.";
                            exit;
                        }   

                        echo "
                            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function(){
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Empleado registrado',
                                            text: 'Se registró el empleado correctamente',
                                            width: '350px'
                                        }).then(() => {
                                            window.location.href = '" . $_SERVER['PHP_SELF'] . "';
                                        });
                                });
                            </script>
                        ";

                        exit;
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
                                    icon: 'warning',
                                    title: 'Oops...',
                                    text: 'No pueden haber dos dni iguales. Intente con otro.',
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
                            icon: 'warning',
                            title: 'Oops...',
                            text: 'El código de empleado ya existe. Intente con otro.',
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
    }

    if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='actualizar')){
        $codactemp=$_POST['codigoActualizarEmpleado'] ?? '';
        $nomactemp=$_POST['nombreActualizarEmpleado'] ?? '';
        $apeactemp=$_POST['apellidoActualizarEmpleado'] ?? '';
        $dniactemp=$_POST['dniActualizarEmpleado'] ?? '';
        $telactemp=$_POST['telefonoActualizarEmpleado'] ?? '';
        $rolactemp=$_POST['direccionActualizarEmpleado'] ?? '';
        $fecnacactemp=$_POST['fechaNacActualizarEmpleado'] ?? '';

        $dniactemp = str_replace(' ', '', $dniactemp);
        $telactemp = str_replace(' ', '', $telactemp);

        $sql3="UPDATE proveedor SET nombre=$2, telefono=$3, direccion=$4 WHERE cod_proveedor=$1";

        $result6=pg_query_params($conexion,$sql3,array($codactprove,$nomactprove,$telactprove,$diractprove));

        if(!$result6){
            echo "Error al seleccionar proveedor.";
            exit;
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit;
    }

    if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='eliminar')){
        $cod=$_POST['codigo_empleado'];

        $eliemp=pg_query_params($conexion,"DELETE FROM usuario WHERE cod_empleado=$1",array($cod));
        if(!$eliemp){
            echo "Error al eliminar usuario para empleado.";
        }

        $sql5="DELETE FROM empleado WHERE cod_empleado=$1";
        $result8=pg_query_params($conexion,$sql5,array($cod));

        if(!$result8){
            echo "Error al borrar empleado.";
            exit;
        } else if($result8){
            echo "Empleado eliminado.";
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit;
    }

    if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='insertar')){
        if($_POST['formulario'] === 'usuario'){
            $codusu=$_POST['codigoUsuario'] ?? '';
            $codiemp=$_POST['codigoEmpleadoUsuario'] ?? '';
            $clave=$_POST['clave'] ?? '';
            $estusu=$_POST['estadoUsuario'] ?? '';
            $codestusu='usu1';

            $vericodiempusu=pg_query_params($conexion, "SELECT COUNT(cod_empleado) AS cantidad_codigo_empleado_usuario FROM usuario WHERE cod_empleado=$1",array($codiemp));
            if(!$vericodiempusu){
                echo "Error al verificar el código del empleado: ".pg_last_error($conexion);
            }

            $vercodiempusu=pg_fetch_assoc($vericodiempusu);
            $vercodiempusu=(int)$vercodiempusu['cantidad_codigo_empleado_usuario'];

            $vericodusu=pg_query_params($conexion, "SELECT COUNT(cod_usuario) AS cantidad_codigo_usuario FROM usuario WHERE cod_usuario=$1",array($codusu));
            if(!$vericodusu){
                echo "Error al verificar el código del usuario: ".pg_last_error($conexion);
            }

            $vercodusu=pg_fetch_assoc($vericodusu);;
            $vercodusu=(int)$vercodusu['cantidad_codigo_usuario'];

            if($vercodiempusu===0){
                if($vercodusu===0){
                    if($codestusu==='usu1'){
                        $estusu='Activo';
                    }

                    $consulta=pg_query_params($conexion,"SELECT nombre,apellido FROM empleado WHERE cod_empleado=$1", array($codiemp));
                    if(!$consulta){
                        echo "Error al seleccionar el empleado para el usuario.";
                    }

                    $con=pg_fetch_assoc($consulta);
                    if(!$con){
                        echo "No se encontró ningún empleado.";
                    }

                    $nomusu=trim($con['nombre'] ?? '');
                    $apeusu=trim($con['apellido'] ?? '');

                    $nomusu=mb_strtolower($nomusu,'UTF-8');
                    $apeusu=mb_strtolower($apeusu,'UTF-8');

                    ob_start();
                    echo $nomusu.".".$apeusu;
                    $usuario=ob_get_clean();

                    $sql6="INSERT INTO usuario(cod_usuario,cod_empleado,usuario,clave,cod_estadousuario) VALUES ($1,$2,$3,$4,$5)";
                    $result9=pg_query_params($conexion,$sql6,array($codusu,$codiemp,$usuario,$clave,$codestusu));
                    if(!$result9){
                        echo "Error al insertar el usuario.";
                        exit;
                    }  

                    echo "
                        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                        <script>
                            document.addEventListener('DOMContentLoaded', function(){
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Usuario registrado',
                                    text: 'Se registró el usuario correctamente',
                                    width: '350px'
                                }).then(() => {
                                    window.location.href = '" . $_SERVER['PHP_SELF'] . "';
                                });
                            });
                        </script>
                    ";
                    
                    exit;
                } else {
                    echo "
                        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                        <script>
                            document.addEventListener('DOMContentLoaded', function(){
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Oops...',
                                    text: 'El código del usuario ya existe. Intente con otro.',
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
                            icon: 'warning',
                            title: 'Oops...',
                            text: 'El empleado ya tiene un usuario. Intente con otro.',
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
    }

    if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='actualizar')){
        if($_POST['formularioActualizarUsuario'] === 'usuario'){
            
            $codactusu=$_POST['codigoActualizarUsuario'] ?? '';
            $codempactusu=$_POST['codigoEmpleadoActualizarUsuario'] ?? '';
            $actusu=$_POST['usuarioActualizarUsuario'] ?? '';
            $claactusu=$_POST['claveActualizarUsuario'] ?? '';
            $estactusu=$_POST['estadoActualizarUsuario'] ?? '';

            $claactusu = str_replace(' ', '', $claactusu);

            

            $sql10="UPDATE usuario SET cod_empleado=$2, usuario=$3, clave=$4 WHERE cod_usuario=$1";

            $result10=pg_query_params($conexion,$sql10,array($codactusu,$codempactusu,$actusu,$claactusu));

            if(!$result10){
                echo "Error al seleccionar usuario.";
                exit;
            }

            
            exit;
        }
    }
    ?>
    <div class="grid">
        <main class="principal">
            <button class="boton-menu" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>

    
            <div class="barra-lateral" id="barra-lateral">
                <div class="logo">
                    <h4><i class="fas fa-store"></i> MAD MARKET</h4>
                    <small id="userRole">Administrador</small>
                </div>

        
                <div class="turno-info">
                    <div class="fw-bold">María Alvarez</div>
                    <small>Turno: 08:00 - 16:00</small><br>
                    <small id="tiempoActivoSidebar">0h 0m activo</small>
                </div>

                <div class="nav flex-column mt-3">
                    <a href="dashboard.php" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="kardexprincipal.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Kardex Principal</ul></a>
                    <a href="proveedores.php" class="nav-link"><ul><i class="fas fa-truck"></i>Proveedores</ul></a>
                    <a href="controlpersonal.php" class="nav-link"><ul><i class="fas fa-truck-loading"></i>Control de Personal</ul></a>
                    <a href="registroventas.php" class="nav-link"><ul><i class="fas fa-arrow-right"></i>Registro de Ventas</ul></a>
                    <a href="configuracion.php" class="nav-link"><ul><i class="fas fa-bell"></i>Configuración</ul></a>
                    <a href="#" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
                </div>
            </div>
        </main>

        <div class="secundario">
            <div class="header">
                <div class="caja-busqueda">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Buscar productos, ventas..." id="globalSearch">
                </div>
                
                <div class="usuario-info">
                    <div class="usuario-avatar" id="usuarioAvatar">AP</div>
                    <div>
                        <div class="fw-bold fs-5" id="userName">Admin Principal</div>
                        <small class="text-muted" id="userPosition">Administrador - Turno Activo</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger ms-3" onclick="cerrarTurno()">
                        <i class="fas fa-sign-out-alt me-1"></i>Cerrar Turno
                    </button>
                </div>
            </div>
            <br>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0"><i class="fas fa-users me-2"></i>Control de Personal</h1>
                <div>
                    <button class="btn btn-mad" data-bs-toggle="modal" data-bs-target="#modalEmpleado">
                        <i class="fas fa-plus me-2"></i>Nuevo Empleado
                    </button>
                    <div class="text-muted d-inline-block ms-3">Mes: Diciembre 2024</div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <?php
                            while($row1=pg_fetch_assoc($result1)){
                                echo "
                                <h3 class='text-primary mb-1'>$row1[cantidad_empleado]</h3>
                                ";

                                if($row1['cantidad_empleado']===1){
                                    echo "<div><small class='text-muted'>empleado</small></div>";
                                } else {
                                    echo "<div><small class='text-muted'>empleados</small></div>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h3 class="text-success mb-1">156</h3>
                            <small class="text-muted">Horas Trabajadas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h3 class="text-warning mb-1">12.5</h3>
                            <small class="text-muted">Horas Extras</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <?php
                            while($rowusu=pg_fetch_assoc($cantusu)){
                                echo "
                                <h3 class='text-info mb-1'>$rowusu[cantidad_usuario]</h3>
                                ";

                                if($rowusu['cantidad_usuario']===1){
                                    echo "<div><small class='text-muted'>usuario</small></div>";
                                } else {
                                    echo "<div><small class='text-muted'>usuarios</small></div>";
                                }
                            }
                            ?>
                            
                            
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaEmpleado" class="table table-hover">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>Código</th>
                                    <th>Empleado</th>
                                    <th>DNI</th>
                                    <th>Rol</th>
                                    <th>Teléfono</th>
                                    <th>Fecha de Nacimiento</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                while($row2=pg_fetch_assoc($result2)){
                                    echo "
                                    <tr>
                                        <td>$row2[codigo_empleado]</td>
                                        <td>$row2[empleado_nombre]";
                                    
                                    echo " $row2[apellido]</td>";
                                        

                                    echo "    
                                        <td>$row2[dni]</td>
                                        <td>$row2[rol_nombre]</td>
                                        <td>$row2[telefono]</td>
                                        <td>$row2[fec_nac]</td>
                                        <td>$row2[rol_nombre]</td>
                                        <td>
                                            <div class='btn-group btn-group-sm gap-1'>
                                                <button class='btn btn-outline-primary' data-bs-toggle='modal' data-bs-target='#modalActualizarEmpleado' title='Actualizar'
                                                    onclick=\"actualizarEmpleado('{$row2['codigo_empleado']}','{$row2['empleado_nombre']}','{$row2['apellido']}','{$row2['dni']}','{$row2['telefono']}','{$row2['fec_nac']}','{$row2['rol_nombre']}')\">
                                                    <i class='fas fa-edit'></i>
                                                </button>

                                                <form method='POST'>
                                                    <input type='hidden' name='codigo_empleado' value='{$row2['codigo_empleado']}'>
                                                    <button class='btn btn-outline-danger' title='Eliminar' name='accion' value='eliminar'>
                                                        <i class='fas fa-trash'></i>
                                                    </button>
                                                </form>

                                                <div>
                                                    <button class='btn btn-outline-success' data-bs-toggle='modal' data-bs-target='#modalUsuario' title='Crear usuario'
                                                        onclick=\"cargarUsuario('{$row2['codigo_empleado']}')\">
                                                        <i class='fas fa-user-plus'></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    ";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Código de Usuario</th>
                                    <th>Código de Empleado</th>
                                    <th>Usuario</th>
                                    <th>Clave</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                while($row4=pg_fetch_assoc($result4)){
                                    echo "
                                    <tr>
                                        <td>$row4[codigo_usuario]</td>
                                        <td>$row4[codigo_empleado]</td>
                                        <td>$row4[usuario]
                                        <td>$row4[clave]</td>
                                        <td>$row4[estadousuario_nombre]</td>
                                        <td>
                                            <div class='btn-group btn-group-sm gap-1'>
                                                <button class='btn btn-outline-primary' data-bs-toggle='modal' data-bs-target='#modalActualizarUsuario' title='Actualizar' 
                                                    onclick=\"actualizarUsuario('{$row4['codigo_usuario']}','{$row4['codigo_empleado']}','{$row4['usuario']}','{$row4['clave']}','{$row4['estadousuario_nombre']}')\">
                                                    <i class='fas fa-edit'></i>
                                                </button>

                                                <form method='POST'>
                                                    <input type='hidden' name='cod_proveedor' value='{$row4['codigo_usuario']}'>
                                                    <button class='btn btn-outline-danger' title='Eliminar' name='accion' value='eliminar'>
                                                        <i class='fas fa-trash'></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    ";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalEmpleado" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Nuevo Empleado</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="formularioEmpleado" method="POST">
                                <input type="hidden" name="formulario" value="empleado">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label" for="codigoEmpleado">Código</label>
                                        <input type="text" class="form-control" id="codigoEmpleado" name="codigoEmpleado" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="nombreEmpleado">Nombre</label>
                                        <input type="text" class="form-control" id="nombreEmpleado" name="nombreEmpleado" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="apellidoEmpleado">Apellido</label>
                                        <input type="text" class="form-control" id="apellidoEmpleado" name="apellidoEmpleado" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="dniEmpleado">DNI</label>
                                        <input type="text" class="form-control" id="dniEmpleado" name="dniEmpleado" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="telefonoEmpleado">Teléfono</label>
                                        <input type="text" class="form-control" id="telefonoEmpleado" name="telefonoEmpleado" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="fechaNacEmpleado">Fecha Nacimiento</label>
                                    <input type="date" class="form-control" id="fechaNacEmpleado" name="fechaNacEmpleado" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="rolEmpleado">Rol</label>
                                    <select class="form-select" id="rolEmpleado" name="rolEmpleado" required>
                                        <option value="">Seleccionar rol...</option>
                                        <?php
                                        while($row3=pg_fetch_assoc($result3)){
                                            echo "
                                            <option value='{$row3['nombre']}'>$row3[nombre]</option>
                                            ";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <input class="btn btn-mad" type="submit" name="accion" value="insertar">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalActualizarEmpleado" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Nuevo Empleado</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="formularioActualizarEmpleado" method="POST">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label" for="codigoActualizarEmpleado">Código</label>
                                        <input type="text" class="form-control" id="codigoActualizarEmpleado" name="codigoActualizarEmpleado" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="nombreActualizarEmpleado">Nombre</label>
                                        <input type="text" class="form-control" id="nombreActualizarEmpleado" name="nombreActualizarEmpleado" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="apellidoActualizarEmpleado">Apellido</label>
                                        <input type="text" class="form-control" id="apellidoActualizarEmpleado" name="apellidoActualizarEmpleado" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="dniActualizarEmpleado">DNI</label>
                                        <input type="text" class="form-control" id="dniActualizarEmpleado" name="dniActualizarEmpleado" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="telefonoActualizarEmpleado">Teléfono</label>
                                        <input type="text" class="form-control" id="telefonoActualizarEmpleado" name="telefonoActualizarEmpleado" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="fechaNacActualizarEmpleado">Fecha Nacimiento</label>
                                    <input type="date" class="form-control" id="fechaNacActualizarEmpleado" name="fechaNacActualizarEmpleado" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="rolActualizarEmpleado">Rol</label>
                                    <select class="form-select" id="rolActualizarEmpleado" name="rolActualizarEmpleado" required>
                                        <option value="">Seleccionar rol...</option>
                                        <?php
                                        while($row3=pg_fetch_assoc($result3)){
                                            echo "
                                            <option value='{$row3['nombre']}'>$row3[nombre]</option>
                                            ";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <input class="btn btn-mad" type="submit" name="accion" value="actualizar">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalUsuario" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Nuevo Usuario</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <form id="formularioUsuario" method="POST">
                                <input type="hidden" name="formulario" value="usuario">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label" for="codigoUsuario">Código</label>
                                        <input type="text" class="form-control" id="codigoUsuario" name="codigoUsuario" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label" for="codigoEmpleadoUsuario">Código de Empleado</label>
                                        <input type="text" class="form-control" id="codigoEmpleadoUsuario" name="codigoEmpleadoUsuario" readonly>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label" for="clave">Clave</label>
                                        <input type="text" class="form-control" id="clave" name="clave" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <input class="btn btn-mad" type="submit" name="accion" value="insertar">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalActualizarUsuario" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Nuevo Empleado</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="formularioActualizarUsuario" method="POST" name="accion" value="actualizar">
                                <input type="hidden" name="formularioActualizarUsuario" value="usuario">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label" for="codigoActualizarUsuario">Código</label>
                                        <input type="text" class="form-control" id="codigoActualizarUsuario" name="codigoActualizarUsuario" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="codigoEmpleadoActualizarUsuario">Código del Empleado</label>
                                        <input type="text" class="form-control" id="codigoEmpleadoActualizarUsuario" name="codigoEmpleadoActualizarUsuario" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="usuarioActualizarUsuario">Usuario</label>
                                        <input type="text" class="form-control" id="usuarioActualizarUsuario" name="usuarioActualizarUsuario" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="claveActualizarUsuario">Clave</label>
                                        <input type="text" class="form-control" id="claveActualizarUsuario" name="claveActualizarUsuario" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="estadoActualizarUsuario">Estado</label>
                                        <input type="text" class="form-control" id="estadoActualizarUsuario" name="estadoActualizarUsuario" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <input class="btn btn-mad" type="submit" name="accion" value="actualizar">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById("formularioEmpleado").addEventListener("submit", function(event){
            const codemp=document.getElementById("codigoEmpleado").value.trim();
            const nomemp=document.getElementById("nombreEmpleado").value.trim();
            const apeemp=document.getElementById("apellidoEmpleado").value.trim();
            const dniemple=document.getElementById("dniEmpleado").value.trim();
            const telemple=document.getElementById("telefonoEmpleado").value.trim();
            const fecnacemp=document.getElementById("fechaNacEmpleado").value.trim();

            const dniemp=dniemple.replace(/\s+/g, '');
            const telemp=telemple.replace(/\s+/g, '');

            if(codemp===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El código no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            const regexnomape=/^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

            if(nomemp === " "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El nombre no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(!regexnomape.test(nomemp)){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El nombre debe empezar con mayúscula y contener solo letras",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(apeemp === " "){
                wal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El apellido no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(!regexnomape.test(apeemp)){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El apellido debe empezar con mayúscula y contener solo letras",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(dniemp === " "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El dni no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(dniemp.length!==8 || isNaN(dniemp)){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El dni debe tener 8 números",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(telemp === " "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El teléfono no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(telemp[0]!=='9' || telemp.length!==9 || isNaN(telemp)){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El teléfono debe empezar con 9 y tener 9 números",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            const hoy=new Date();
            const fecha=new Date(fecnacemp);

            hoy.setHours(0,0,0,0);
            fecha.setHours(0,0,0,0);

            if(fecha>hoy){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "La fecha no puede ser superior a la de hoy",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }
        });

        function actualizarEmpleado(codigoActEmp,nombreActEmp,apellidoActEmp,dniActEmp,telefonoActEmp,fecNacActEmp,rolActEmp){
            document.getElementById("codigoActualizarEmpleado").value=codigoActEmp;
            document.getElementById("nombreActualizarEmpleado").value=nombreActEmp;
            document.getElementById("apellidoActualizarEmpleado").value=apellidoActEmp;
            document.getElementById("dniActualizarEmpleado").value=dniActEmp;
            document.getElementById("telefonoActualizarEmpleado").value=telefonoActEmp;
            document.getElementByID("fechaNacActualizarEmpleado").value=fecNacActEmp;
            document.getElementByID("rolActualizarEmpleado").value=rolActEmp;
        }
        
        document.getElementById("formularioActualizarEmpleado").addEventListener("submit", function(event){
            const codactemp=document.getElementById("codigoActualizarEmpleado").value.trim();
            const nomactemp=document.getElementById("nombreActualizarEmpleado").value.trim();
            const apeactemp=document.getElementById("apellidoActualizarEmpleado").value.trim();
            const dniactemple=document.getElementById("dniActualizarEmpleado").value.trim();
            const telactemple=document.getElementById("telefonoActualizarEmpleado").value.trim();
            const fecactnacemp=document.getElementById("fechaNacActualizarEmpleado").value.trim();
            const rolactemp=document.getElementById("rolActualizarEmpleado").value.trim();

            const dniactemp=dniemple.replace(/\s+/g, '');
            const telactemp=telemple.replace(/\s+/g, '');

            if(codactemp===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El código no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            const regexnomape=/^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

            if(nomactemp === " "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El nombre no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(!regexnomape.test(nomactemp)){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El nombre debe empezar con mayúscula y contener solo letras",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(apeactemp === " "){
                wal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El apellido no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(!regexnomape.test(apeactemp)){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El apellido debe empezar con mayúscula y contener solo letras",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(dniactemp === " "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El dni no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(dniactemp.length!==8 || isNaN(dniactemp)){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El dni debe tener 8 números",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(telactemp === " "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El teléfono no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(telactemp[0]!=='9' || telactemp.length!==9 || isNaN(telactemp)){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El teléfono debe empezar con 9 y tener 9 números",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            const hoy=new Date();
            const fecha=new Date(fecactnacemp);

            hoy.setHours(0,0,0,0);
            fecha.setHours(0,0,0,0);

            if(fecha>hoy){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "La fecha no puede ser superior a la de hoy",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }
        });

        document.getElementById("formularioUsuario").addEventListener("submit", function(event){
            const codusu=document.getElementById("codigoUsuario").value.trim();
            const claveusu=document.getElementById("clave").value.trim();

            if(codusu===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El código no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(codusu>10){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El código no puede ser mayor de 10 dígitos",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(claveusu===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "La clave no puede estar vacía",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(claveusu>30){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "La clave no puede ser mayor de 30 dígitos",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }
        });

        function actualizarUsuario(codigoActUsu,codigoEmpActUsu,usuarioActUsu,claveActUsu,estadoActUsu){
            document.getElementById("codigoActualizarUsuario").value=codigoActUsu;
            document.getElementById("codigoEmpleadoActualizarUsuario").value=codigoEmpActUsu;
            document.getElementById("usuarioActualizarUsuario").value=usuarioActUsu;
            document.getElementById("claveActualizarUsuario").value=claveActUsu;
            document.getElementById("estadoActualizarUsuario").value=estadoActUsu;
        }

        document.getElementById("formularioActualizarUsuario").addEventListener("submit", function(event){
            const codactusu=document.getElementById("codigoActualizarUsuario").value.trim();
            const codempactusu=document.getElementById("codigoEmpleadoActualizarUsuario").value.trim();
            const usuactusu=document.getElementById("usuarioActualizarUsuario").value.trim();
            const claveactusu=document.getElementById("claveActualizarUsuario").value.trim();
            const estactusu=document.getElementById("estadoActualizarUsuario").value.trim();

            if(codactusu===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El código no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(codactusu.length>10){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El código no puede ser mayor de 10 dígitos",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(codempactusu===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El código del empleado no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(usuactusu===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El usuario no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(claveactusu===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "La clave no puede estar vacía",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(claveactusu.length>30){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "La clave no puede ser mayor de 30 dígitos",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(estactusu===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El estado no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>