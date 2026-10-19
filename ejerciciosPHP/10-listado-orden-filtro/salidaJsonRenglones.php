<?php
include 'datosConexionBase.php';
$log = __DIR__ . '/errores.log';
function logError($msg) {
  global $log;
  $linea = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
  file_put_contents($log, $linea, FILE_APPEND | LOCK_EX);
}

$fNro = trim($_POST['f_nroFactura'] ?? '');
$fCod = trim($_POST['f_codArticulo'] ?? '');
$fUm = trim($_POST['f_unidadMedida'] ?? '');
$fDesc = trim($_POST['f_descripcion'] ?? '');
$fFecha = trim($_POST['f_fechaFactura'] ?? '');
$ordenPed = $_POST['orden'] ?? 'nroFactura';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  sleep(2);

  // Detectar nombres reales de las columnas en la tabla
  $cols = $pdo->query("SHOW COLUMNS FROM renglones_factura")->fetchAll(PDO::FETCH_COLUMN);
  $colNro   = in_array('nroFactura', $cols) ? 'nroFactura' : (in_array('nro_factura', $cols) ? 'nro_factura' : ($cols[0] ?? 'nroFactura'));
  $colFecha = in_array('fechaFactura', $cols) ? 'fechaFactura' : (in_array('fecha_factura', $cols) ? 'fecha_factura' : (in_array('fecha', $cols) ? 'fecha' : ($cols[1] ?? 'fechaFactura')));
  $colCod   = in_array('codArticulo', $cols) ? 'codArticulo' : (in_array('cod_articulo', $cols) ? 'cod_articulo' : ($cols[2] ?? 'codArticulo'));
  $colDesc  = in_array('descripcion', $cols) ? 'descripcion' : ($cols[3] ?? 'descripcion');
  $colUm    = in_array('unidadMedida', $cols) ? 'unidadMedida' : (in_array('unidad_medida', $cols) ? 'unidad_medida' : (in_array('codUm', $cols) ? 'codUm' : ($cols[4] ?? 'unidadMedida')));
  $colCant  = in_array('cantidad', $cols) ? 'cantidad' : ($cols[5] ?? 'cantidad');
  $colPrec  = in_array('precioUnitario', $cols) ? 'precioUnitario' : (in_array('precio_unitario', $cols) ? 'precio_unitario' : ($cols[6] ?? 'precioUnitario'));
  $colImp   = in_array('importeRenglon', $cols) ? 'importeRenglon' : (in_array('importe_renglon', $cols) ? 'importe_renglon' : (in_array('importe', $cols) ? 'importe' : ($cols[7] ?? 'importeRenglon')));

  $mapaOrden = [
    'nroFactura'     => $colNro,
    'codArticulo'    => $colCod,
    'unidadMedida'   => $colUm,
    'descripcion'    => $colDesc,
    'fechaFactura'   => $colFecha,
    'cantidad'       => $colCant,
    'precioUnitario' => $colPrec,
    'importeRenglon' => $colImp,
  ];
  $colOrden = $mapaOrden[$ordenPed] ?? $colNro;

  $sql = "SELECT * FROM renglones_factura WHERE 1=1";
  $params = [];
  if ($fNro !== '')   { $sql .= " AND `$colNro` LIKE ?";   $params[] = "%$fNro%"; }
  if ($fCod !== '')   { $sql .= " AND `$colCod` LIKE ?";   $params[] = "%$fCod%"; }
  if ($fUm !== '')    { $sql .= " AND `$colUm` = ?";       $params[] = $fUm; }
  if ($fDesc !== '')  { $sql .= " AND `$colDesc` LIKE ?";  $params[] = "%$fDesc%"; }
  if ($fFecha !== '') { $sql .= " AND `$colFecha` = ?";    $params[] = $fFecha; }
  $sql .= " ORDER BY `$colOrden`";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $filasRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Normalizar claves para que el frontend siempre reciba camelCase además de las originales
  $filas = array_map(function($r) use ($colNro, $colCod, $colUm, $colDesc, $colFecha, $colCant, $colPrec, $colImp) {
    return [
      'nroFactura'     => $r[$colNro] ?? '',
      'codArticulo'    => $r[$colCod] ?? '',
      'unidadMedida'   => $r[$colUm] ?? '',
      'descripcion'    => $r[$colDesc] ?? '',
      'fechaFactura'   => $r[$colFecha] ?? '',
      'cantidad'       => $r[$colCant] ?? '',
      'precioUnitario' => $r[$colPrec] ?? '',
      'importeRenglon' => $r[$colImp] ?? '',
    ];
  }, $filasRaw);

  echo json_encode(['renglones' => $filas, 'cantidad' => count($filas)], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  logError('Renglones: ' . $e->getMessage());
  echo json_encode(['error' => $e->getMessage(), 'renglones' => [], 'cantidad' => 0]);
}
