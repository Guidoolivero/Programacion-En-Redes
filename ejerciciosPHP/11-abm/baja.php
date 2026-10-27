<?php
// baja.php - Endpoint que delega a la capa View (MVT)

require_once __DIR__ . '/views/RenglonesView.php';

$nro = $_POST['nroFactura'] ?? '';
$cod = $_POST['codArticulo'] ?? '';

$view = new RenglonesView();
$view->procesarBaja($nro, $cod);
