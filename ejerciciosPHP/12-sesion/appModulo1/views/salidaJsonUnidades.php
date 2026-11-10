<?php
include __DIR__ . '/../../manejoSesion.inc'; // Primero comprueba la sesión.
require_once __DIR__ . '/../models/Database.php'; // Conexión y registro de errores compartidos.

$pdo = Database::getConnection();
header('Content-Type: application/json; charset=utf-8');
sleep(2); // Espera didáctica de unidades.

$unidades = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM unidades_medida"); // query ejecuta directamente el SELECT de unidades.
        $unidades = $stmt->fetchAll();
    } catch (PDOException $e) {
        Database::logError('Unidades: ' . $e->getMessage());

    }
}
echo json_encode(['unidades'=>$unidades], JSON_UNESCAPED_UNICODE);
