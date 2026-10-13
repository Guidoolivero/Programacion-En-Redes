<?php
// models/Database.php - Manejo de conexión PDO a InfinityFree

class Database {
    private static $host = 'sql111.infinityfree.com';
    private static $dbname = 'if0_42934198_facturacion';
    private static $username = 'if0_42934198';
    private static $password = 'dcyFb22feWoUQ';
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4",
                    self::$username,
                    self::$password,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'status'  => 'error',
                    'mensaje' => 'Error de conexión: ' . $e->getMessage()
                ]);
                exit;
            }
        }
        return self::$pdo;
    }
}
