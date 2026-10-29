<?php
// ========================================================================================
// ARCHIVO: appModulo1/alta.php (Protegido por sesión)
// OBJETIVO: Endpoint AJAX para inserción de renglón con PDF opcional.
//
// PRIMERA LÍNEA: Protección de sesión con include('../manejoSesion.inc');
// ========================================================================================

include('../manejoSesion.inc');

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

$archivoPdf = $_FILES['documentoPdf'] ?? null;

// Delegación a la capa View (MVT)
$view = new RenglonesView();
$view->procesarAlta($datos, $archivoPdf);
