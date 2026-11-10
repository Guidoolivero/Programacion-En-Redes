<?php
// ========================================================================================
// models/Database.php - Conexión PDO Singleton o carga perezosa / Lazy Loading para el patrón MVT dentro de appModulo1
// ========================================================================================

class Database {
    private static $pdo = null; // Conserva una sola conexión para reutilizarla en este pedido.
    private static $log = __DIR__ . '/../errores.log'; // Define dónde se escribirá el registro de errores del módulo.

    public static function logError($msg) {
        // Agrega fecha y hora al mensaje que se guardará en el log.
        $linea = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
        // Guardamos tanto en appModulo1/errores.log como en 12-sesion/errores.log
        // Escribe al final (FILE_APPEND) del log principal bloqueando el archivo; el '@' silencia fallos si no hay permisos
        // LOCK_EX Bloquea temporalmente el archivo mientras se escribe.
        @file_put_contents(self::$log, $linea, FILE_APPEND | LOCK_EX);
        // Respaldo redundante: intenta guardarlo dos niveles arriba por si falla la ruta principal
        @file_put_contents(__DIR__ . '/../../errores.log', $linea, FILE_APPEND | LOCK_EX);
    }

    public static function getConnection() {
        if (self::$pdo === null) { // Solo crea PDO si todavía no hay una conexión disponible.
            //:: => Operador de Resolución de Ámbito
            $archivoConexion1 = __DIR__ . '/../datosConexionBase.php'; // Primera opción: configuración de appModulo1.
            // Segunda opción: configuración de la carpeta 12-sesion.
            $archivoConexion2 = __DIR__ . '/../../datosConexionBase.php';

            if (file_exists($archivoConexion1)) {
                include $archivoConexion1; // Carga las variables de conexión desde el archivo del módulo.
            } elseif (file_exists($archivoConexion2)) {
                include $archivoConexion2; // Carga las variables desde la carpeta padre.
            } else {
                $host = 'sql111.infinityfree.com';
                $dbname = 'if0_42934198_facturacion';
                $username = 'if0_42934198';
                $password = 'dcyFb22feWoUQ';
            }

            try {
                self::$pdo = new PDO( // Abre la conexión MySQL usando la configuración elegida.
                    //:: => Operador de Resolución de Ámbito
                    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                    $username,
                    $password,
                    [
                        //ERRMODE_EXCEPTION convierte errores SQL en excepciones
                        // Convierte los errores SQL en excepciones capturables con catch.
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        // FETCH_ASSOC hace que fetch devuelva arreglos con nombres de columna
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Los resultados se leerán por nombre de columna.
                        //EMULATE_PREPARES en false usa prepares nativos de MySQL
                        PDO::ATTR_EMULATE_PREPARES   => false, // Usa las consultas preparadas nativas de MySQL.
                    ]
                );
            } catch (PDOException $e) {
                self::logError('Conexión PDO: ' . $e->getMessage());
                return null; // Avisa al modelo que no se pudo abrir la conexión.
            }
        }
        return self::$pdo; // Devuelve la conexión nueva o la que ya existía.
    }
}
