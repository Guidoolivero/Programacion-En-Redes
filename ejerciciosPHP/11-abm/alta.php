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
  $fecha = $_POST['fechaFactura'] ?? '';
  $cod = $_POST['codArticulo'] ?? '';
  $desc = $_POST['descripcion'] ?? '';
  $um = $_POST['unidadMedida'] ?? '';
  $cant = (float)($_POST['cantidad'] ?? 0);
  $precio = (float)($_POST['precioUnitario'] ?? 0);
  $importe = $cant * $precio;
  $sql = "INSERT INTO renglones_factura (nroFactura, fechaFactura, codArticulo, descripcion, unidadMedida, cantidad, precioUnitario, importeRenglon) VALUES (?,?,?,?,?,?,?,?)";
  $stmt = $pdo->prepare($sql);
  $out .= "Preparacion exitosa<br>";
  $stmt->execute([$nro, $fecha, $cod, $desc, $um, $cant, $precio, $importe]);
  $out .= "Ejecucion exitosa<br>";
  $out .= "Alta de renglón $nro / $cod correcta";
} catch (PDOException $e) {
  logError('Alta: ' . $e->getMessage());
  $out .= "Error: " . $e->getMessage();
}
echo $out;
