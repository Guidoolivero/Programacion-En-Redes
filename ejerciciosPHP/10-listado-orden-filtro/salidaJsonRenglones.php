<?php
include 'datosConexionBase.php';
$log = __DIR__ . '/errores.log';
function logError($msg) {
  global $log;
  $linea = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
  file_put_contents($log, $linea, FILE_APPEND | LOCK_EX);
}
$camposOrden = ['nroFactura','codArticulo','unidadMedida','descripcion','fechaFactura','cantidad','precioUnitario','importeRenglon'];
$orden = $_POST['orden'] ?? 'nroFactura';
if (!in_array($orden, $camposOrden)) $orden = 'nroFactura';
$fNro = trim($_POST['f_nroFactura'] ?? '');
$fCod = trim($_POST['f_codArticulo'] ?? '');
$fUm = trim($_POST['f_unidadMedida'] ?? '');
$fDesc = trim($_POST['f_descripcion'] ?? '');
$fFecha = trim($_POST['f_fechaFactura'] ?? '');
try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  sleep(2);
  $sql = "SELECT * FROM renglones_factura WHERE 1=1";
  $params = [];
  if ($fNro !== '') { $sql .= " AND nroFactura LIKE ?"; $params[] = "%$fNro%"; }
  if ($fCod !== '') { $sql .= " AND codArticulo LIKE ?"; $params[] = "%$fCod%"; }
  if ($fUm !== '') { $sql .= " AND unidadMedida = ?"; $params[] = $fUm; }
  if ($fDesc !== '') { $sql .= " AND descripcion LIKE ?"; $params[] = "%$fDesc%"; }
  if ($fFecha !== '') { $sql .= " AND fechaFactura = ?"; $params[] = $fFecha; }
  $sql .= " ORDER BY `$orden`";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['renglones' => $filas, 'cantidad' => count($filas)], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  logError('Renglones: ' . $e->getMessage());
  echo json_encode(['error' => $e->getMessage(), 'renglones' => [], 'cantidad' => 0]);
}
