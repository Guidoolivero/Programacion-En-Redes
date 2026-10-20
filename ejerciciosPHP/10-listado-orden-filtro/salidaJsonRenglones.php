<?php
include 'datosConexionBase.php';
$log = __DIR__ . '/errores.log';
function logError($msg) {
  global $log;
  $linea = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
  file_put_contents($log, $linea, FILE_APPEND | LOCK_EX);
}

// Helper para encontrar una columna por posibles nombres o palabra clave
function buscarColumna($cols, $candidatos, $fallbackIdx = null) {
  foreach ($candidatos as $cand) {
    foreach ($cols as $c) {
      if (strcasecmp($c, $cand) === 0) return $c;
    }
  }
  foreach ($candidatos as $cand) {
    foreach ($cols as $c) {
      if (stripos($c, $cand) !== false) return $c;
    }
  }
  return ($fallbackIdx !== null && isset($cols[$fallbackIdx])) ? $cols[$fallbackIdx] : $candidatos[0];
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

  $cols = $pdo->query("SHOW COLUMNS FROM renglones_factura")->fetchAll(PDO::FETCH_COLUMN);
  $colNro   = buscarColumna($cols, ['nroFactura', 'nro_factura', 'numero_factura', 'nro']);
  $colFecha = buscarColumna($cols, ['fechaFactura', 'fecha_factura', 'fecha']);
  $colCod   = buscarColumna($cols, ['codArticulo', 'cod_articulo', 'codigo_articulo', 'cod_art', 'articulo']);
  $colDesc  = buscarColumna($cols, ['descripcion', 'detalle', 'desc']);
  $colUm    = buscarColumna($cols, ['unidadMedida', 'unidad_medida', 'codUm', 'cod_um', 'id_unidad', 'um', 'unidad']);
  $colCant  = buscarColumna($cols, ['cantidad', 'cant']);
  $colPrec  = buscarColumna($cols, ['precioUnitario', 'precio_unitario', 'precio']);
  $colImp   = buscarColumna($cols, ['importeRenglon', 'importe_renglon', 'importe', 'total']);

  // Mapa de unidades_medida por si renglones_factura guarda id numérico o código y el filtro manda código/id
  $colsUm = $pdo->query("SHOW COLUMNS FROM unidades_medida")->fetchAll(PDO::FETCH_COLUMN);
  $umFilas = $pdo->query("SELECT * FROM unidades_medida")->fetchAll(PDO::FETCH_ASSOC);
  $mapaUmTexto = [];
  $equivalenciasUm = [$fUm];
  foreach ($umFilas as $u) {
    $vals = array_values($u);
    $idVal   = $u['id'] ?? $u['id_unidad'] ?? null;
    $codVal  = $u['codUm'] ?? $u['cod_um'] ?? $u['codigo'] ?? $u['unidad_medida'] ?? ($vals[0] ?? '');
    $descVal = $u['descripcion'] ?? $u['descripcionUm'] ?? $u['nombre'] ?? ($vals[1] ?? $codVal);
    if ($idVal !== null) $mapaUmTexto[(string)$idVal] = $codVal ?: $descVal;
    if ($codVal !== '')  $mapaUmTexto[(string)$codVal] = $codVal;
    if ($descVal !== '') $mapaUmTexto[(string)$descVal] = $codVal ?: $descVal;

    if ($fUm !== '' && (strcasecmp((string)$idVal, $fUm) === 0 || strcasecmp((string)$codVal, $fUm) === 0 || strcasecmp((string)$descVal, $fUm) === 0)) {
      if ($idVal !== null) $equivalenciasUm[] = (string)$idVal;
      if ($codVal !== '')  $equivalenciasUm[] = (string)$codVal;
      if ($descVal !== '') $equivalenciasUm[] = (string)$descVal;
    }
  }
  $equivalenciasUm = array_values(array_unique(array_filter($equivalenciasUm, fn($v) => $v !== '')));

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
  if ($fUm !== '' && count($equivalenciasUm) > 0) {
    $placeholdersUm = implode(',', array_fill(0, count($equivalenciasUm), '?'));
    $sql .= " AND `$colUm` IN ($placeholdersUm)";
    foreach ($equivalenciasUm as $eq) { $params[] = $eq; }
  }
  if ($fDesc !== '')  { $sql .= " AND `$colDesc` LIKE ?";  $params[] = "%$fDesc%"; }
  if ($fFecha !== '') { $sql .= " AND `$colFecha` = ?";    $params[] = $fFecha; }
  $sql .= " ORDER BY `$colOrden`";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $filasRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $filas = array_map(function($r) use ($colNro, $colCod, $colUm, $colDesc, $colFecha, $colCant, $colPrec, $colImp, $mapaUmTexto) {
    $rawUm = (string)($r[$colUm] ?? '');
    $umMostrar = $mapaUmTexto[$rawUm] ?? $rawUm;
    $cant = $r[$colCant] ?? '';
    $prec = $r[$colPrec] ?? '';
    $imp  = $r[$colImp] ?? (($cant !== '' && $prec !== '') ? number_format((float)$cant * (float)$prec, 2, '.', '') : '');
    return [
      'nroFactura'     => $r[$colNro] ?? '',
      'codArticulo'    => $r[$colCod] ?? '',
      'unidadMedida'   => $umMostrar,
      'descripcion'    => $r[$colDesc] ?? '',
      'fechaFactura'   => $r[$colFecha] ?? '',
      'cantidad'       => $cant,
      'precioUnitario' => $prec,
      'importeRenglon' => $imp,
    ];
  }, $filasRaw);

  echo json_encode(['renglones' => $filas, 'cantidad' => count($filas)], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  logError('Renglones: ' . $e->getMessage());
  echo json_encode(['error' => $e->getMessage(), 'renglones' => [], 'cantidad' => 0]);
}
