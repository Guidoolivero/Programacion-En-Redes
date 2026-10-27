<?php
// salidaJsonRenglones.php - Endpoint que delega a la capa View (MVT)

require_once __DIR__ . '/views/RenglonesView.php';

$orden = $_POST['orden'] ?? 'nroFactura';
$filtros = [
    'f_nroFactura'   => $_POST['f_nroFactura'] ?? '',
    'f_codArticulo'  => $_POST['f_codArticulo'] ?? '',
    'f_unidadMedida' => $_POST['f_unidadMedida'] ?? '',
    'f_descripcion'  => $_POST['f_descripcion'] ?? '',
    'f_fechaFactura' => $_POST['f_fechaFactura'] ?? '',
];

$view = new RenglonesView();
$view->emitirJsonRenglones($filtros, $orden);
