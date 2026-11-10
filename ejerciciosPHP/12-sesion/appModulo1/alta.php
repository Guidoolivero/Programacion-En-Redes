<?php
// ========================================================================================
// ARCHIVO: appModulo1/alta.php (Protegido por sesión)
// OBJETIVO: Endpoint AJAX para inserción de renglón con PDF opcional.
//
// PRIMERA LÍNEA: Protección de sesión con include('../manejoSesion.inc');
// ========================================================================================

include('../manejoSesion.inc'); // Comprueba la sesión antes de continuar con este pedido.

require_once __DIR__ . '/views/RenglonesView.php'; // Carga la clase que coordina la petición con el modelo.

$datos = [ // Reúne los campos que llegaron del formulario.
    'nroFactura'     => $_POST['nroFactura'] ?? '', // Lee la factura; ?? usa un valor vacío si no vino.
    'fechaFactura'   => $_POST['fechaFactura'] ?? '', // Lee la fecha enviada por el formulario.
    'codArticulo'    => $_POST['codArticulo'] ?? '', // Lee el artículo que identifica el renglón.
    'descripcion'    => $_POST['descripcion'] ?? '', // Lee la descripción ingresada.
    'unidadMedida'   => $_POST['unidadMedida'] ?? '', // Lee la unidad seleccionada.
    'cantidad'       => (float)($_POST['cantidad'] ?? 0), // Lee la cantidad y la convierte a decimal.
    'precioUnitario' => (float)($_POST['precioUnitario'] ?? 0), // Lee el precio y lo convierte a decimal.
];

$archivoPdf = $_FILES['documentoPdf'] ?? null; // Recibe la información del PDF temporal, si se adjuntó uno.

// Delegación a la capa View (MVT)
$view = new RenglonesView(); // Crea la vista; su constructor prepara el modelo.
$view->procesarAlta($datos, $archivoPdf); // La vista llama a insertarRenglon y devuelve la traza del alta.
