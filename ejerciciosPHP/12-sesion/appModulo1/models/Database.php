<?php
// ========================================================================================
// models/Database.php - Conexión PDO Singleton para el patrón MVT dentro de appModulo1
// ========================================================================================

class Database {
    private static $pdo = null;
    private static $log = __DIR__ . '/../errores.log';

    public static function logError($msg) {
        $linea = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
        @file_put_contents(self::$log, $linea, FILE_APPEND | LOCK_EX);
    }

    public static function getConnection() {
        if (self::$pdo === null) {
            $archivoConexion1 = __DIR__ . '/../datosConexionBase.php';
            $archivoConexion2 = __DIR__ . '/../../datosConexionBase.php';

            if (file_exists($archivoConexion1)) {
                include $archivoConexion1;
            } elseif (file_exists($archivoConexion2)) {
                include $archivoConexion2;
            } else {
                $host = 'sql111.infinityfree.com';
                $dbname = 'if0_42934198_facturacion';
                $username = 'if0_42934198';
                $password = 'dcyFb22feWoUQ';
            }

            try {
                self::$pdo = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                self::logError('Conexión PDO: ' . $e->getMessage());
                return null;
            }
        }
        return self::$pdo;
    }
}
