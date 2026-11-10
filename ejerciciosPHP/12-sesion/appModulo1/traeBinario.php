<?php
// ========================================================================================
// ARCHIVO: appModulo1/traeBinario.php (Protegido por sesión - Pág. 23 del apunte CRUD)
// OBJETIVO: Emite el JSON con el PDF codificado en Base64 para visualización en modal.
//
// PRIMERA LÍNEA: Protección de sesión con include __DIR__ . '/../manejoSesion.inc';
// ========================================================================================

include __DIR__ . '/../manejoSesion.inc'; // Comprueba la sesión antes de continuar con este pedido.

require_once __DIR__ . '/models/RenglonesModel.php';

$nro = $_GET['nroFactura'] ?? ($_POST['nroFactura'] ?? ''); // Toma la factura de la URL; si no vino, prueba en POST.
$cod = $_GET['codArticulo'] ?? ($_POST['codArticulo'] ?? ''); // Toma el artículo de la URL; si no vino, prueba en POST.

// Vista del PDF: entrega el binario codificado en Base64 para el visor.
header('Content-Type: application/json; charset=utf-8');
$modelo = new RenglonesModel();
echo json_encode(['documentoPdf' => $modelo->getPdfBase64($nro, $cod)], JSON_INVALID_UTF8_SUBSTITUTE);
