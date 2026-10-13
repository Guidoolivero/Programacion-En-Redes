<?php
// views/listar_unidades.php - Vista modular para consultar unidades de medida

require_once __DIR__ . '/../models/FacturaModel.php';

$model = new FacturaModel();
$unidades = $model->getUnidadesMedida();

// Si se pide por AJAX/JSON
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($unidades);
    exit;
}

// O renderiza su template correspondiente
require __DIR__ . '/../templates/unidades_template.php';
