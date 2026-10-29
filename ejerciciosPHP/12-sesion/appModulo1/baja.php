<?php
// ========================================================================================
// ARCHIVO: appModulo1/baja.php (Protegido por sesión)
// OBJETIVO: Endpoint AJAX para eliminación de renglón según clave compuesta.
//
// PRIMERA LÍNEA: Protección de sesión con include('../manejoSesion.inc');
// ========================================================================================

include('../manejoSesion.inc');

require_once __DIR__ . '/views/RenglonesView.php';

$nro = $_POST['nroFactura'] ?? '';
$cod = $_POST['codArticulo'] ?? '';

// Delegación a la capa View (MVT)
$view = new RenglonesView();
$view->procesarBaja($nro, $cod);
