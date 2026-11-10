<?php
// ========================================================================================
// ARCHIVO: appModulo1/modi.php (Protegido por sesión)
// OBJETIVO: Endpoint AJAX para modificación de renglón con PDF opcional.
//
// PRIMERA LÍNEA: Protección de sesión con include __DIR__ . '/../manejoSesion.inc';
// ========================================================================================

include __DIR__ . '/../manejoSesion.inc'; // Comprueba la sesión antes de continuar con este pedido.

require_once __DIR__ . '/models/RenglonesModel.php'; // La vista usa el modelo para acceder a MySQL.

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

// Vista PHP: llama al modelo y devuelve la respuesta de esta operación.
$modelo = new RenglonesModel();
$traza = '';
$modelo->actualizarRenglon($datos, $traza, $archivoPdf);
echo $traza;
