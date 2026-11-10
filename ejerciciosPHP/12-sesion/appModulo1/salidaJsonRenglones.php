<?php
// ========================================================================================
// ARCHIVO: appModulo1/salidaJsonRenglones.php (Protegido por sesión)
// OBJETIVO: Emite el JSON con los renglones filtrados y ordenados.
//
// PRIMERA LÍNEA: Protección de sesión con include('../manejoSesion.inc');
// ========================================================================================

include('../manejoSesion.inc'); // Comprueba la sesión antes de continuar con este pedido.

require_once __DIR__ . '/views/RenglonesView.php'; // Carga la clase que coordina la petición con el modelo.

$orden = $_POST['orden'] ?? 'nroFactura'; // Usa el orden recibido o nroFactura como valor inicial.
$filtros = [ // Agrupa los cinco filtros recibidos para pasarlos al modelo.
    'f_nroFactura'   => $_POST['f_nroFactura'] ?? '',
    'f_codArticulo'  => $_POST['f_codArticulo'] ?? '',
    'f_unidadMedida' => $_POST['f_unidadMedida'] ?? '',
    'f_descripcion'  => $_POST['f_descripcion'] ?? '',
    'f_fechaFactura' => $_POST['f_fechaFactura'] ?? '',
];

// Delegación a la capa View (MVT)
$view = new RenglonesView(); // Crea la vista; su constructor prepara el modelo.
$view->emitirJsonRenglones($filtros, $orden); // La vista pide al modelo el listado filtrado y ordenado.
