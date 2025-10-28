<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mad Market - Sistema de Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/administrador-estilo.css">
</head>
<body>
    <?php
        $conexion=pg_connect("host=localhost dbname=sistemainventario user=postgres password=root");

        if(!$conexion){
            echo "Un error de conexión ocurrió.";
            exit;
        }

        $result1=pg_query($conexion,"SELECT COUNT(cod_proveedor) AS cantidad_proveedor FROM proveedor");
        if(!$result1){
            echo "Error al contar los proveedores";
        }

        $result2=pg_query($conexion,"SELECT COUNT(cod_movimiento) AS cantidad_movimiento FROM movimiento m
                                     JOIN tipomovimiento tm ON m.cod_tipomovimiento=tm.cod_tipomovimiento
                                     WHERE tm.nombre='Entrada'");
        if(!$result2){
            echo "Error al contar los producto entrantes.". pg_last_error($conexion);
        }

        if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='insertar')){
            $codprove=$_POST['codigoProveedor'] ?? '';
            $nomprove=$_POST['nombreProveedor'] ?? '';
            $telprove=$_POST['telefonoProveedor'] ?? '';
            $dirprove=$_POST['direccionProveedor'] ?? '';

            $telprove = str_replace(' ', '', $telprove);

            $vericodprove=pg_query_params($conexion, "SELECT COUNT(cod_proveedor) FILTER(WHERE cod_proveedor=$1) AS cantidad_codigo_proveedor, COUNT(telefono) FILTER(WHERE telefono=$2) AS cantidad_telefono_proveedor from proveedor",array($codprove,$telprove));
            if(!$vericodprove){
                echo "Error al verificar el código y teléfono del proveedor";
                exit;
            }

            $veri=pg_fetch_assoc($vericodprove);
            if($veri){
                $veric=(int)$veri['cantidad_codigo_proveedor'];
                $verit=(int)$veri['cantidad_telefono_proveedor'];
            } else {
                $veric=$verit=0;
            }

            if($veric===0){
                if($verit===0){
                    $sql1="INSERT INTO proveedor(cod_proveedor,nombre,telefono,direccion) VALUES ($1,$2,$3,$4)";
                    $resul=pg_query_params($conexion,$sql1,array($codprove,$nomprove,$telprove,$dirprove));

                    if(!$resul){
                        echo "Un error de conexión ocurrió.";
                        exit;
                    }

                    echo "
                        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                            <script>
                                document.addEventListener('DOMContentLoaded', function(){
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Proveedor registrado',
                                        text: 'Se registró el proveedor correctamente',
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
                            text: 'El código del proveedor ya existe. Intente con otro.',
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

        $result5=pg_query($conexion, "SELECT cod_proveedor,nombre,telefono,direccion FROM proveedor");
        if(!$result5){
            echo "Error al insertar proveedor.";
            exit;
        }

        if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='actualizar')){
            $codactprove=$_POST['codigoActualizarProveedor'] ?? '';
            $nomactprove=$_POST['nombreActualizarProveedor'] ?? '';
            $telactprove=$_POST['telefonoActualizarProveedor'] ?? '';
            $diractprove=$_POST['direccionActualizarProveedor'] ?? '';

            $telactprove = str_replace(' ', '', $telactprove);

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
            $cod=$_POST['cod_proveedor'];

            $prodprove=pg_query_params($conexion,"SELECT cod_producto FROM producto WHERE cod_proveedor=$1",array($cod));

            while($conprodprove=pg_fetch_assoc($prodprove)){
                $codprod=trim($conprodprove['cod_producto']);

                pg_query_params($conexion,"DELETE FROM registroinventario WHERE cod_producto=$1",array($codprod));
                pg_query_params($conexion,"DELETE FROM detalleventa WHERE cod_producto=$1",array($codprod));
                pg_query_params($conexion,"DELETE FROM detallecompra WHERE cod_producto=$1",array($codprod));
                pg_query_params($conexion,"DELETE FROM lote WHERE cod_producto=$1",array($codprod));
                pg_query_params($conexion,"DELETE FROM movimiento WHERE cod_producto=$1",array($codprod));
                pg_query_params($conexion,"DELETE FROM notificacion WHERE cod_producto=$1",array($codprod));
                pg_query_params($conexion,"DELETE FROM historial producto WHERE cod_producto=$1",array($codprod));

                $borrarproducto=pg_query_params($conexion,"DELETE FROM producto WHERE cod_proveedor=$1",array($cod));
                if(!$borrarproducto){
                    echo "Error al borrar proveedor de producto.". pg_last_error($conexion);
                }
            }

            $sql2="DELETE FROM proveedor WHERE cod_proveedor=$1";
            $result7=pg_query_params($conexion,$sql2,array($cod));

            if(!$result7){
                echo "Error al borrar proveedor.". pg_last_error($conexion);
                exit;
            }

            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit;
        }

        pg_close($conexion)
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
                    <a href="dashboard.php" class="nav-link"><ul><i class="fas fa-tachometer-alt"></i>Dashboard</ul></a>
                    <a href="kardexprincipal.php" class="nav-link"><ul><i class="fas fa-boxes"></i>Kardex Principal</ul></a>
                    <a href="proveedores.php" class="nav-link"><ul><i class="fas fa-truck"></i>Proveedores</ul></a>
                    <a href="controlpersonal.php" class="nav-link"><ul><i class="fas fa-truck-loading"></i>Control de Personal</ul></a>
                    <a href="registroventas.php" class="nav-link"><ul><i class="fas fa-arrow-right"></i>Registro de Ventas</ul></a>
                    <a href="../login.php" class="nav-link"><ul><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</ul></a>
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
            <div class="contenedor-proveedores">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1"><i class="fas fa-truck me-2"></i>Gestión de Proveedores</h4>
                        <p class="text-muted mb-0">Administra los proveedores del sistema</p>                        
                    </div>
                    <button class="btn btn-mad" data-bs-toggle="modal" data-bs-target="#modalProveedor">
                        <i class="fas fa-plus me-2"></i>Nuevo Proveedor
                    </button>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-building fa-2x text-primary mb-2"></i>
                                <?php
                                while($row1=pg_fetch_assoc($result1)){
                                    echo "<h4>$row1[cantidad_proveedor]</h4>";

                                    if($row1['cantidad_proveedor']===1){
                                        echo "<p class='mb-0'>proveedor</p>";
                                    } else {
                                        echo "<p class='mb-0'>proveedores</p>";
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-truck-loading fa-2x text-success mb-2"></i>
                                <?php
                                while($row2=pg_fetch_assoc($result2)){
                                    echo "<h4>$row2[cantidad_movimiento]</h4>";

                                    if($row2['cantidad_movimiento']===1){
                                        echo "<p class='mb-0'>entrada este mes</p>";
                                    } else {
                                        echo "<p class='mb-0'> entradas este mes</p>";
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-boxes fa-2x text-warning mb-2"></i>
                                <h4>45</h4>
                                <p class="mb-0">Productos Asociados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-bell fa-2x text-danger mb-2"></i>
                                <h4>3</h4>
                                <p class="mb-0">Pedidos Pendientes</p>
                            </div>
                        </div>          
                    </div>
                </div>

                <div class="modal fade" id="modalProveedor" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Insertar proveedor</h5>
                            </div>
                            <div class="modal-body">
                                <form id="formularioProveedor" method="POST" name="accion" value="insertar">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label" for="codigoProveedor">Código del Proveedor</label>
                                            <input type="text" id="codigoProveedor" name="codigoProveedor" class=form-control required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="nombreProveedor">Nombre del Proveedor</label>
                                            <input type="text" id="nombreProveedor" name="nombreProveedor" class="form-control" required>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label" for="telefonoProveedor">Teléfono</label>
                                            <input type="tel" id="telefonoProveedor" name="telefonoProveedor" class="form-control" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="direccionProveedor">Dirección</label>
                                            <textarea class="form-control" id="direccionProveedor" name="direccionProveedor" rows="2" required></textarea>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <input type="submit" class="btn btn-mad" name="accion" value="insertar">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Lista de Proveedores</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 text-center">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Proveedor</th>
                                        <th>Telefono</th> 
                                        <th>Dirección</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    while($row5=pg_fetch_assoc($result5)){
                                        echo "
                                        <tr>
                                            <td>$row5[cod_proveedor]</td>
                                            <td><strong>$row5[nombre]</strong></td>
                                            <td><i class='fas fa-phone me-1'></i>$row5[telefono]</td>
                                            <td><i class='fas fa-map-marker me-1'></i>$row5[direccion]</td>
                                            <td>
                                                <div class='btn-group btn-group-sm gap-1'>
                                                    <button class='btn btn-outline-primary' data-bs-toggle='modal' data-bs-target='#modalActualizarProveedor' title='Actualizar' 
                                                        onclick=\"cargarProveedor('{$row5['cod_proveedor']}','{$row5['nombre']}','{$row5['telefono']}','{$row5['direccion']}')\">
                                                        <i class='fas fa-edit'></i>
                                                    </button>
                                            
                                                    <form method='POST' id='formularioEliminar'>
                                                        <input type='hidden' name='accion' value='eliminar'>
                                                        <input type='hidden' name='cod_proveedor' value='{$row5['cod_proveedor']}'>
                                                        <button class='btn btn-outline-danger' id='eliminarProveedor' title='Eliminar' name='accion' value='eliminar'>
                                                            <i class='fas fa-trash'></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal fade" id="modalActualizarProveedor" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Actualizar Proveedor</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <form id="formularioActualizarProveedor" method="POST" name="accion" value="actualizar">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label" for="codigoActualizarProveedor">Código del Proveedor</label>
                                        <input type="text" id="codigoActualizarProveedor" name="codigoActualizarProveedor" class="form-control" readonly>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" for="nombreActualizarProveedor">Nombre del Proveedor</label>
                                        <input type="text" id="nombreActualizarProveedor" name="nombreActualizarProveedor" class="form-control" required>
                                    </div>
                                    
                                    <div class="col-md-12"> 
                                        <label class="form-label" for="telefonoActualizarProveedor">Teléfono</label>
                                        <input type="text" id="telefonoActualizarProveedor" name="telefonoActualizarProveedor" class="form-control" required>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" for="direccionActualizarProveedor">Dirección</label>
                                        <textarea class="form-control" id="direccionActualizarProveedor" name="direccionActualizarProveedor" rows="2" required></textarea>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <input type="submit" class="btn btn-mad" name="accion" value="actualizar">
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
        document.getElementById("formularioProveedor").addEventListener("submit", function(event) {
            const codprove=document.getElementById("codigoProveedor").value.trim();
            const nomprove=document.getElementById("nombreProveedor").value.trim();
            const teleprove=document.getElementById("telefonoProveedor").value.trim();
            const dirprove=document.getElementById("direccionProveedor").value.trim();

            const telprove = teleprove.replace(/\s+/g, '');

            if (codprove === " ") {
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El código no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(codprove.length>10){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El código no pueder ser mayor de 10 dígitos",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            const regexnom = /^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

            if(nomprove===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El nombre no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if (!regexnom.test(nomprove)) {
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El nombre debe empezar con mayúscula y contener solo letras",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(telprove===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El teléfono no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(telprove[0]!=='9' || telprove.length!==9 || isNaN(telprove)){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El teléfono debe empezar con 9 y tener 9 números",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(dirprove===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "la dirección no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }
        });

        function cargarProveedor(codigo,nombre,telefono,direccion){
            document.getElementById("codigoActualizarProveedor").value=codigo;
            document.getElementById("nombreActualizarProveedor").value=nombre;
            document.getElementById("telefonoActualizarProveedor").value=telefono;
            document.getElementById("direccionActualizarProveedor").value=direccion;
        }

        document.getElementById("formularioActualizarProveedor").addEventListener("submit", function(event) {
            const codprove=document.getElementById("codigoActualizarProveedor").value.trim();
            const nomprove=document.getElementById("nombreActualizarProveedor").value.trim();
            const teleprove=document.getElementById("telefonoActualizarProveedor").value.trim();
            const dirprove=document.getElementById("direccionActualizarProveedor").value.trim();

            const telprove = teleprove.replace(/\s+/g, '');

            if (codprove === " ") {
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El código no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }  

            const regexnom = /^[A-Z][a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/;

            if(nomprove===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El nombre no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if (!regexnom.test(nomprove)) {
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El nombre debe empezar con mayúscula y contener solo letras",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(telprove===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El teléfono no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            } else if(telprove[0]!=='9' || telprove.length!==9 || isNaN(telprove)){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "El teléfono debe empezar con 9 y tener 9 números",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }

            if(dirprove===" "){
                Swal.fire({
                    icon: "warning",
                    title: "Oops...",
                    text: "La dirección no puede estar vacío",
                    width: "350px",
                });
                event.preventDefault();
                return;
            }
        });

        document.getElementById('eliminarProveedor').addEventListener('click', function () {
            event.preventDefault();

            Swal.fire({
                title: "Estás seguro?",
                text: "Esta acción no se puede deshacer",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Si, estoy seguro"
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formularioEliminar').submit();

                    Swal.fire({
                    title: "Eliminado",
                    text: "Se eliminó correctamente al proveedor",
                    icon: "success"
                    });
                } else {
                    return;
                }
            });
        });
    </script>
</body>
</html>