<?php
include 'datosConexionBase.php';
$log = __DIR__ . '/errores.log';
function logError($msg) {
  global $log;
  file_put_contents($log, '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
}
$out = '';
try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  $out .= "Conexion exitosa<br>";
  $nro = $_POST['nroFactura'] ?? '';
  $cod = $_POST['codArticulo'] ?? '';
  $sql = "DELETE FROM renglones_factura WHERE nroFactura=? AND codArticulo=?";
  $stmt = $pdo->prepare($sql);
  $out .= "Preparacion exitosa<br>";
  $stmt->execute([$nro, $cod]);
  $out .= "Ejecucion exitosa<br>";
  $out .= "Baja de renglón $nro / $cod correcta";
} catch (PDOException $e) {
  logError('Baja: ' . $e->getMessage());
  $out .= "Error: " . $e->getMessage();
}
echo $out;
