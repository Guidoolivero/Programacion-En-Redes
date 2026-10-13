<?php
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['nroFactura'])) {
  echo json_encode(['error' => 'Faltan datos']);
  exit;
}

$obj = new stdClass();
$obj->nroFactura     = $_POST['nroFactura'];
$obj->fechaFactura   = $_POST['fechaFactura'];
$obj->codArticulo    = $_POST['codArticulo'];
$obj->descripcion    = $_POST['descripcion'];
$obj->cantidad       = (float) $_POST['cantidad'];
$obj->precioUnitario = (float) $_POST['precioUnitario'];
$obj->importeRenglon = $obj->cantidad * $obj->precioUnitario;

echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
