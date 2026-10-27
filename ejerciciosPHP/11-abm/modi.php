<?php
// modi.php - Endpoint que delega a la capa View (MVT)

require_once __DIR__ . '/views/RenglonesView.php';

$datos = [
    'nroFactura'     => $_POST['nroFactura'] ?? '',
    'fechaFactura'   => $_POST['fechaFactura'] ?? '',
    'codArticulo'    => $_POST['codArticulo'] ?? '',
    'descripcion'    => $_POST['descripcion'] ?? '',
    'unidadMedida'   => $_POST['unidadMedida'] ?? '',
    'cantidad'       => (float)($_POST['cantidad'] ?? 0),
    'precioUnitario' => (float)($_POST['precioUnitario'] ?? 0),
];

$view = new RenglonesView();
$view->procesarModi($datos);
