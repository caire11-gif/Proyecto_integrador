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
    <link rel="stylesheet" href="css/administrador-boton/boton.css">
</head>
<body>
    <?php
    $conexion = pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");
    if (!$conexion) {
        echo "Un error de conexión ocurrió.";
    }

    session_start();
    $usuarioadmin=$_SESSION['nombreusuarioadmin'];
    $apellidoadmin=$_SESSION['apellidousuarioadmin'];

    $inicialNombre = substr($usuarioadmin, 0, 1);
    $inicialApellido=substr($apellidoadmin,0,1);

    if (!isset($_SESSION['nombreusuarioadmin'])) {
        header("Location: ../login.php");
        exit;
    }

    // FUNCIÓN PARA GENERAR CÓDIGO AUTOMÁTICO
    function generarCodigoUsuario($conexion, $prefijo) {
        $result = pg_query($conexion, "SELECT COUNT(*) as total FROM usuario WHERE cod_usuario LIKE '$prefijo%'");
        $row = pg_fetch_assoc($result);
        $numero = $row['total'] + 1;
        return $prefijo . str_pad($numero, 3, '0', STR_PAD_LEFT);
    }

    // FUNCIÓN PARA GENERAR USUARIO AUTOMÁTICO
    function generarUsuario($nombre, $apellido) {
        $nombre = mb_strtolower(trim($nombre), 'UTF-8');
        $apellido = mb_strtolower(trim($apellido), 'UTF-8');

        // Tomar primera letra del nombre + apellido completo
        $inicialNombre = substr($nombre, 0, 1);
        $usuario = $inicialNombre . '.' . $apellido;

        // Eliminar tildes y caracteres especiales
        $usuario = iconv('UTF-8', 'ASCII//TRANSLIT', $usuario);
        $usuario = preg_replace('/[^a-zA-Z0-9.]/', '', $usuario);

        return $usuario;
    }

    // CONSULTAS PARA DATOS
    $result1 = pg_query($conexion, "SELECT COUNT(cod_empleado) AS cantidad_empleado FROM empleado");
    $cantusu = pg_query($conexion, "SELECT COUNT(cod_usuario) AS cantidad_usuario FROM usuario");
    $result2 = pg_query($conexion, "SELECT e.cod_empleado, e.nombre, e.apellido, e.dni, e.fecha_nacimiento, e.telefono, r.nombre as rol_nombre FROM empleado e JOIN rol r ON e.cod_rol = r.cod_rol ORDER BY e.cod_empleado");
    $result3 = pg_query($conexion, "SELECT nombre FROM rol");
    $selectestusu = pg_query($conexion, "SELECT nombre FROM estadousuario");
    $result4 = pg_query($conexion, "SELECT u.cod_usuario, u.cod_empleado, u.usuario, u.contraseña, eu.nombre as estadousuario_nombre FROM usuario u JOIN estadousuario eu ON u.cod_estadousuario = eu.cod_estadousuario");

    // LÓGICA PARA INSERTAR EMPLEADO Y CREAR USUARIO AUTOMÁTICO
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] === 'insertar')) {
        if ($_POST['formulario'] === 'empleado') {
            $codemp = $_POST['codigoEmpleado'] ?? '';
            $nomeemp = $_POST['nombreEmpleado'] ?? '';
            $apelemp = $_POST['apellidoEmpleado'] ?? '';
            $fecnacemp = $_POST['fechaNacEmpleado'] ?? '';
            $dniemp = $_POST['dniEmpleado'] ?? '';
            $telemp = $_POST['telefonoEmpleado'] ?? '';
            $rolemp = $_POST['rolEmpleado'] ?? '';

            $dniemp = str_replace(' ', '', $dniemp);
            $telemp = str_replace(' ', '', $telemp);

            // Verificar si ya existe el empleado
            $vericodemp = pg_query_params(
                $conexion,
                "SELECT COUNT(cod_empleado) FILTER(WHERE cod_empleado=$1) AS cantidad_codigo_empleado,
                        COUNT(dni) FILTER (WHERE dni=$2) AS cantidad_dni,
                        COUNT(telefono) FILTER (WHERE telefono=$3) AS cantidad_telefono 
                 FROM empleado",
                array($codemp, $dniemp, $telemp)
            );

            $veri = pg_fetch_assoc($vericodemp);
            $veric = (int)$veri['cantidad_codigo_empleado'];
            $verid = (int)$veri['cantidad_dni'];
            $verit = (int)$veri['cantidad_telefono'];

            if ($veric === 0 && $verid === 0 && $verit === 0) {
                // Asignar código de rol
                if ($rolemp === 'Administrador') {
                    $codigorol = 'rol1';
                } else if ($rolemp === 'Encargado') {
                    $codigorol = 'rol2';
                } else if ($rolemp === 'Vendedor') {
                    $codigorol = 'rol3';
                }

                // Insertar empleado
                $sql2 = "INSERT INTO empleado(cod_empleado, nombre, apellido, fecha_nacimiento, dni, telefono, cod_rol) 
                         VALUES ($1, $2, $3, $4, $5, $6, $7)";
                $result5 = pg_query_params($conexion, $sql2, array($codemp, $nomeemp, $apelemp, $fecnacemp, $dniemp, $telemp, $codigorol));

                if ($result5) {
                    // 🎯 CREAR USUARIO AUTOMÁTICAMENTE
                    $codigoUsuario = generarCodigoUsuario($conexion, 'user');
                    $usuarioGenerado = generarUsuario($nomeemp, $apelemp);
                    $contraseñaInicial = $dniemp; // DNI como contraseña inicial
                    $codEstadoUsuario = 'est001'; // Activo por defecto

                    $sqlUsuario = "INSERT INTO usuario(cod_usuario, cod_empleado, usuario, contraseña, cod_estadousuario) 
                                   VALUES ($1, $2, $3, $4, $5)";
                    $resultUsuario = pg_query_params(
                        $conexion,
                        $sqlUsuario,
                        array($codigoUsuario, $codemp, $usuarioGenerado, $contraseñaInicial, $codEstadoUsuario)
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
                                        window.location.href = '" . $_SERVER['PHP_SELF'] . "';
                                    });
                                });
                            </script>
                        ";
                    } else {
                        echo "Error al crear usuario automático: " . pg_last_error($conexion);
                    }
                } else {
                    echo "Error al insertar el empleado: " . pg_last_error($conexion);
                }
                exit;
            } else {
                $mensajeError = "";
                if ($veric > 0) $mensajeError = "El código de empleado ya existe.";
                else if ($verid > 0) $mensajeError = "El DNI ya está registrado.";
                else if ($verit > 0) $mensajeError = "El teléfono ya está registrado.";

                echo "
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function(){
                            Swal.fire({
                                icon: 'warning',
                                title: 'Oops...',
                                text: '$mensajeError',
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

    // LÓGICA PARA ELIMINAR EMPLEADO
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] === 'eliminar')) {
        $cod = $_POST['codigo_empleado'] ?? '';

        // Primero eliminar el usuario asociado
        $eliemp = pg_query_params($conexion, "DELETE FROM usuario WHERE cod_empleado = $1", array($cod));
        
        // Luego eliminar el empleado
        $sql5 = "DELETE FROM empleado WHERE cod_empleado = $1";
        $result8 = pg_query_params($conexion, $sql5, array($cod));

        if ($result8) {
            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        Swal.fire({
                            icon: 'success',
                            title: 'Empleado eliminado',
                            text: 'El empleado y su usuario han sido eliminados correctamente',
                            width: '350px'
                        }).then(() => {
                            window.location.href = '" . $_SERVER['PHP_SELF'] . "';
                        });
                    });
                </script>
            ";
        } else {
            echo "Error al eliminar empleado.";
        }
        exit;
    }

    // LÓGICA PARA CAMBIO DE CONTRASEÑA (para el empleado)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] === 'cambiarContraseña')) {
        $codUsuario = $_POST['codigoUsuario'] ?? '';
        $contraseñaActual = $_POST['contraseñaActual'] ?? '';
        $nuevaContraseña = $_POST['nuevaContraseña'] ?? '';
        $confirmarContraseña = $_POST['confirmarContraseña'] ?? '';

        // Verificar contraseña actual
        $verificar = pg_query_params(
            $conexion,
            "SELECT contraseña FROM usuario WHERE cod_usuario = $1",
            array($codUsuario)
        );

        $usuario = pg_fetch_assoc($verificar);

        if ($usuario && $usuario['contraseña'] === $contraseñaActual) {
            if ($nuevaContraseña === $confirmarContraseña) {
                // Actualizar contraseña
                $actualizar = pg_query_params(
                    $conexion,
                    "UPDATE usuario SET contraseña = $1 WHERE cod_usuario = $2",
                    array($nuevaContraseña, $codUsuario)
                );

                if ($actualizar) {
                    echo "
                        <script>
                            Swal.fire({
                                icon: 'success',
                                title: 'Contraseña actualizada',
                                text: 'Tu contraseña ha sido cambiada exitosamente',
                                width: '350px'
                            });
                        </script>
                    ";
                }
            } else {
                echo "
                    <script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Las contraseñas nuevas no coinciden',
                            width: '350px'
                        });
                    </script>
                ";
            }
        } else {
            echo "
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'La contraseña actual es incorrecta',
                        width: '350px'
                    });
                </script>
            ";
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

                <div class="nav flex-column mt-3">
                    <a href="dashboard.php" class="nav-link">
                        <ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul>
                    </a>
                    <a href="kardexprincipal.php" class="nav-link">
                        <ul><i class="fas fa-boxes"></i>Kardex Principal</ul>
                    </a>
                    <a href="proveedores.php" class="nav-link">
                        <ul><i class="fas fa-truck"></i>Proveedores</ul>
                    </a>
                    <a href="controlpersonal.php" class="nav-link">
                        <ul><i class="fas fa-truck-loading"></i>Control de Personal</ul>
                    </a>
                    <a href="registroventas.php" class="nav-link">
                        <ul><i class="fas fa-arrow-right"></i>Registro de Ventas</ul>
                    </a>
                </div>
            </div>
        </main>

        <div class="secundario">
            <div class="header">
                <div class="usuario-info">
                    <div class="usuario-avatar" id="usuarioAvatar"><?php echo htmlspecialchars($inicialNombre.$inicialApellido)?></div>
                    <div>
                        <div class="fw-bold fs-5" id="userName"><?php echo htmlspecialchars($usuarioadmin." ".$apellidoadmin) ?></div>
                        <small class="text-muted" id="userPosition">Administrador</small>
                    </div>
                    <div class="dropdown-container">
                        <div class="dropdown">
                            <button class="dropdown-btn" id="dropdownBtn">
                                <span class="arrow" id="arrow">▲</span>
                            </button>
                            <ul class="dropdown-list" id="dropdownList">
                                <a href="../login.php" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
                            </ul>
                        </div>
                    </div>
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
                            while ($row1 = pg_fetch_assoc($result1)) {
                                echo "
                                <h3 class='text-primary mb-1'>$row1[cantidad_empleado]</h3>
                                ";

                                if ($row1['cantidad_empleado'] === 1) {
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
                            while ($rowusu = pg_fetch_assoc($cantusu)) {
                                echo "
                                <h3 class='text-info mb-1'>$rowusu[cantidad_usuario]</h3>
                                ";

                                if ($rowusu['cantidad_usuario'] === 1) {
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
                        <table id="tablaEmpleado" class="table table-hover text-center">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>Código</th>
                                    <th>Empleado</th>
                                    <th>DNI</th>
                                    <th>Rol</th>
                                    <th>Teléfono</th>
                                    <th>Fecha de Nacimiento</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                while ($row2 = pg_fetch_assoc($result2)) {
                                    echo "
                                    <tr>
                                        <td>$row2[cod_empleado]</td>
                                        <td>$row2[nombre] $row2[apellido]</td>
                                        <td>$row2[dni]</td>
                                        <td>$row2[rol_nombre]</td>
                                        <td>$row2[telefono]</td>
                                        <td>$row2[fecha_nacimiento]</td>
                                        <td>
                                            <div class='btn-group btn-group-sm gap-1'>
                                                <button class='btn btn-outline-primary' data-bs-toggle='modal' data-bs-target='#modalActualizarEmpleado' title='Actualizar'
                                                    onclick=\"actualizarEmpleado('{$row2['cod_empleado']}','{$row2['nombre']}','{$row2['apellido']}','{$row2['dni']}','{$row2['telefono']}','{$row2['fecha_nacimiento']}','{$row2['rol_nombre']}')\">
                                                    <i class='fas fa-edit'></i>
                                                </button>

                                                <form method='POST' onsubmit='return confirm(\"¿Estás seguro de eliminar este empleado?\")'>
                                                    <input type='hidden' name='codigo_empleado' value='{$row2['cod_empleado']}'>
                                                    <button class='btn btn-outline-danger' title='Eliminar' name='accion' value='eliminar'>
                                                        <i class='fas fa-trash'></i>
                                                    </button>
                                                </form>

                                                <button class='btn btn-outline-warning' title='Cambiar Contraseña' 
                                                    onclick=\"abrirCambioContraseña('{$row2['cod_empleado']}')\">
                                                    <i class='fas fa-key'></i>
                                                </button>
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

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Usuarios del Sistema</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaUsuario" class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Código de Usuario</th>
                                    <th>Código de Empleado</th>
                                    <th>Usuario</th>
                                    <th>Contraseña</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                while ($row4 = pg_fetch_assoc($result4)) {
                                    echo "
                                    <tr>
                                        <td>$row4[cod_usuario]</td>
                                        <td>$row4[cod_empleado]</td>
                                        <td>$row4[usuario]</td>
                                        <td>••••••••</td>
                                        <td>$row4[estadousuario_nombre]</td>
                                        <td>
                                            <div class='btn-group btn-group-sm gap-1'>
                                                <button class='btn btn-outline-warning' title='Cambiar Contraseña' 
                                                    onclick=\"abrirCambioContraseñaUsuario('{$row4['cod_usuario']}')\">
                                                    <i class='fas fa-key'></i>
                                                </button>
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

            <!-- MODAL PARA NUEVO EMPLEADO -->
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
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label" for="codigoEmpleado">Código del Empleado</label>
                                        <input type="text" class="form-control" id="codigoEmpleado" name="codigoEmpleado" placeholder="Ej: emp001" required>
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
                                        pg_result_seek($result3, 0); // Resetear el puntero del resultado
                                        while ($row3 = pg_fetch_assoc($result3)) {
                                            echo "<option value='{$row3['nombre']}'>$row3[nombre]</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-mad" name="accion" value="insertar">
                                        <i class="fas fa-save me-2"></i>Guardar Empleado
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL PARA CAMBIO DE CONTRASEÑA -->
            <div class="modal fade" id="modalCambiarContraseña" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-key me-2"></i>Cambiar Contraseña</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="formularioCambiarContraseña" method="POST">
                                <input type="hidden" name="accion" value="cambiarContraseña">
                                <input type="hidden" id="codigoUsuarioContraseña" name="codigoUsuario">
                                
                                <div class="mb-3">
                                    <label class="form-label" for="contraseñaActual">Contraseña Actual</label>
                                    <input type="password" class="form-control" id="contraseñaActual" name="contraseñaActual" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label" for="nuevaContraseña">Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="nuevaContraseña" name="nuevaContraseña" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label" for="confirmarContraseña">Confirmar Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="confirmarContraseña" name="confirmarContraseña" required>
                                </div>
                                
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-mad">Cambiar Contraseña</button>
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
        // Función para abrir modal de cambio de contraseña
        function abrirCambioContraseña(codEmpleado) {
            // Buscar el código de usuario asociado al empleado
            fetch('buscar_usuario.php?cod_empleado=' + codEmpleado)
                .then(response => response.json())
                .then(data => {
                    if (data.cod_usuario) {
                        document.getElementById('codigoUsuarioContraseña').value = data.cod_usuario;
                        var modal = new bootstrap.Modal(document.getElementById('modalCambiarContraseña'));
                        modal.show();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se encontró usuario para este empleado',
                            width: '350px'
                        });
                    }
                });
        }

        function abrirCambioContraseñaUsuario(codUsuario) {
            document.getElementById('codigoUsuarioContraseña').value = codUsuario;
            var modal = new bootstrap.Modal(document.getElementById('modalCambiarContraseña'));
            modal.show();
        }

        // Validación del formulario de cambio de contraseña
        document.getElementById('formularioCambiarContraseña').addEventListener('submit', function(event) {
            const nuevaContraseña = document.getElementById('nuevaContraseña').value;
            const confirmarContraseña = document.getElementById('confirmarContraseña').value;
            
            if (nuevaContraseña !== confirmarContraseña) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Las contraseñas no coinciden',
                    width: '350px'
                });
                event.preventDefault();
                return;
            }
            
            if (nuevaContraseña.length < 6) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Contraseña muy corta',
                    text: 'La contraseña debe tener al menos 6 caracteres',
                    width: '350px'
                });
                event.preventDefault();
                return;
            }
        });

        // Validación del formulario de empleado
        document.getElementById("formularioEmpleado").addEventListener("submit", function(event) {
            const codemp = document.getElementById("codigoEmpleado").value.trim();
            const nomemp = document.getElementById("nombreEmpleado").value.trim();
            const apeemp = document.getElementById("apellidoEmpleado").value.trim();
            const dniemple = document.getElementById("dniEmpleado").value.trim();
            const telemple = document.getElementById("telefonoEmpleado").value.trim();
            const fecnacemp = document.getElementById("fechaNacEmpleado").value.trim();

            const dniemp = dniemple.replace(/\s+/g, '');
            const telemp = telemple.replace(/\s+/g, '');

            if (codemp === "") {
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El código no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            const regexnomape = /^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

            if (nomemp === "") {
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El nombre no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if (!regexnomape.test(nomemp)) {
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El nombre debe empezar con mayúscula y contener solo letras",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if (apeemp === "") {
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El apellido no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if (!regexnomape.test(apeemp)) {
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El apellido debe empezar con mayúscula y contener solo letras",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if (dniemp === "") {
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El DNI no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if (dniemp.length !== 8 || isNaN(dniemp)) {
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El DNI debe tener 8 números",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if (telemp === "") {
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El teléfono no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if (telemp[0] !== '9' || telemp.length !== 9 || isNaN(telemp)) {
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El teléfono debe empezar con 9 y tener 9 números",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            const hoy = new Date();
            const fecha = new Date(fecnacemp);

            if (fecha > hoy) {
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

        // Función para actualizar empleado (placeholder)
        function actualizarEmpleado(codigoActEmp, nombreActEmp, apellidoActEmp, dniActEmp, telefonoActEmp, fecNacActEmp, rolActEmp) {
            // Aquí puedes implementar la lógica para actualizar empleado
            Swal.fire({
                icon: 'info',
                title: 'Función en desarrollo',
                text: 'La actualización de empleados estará disponible pronto',
                width: '350px'
            });
        }

    const dropdownBtn = document.getElementById("dropdownBtn");
    const dropdownList = document.getElementById("dropdownList");
    const arrow = document.getElementById("arrow");

    dropdownBtn.addEventListener("click", () => {
        const isVisible = dropdownList.style.display === "block";
        dropdownList.style.display = isVisible ? "none" : "block";
        arrow.style.transform = isVisible ? "rotate(0deg)" : "rotate(180deg)";
    });
                            
    // Cierra el menú si haces clic fuera
    document.addEventListener("click", (e) => {
        if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
            dropdownList.style.display = "none";
            arrow.style.transform = "rotate(0deg)";
        }
    });
    
    </script>
</body>
</html>