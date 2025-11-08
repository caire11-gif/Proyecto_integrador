<?php include('../../../login/ingresarlogin.php') ?>

<?php
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']==='insertar')){


    $rucprove=$_POST['rucProveedor'] ?? '';
    $nomprove=$_POST['razonSocialProveedor'] ?? '';
    $telprove=$_POST['telefonoProveedor'] ?? '';
    $dirprove=$_POST['direccionProveedor'] ?? '';

    $timestamp = substr(time(), -6);
    $codprove = 'PRO' . $timestamp;

    $nomprove=ucwords(strtolower($nomprove));
    $telprove = str_replace(' ', '', $telprove);
    $dirprove = mb_convert_encoding($dirprove, 'UTF-8', 'auto');
    
    $dirprove = ucwords(strtolower($dirprove));

    $vericodprove=pg_query_params($conexion, "SELECT COUNT(cod_proveedor) FILTER(WHERE cod_proveedor=$1) AS cantidad_codigo_proveedor, COUNT(ruc) FILTER(WHERE ruc=$2) AS ruc_proveedor, COUNT(telefono) FILTER(WHERE telefono=$3) AS cantidad_telefono_proveedor from proveedor",array($codprove,$rucprove,$telprove));
    if(!$vericodprove){
        echo "Error al verificar el código, teléfono y ruc del proveedor";
        exit;
    }

    $veri=pg_fetch_assoc($vericodprove);
    if($veri){
        $veric=(int)$veri['cantidad_codigo_proveedor'];
        $verit=(int)$veri['cantidad_telefono_proveedor'];
        $verir=(int)$veri['ruc_proveedor'];
    } else {
        $veric=$verit=$verir=0;
    }

    if($veric===0){
        if($verit===0){
            if($verir===0){
                $sql1="INSERT INTO proveedor(cod_proveedor,razon_social,ruc,telefono,direccion) VALUES ($1,$2,$3,$4,$5)";
                $resul=pg_query_params($conexion,$sql1,array($codprove,$nomprove,$rucprove,$telprove,$dirprove));

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
                                    window.location.href = '../../../administrador/proveedores.html';
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
                                text: 'No pueden haber dos rucs iguales. Intente con otro.',
                                width: '350px'
                            }).then(() => {
                                window.location.href = '../../../administrador/proveedor.html';
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
                            window.location.href = '../../../administrador/proveedor.html';
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
                        window.location.href = '../../../administrador/proveedor.html';
                    });
            });
            </script>
        ";

        exit;
    }
}
?>