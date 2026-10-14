<?php
// views/eliminar_renglon.php - Vista modular para eliminar un renglón de la base de datos vía AJAX

require_once __DIR__ . '/../models/FacturaModel.php';
require_once __DIR__ . '/../models/logger.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    registrarError("Método no permitido al intentar eliminar", 'ELIMINAR_ACCESO');
    echo json_encode(['status' => 'error', 'mensaje' => 'Método no permitido']);
    exit;
}

$nroFactura = $_POST['nroFactura'] ?? null;
if (!$nroFactura) {
    registrarError("Nro de factura no especificado para eliminación", 'ELIMINAR_PARAM');
    echo json_encode(['status' => 'error', 'mensaje' => 'Nro de factura no especificado']);
    exit;
}

$model = new FacturaModel();
$errorMsg = '';
$ok = $model->eliminarRenglon($nroFactura, $errorMsg);

if ($ok) {
    echo json_encode(['status' => 'success', 'mensaje' => 'Factura eliminada de la base de datos']);
} else {
    registrarError("Fallo al eliminar factura $nroFactura: $errorMsg", 'BD_DELETE');
    echo json_encode(['status' => 'error', 'mensaje' => 'Error al eliminar en la BD: ' . $errorMsg]);
}
