<?php
class Conexion {
    private static $conexion = null;
    public static function getConexion() {

        if (self::$conexion === null) {
            $host = "localhost";
            $dbname = "sistemainventario";
            $user = "postgres";
            $password = "root";

            $cadena = "host=$host dbname=$dbname user=$user password=$password";

            self::$conexion = pg_connect($cadena);

            if (!self::$conexion) {
                die("❌ Error al conectar con PostgreSQL.");
            }
        }

        return self::$conexion;
    }
}
?>