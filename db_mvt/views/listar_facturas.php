<?php
// Habilitar reporte de errores para diagnóstico si algo falla
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// views/listar_facturas.php - Vista modular para consultar/listar renglones de facturas
require_once __DIR__ . '/../models/FacturaModel.php';

$model = new FacturaModel();
$renglones = $model->getRenglonesFactura();
$unidades = $model->getUnidadesMedida();

// Si se pide por AJAX/JSON
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($renglones);
    exit;
}

// Renderiza su template correspondiente
require __DIR__ . '/../templates/factura_template.php';
