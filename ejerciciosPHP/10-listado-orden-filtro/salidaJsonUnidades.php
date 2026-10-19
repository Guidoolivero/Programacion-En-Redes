<?php
include 'datosConexionBase.php';
$log = __DIR__ . '/errores.log';
function logError($msg) {
  global $log;
  $linea = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
  file_put_contents($log, $linea, FILE_APPEND | LOCK_EX);
}
try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  sleep(2);
  $stmt = $pdo->query("SELECT * FROM unidades_medida");
  $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['unidades' => $filas], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  logError('Unidades: ' . $e->getMessage());
  echo json_encode(['error' => $e->getMessage(), 'unidades' => []]);
}
