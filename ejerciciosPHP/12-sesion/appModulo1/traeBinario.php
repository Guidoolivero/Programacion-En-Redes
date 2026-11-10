<?php
// ========================================================================================
// ARCHIVO: appModulo1/traeBinario.php (Protegido por sesión - Pág. 23 del apunte CRUD)
// OBJETIVO: Emite el JSON con el PDF codificado en Base64 para visualización en modal.
//
// PRIMERA LÍNEA: Protección de sesión con include('../manejoSesion.inc');
// ========================================================================================

include('../manejoSesion.inc'); // Comprueba la sesión antes de continuar con este pedido.

require_once __DIR__ . '/views/RenglonesView.php'; // Carga la clase que coordina la petición con el modelo.

$nro = $_GET['nroFactura'] ?? ($_POST['nroFactura'] ?? ''); // Toma la factura de la URL; si no vino, prueba en POST.
$cod = $_GET['codArticulo'] ?? ($_POST['codArticulo'] ?? ''); // Toma el artículo de la URL; si no vino, prueba en POST.

// Delegación a la capa View (MVT)
$view = new RenglonesView(); // Crea la vista; su constructor prepara el modelo.
$view->emitirBinarioPdf($nro, $cod); // La vista pide el binario al modelo y responde con JSON.
