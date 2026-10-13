<?php
$host     = 'sql111.infinityfree.com';
$dbname   = 'if0_42934198_facturacion';
$username = 'if0_42934198';
$password = 'dcyFb22feWoUQ';

try {
    $pdo = new PDO(
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
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'mensaje' => 'Error de conexión: ' . $e->getMessage()
    ]);
    exit;
}
