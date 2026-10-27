<?php
// traeBinario.php - Endpoint MVT para obtener el PDF codificado en Base64

require_once __DIR__ . '/views/RenglonesView.php';

$nro = $_GET['nroFactura'] ?? ($_POST['nroFactura'] ?? '');
$cod = $_GET['codArticulo'] ?? ($_POST['codArticulo'] ?? '');

$view = new RenglonesView();
$view->emitirBinarioPdf($nro, $cod);
