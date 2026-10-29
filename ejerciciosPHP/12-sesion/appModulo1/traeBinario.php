<?php
// ========================================================================================
// ARCHIVO: appModulo1/traeBinario.php (Protegido por sesión - Pág. 23 del apunte CRUD)
// OBJETIVO: Emite el JSON con el PDF codificado en Base64 para visualización en modal.
//
// PRIMERA LÍNEA: Protección de sesión con include('../manejoSesion.inc');
// ========================================================================================

include('../manejoSesion.inc');

require_once __DIR__ . '/views/RenglonesView.php';

$nro = $_GET['nroFactura'] ?? ($_POST['nroFactura'] ?? '');
$cod = $_GET['codArticulo'] ?? ($_POST['codArticulo'] ?? '');

// Delegación a la capa View (MVT)
$view = new RenglonesView();
$view->emitirBinarioPdf($nro, $cod);
