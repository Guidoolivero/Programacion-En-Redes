<?php
// ========================================================================================
// ARCHIVO: appModulo1/salidaJsonRenglones.php (Protegido por sesión)
// OBJETIVO: Emite el JSON con los renglones filtrados y ordenados.
//
// PRIMERA LÍNEA: Protección de sesión con include('../manejoSesion.inc');
// ========================================================================================

include('../manejoSesion.inc');

require_once __DIR__ . '/views/RenglonesView.php';

$orden = $_POST['orden'] ?? 'nroFactura';
$filtros = [
    'f_nroFactura'   => $_POST['f_nroFactura'] ?? '',
    'f_codArticulo'  => $_POST['f_codArticulo'] ?? '',
    'f_unidadMedida' => $_POST['f_unidadMedida'] ?? '',
    'f_descripcion'  => $_POST['f_descripcion'] ?? '',
    'f_fechaFactura' => $_POST['f_fechaFactura'] ?? '',
];

// Delegación a la capa View (MVT)
$view = new RenglonesView();
$view->emitirJsonRenglones($filtros, $orden);
