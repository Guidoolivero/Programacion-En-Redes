<?php
include 'datosConexionBase.php';
$log = __DIR__ . '/errores.log';
function logError($msg) {
  global $log;
  file_put_contents($log, '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
}
try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  sleep(1);
  $stmt = $pdo->query("SELECT * FROM unidades_medida");
  echo json_encode(['unidades' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  logError('Unidades: ' . $e->getMessage());
  echo json_encode(['error' => $e->getMessage(), 'unidades' => []]);
}
