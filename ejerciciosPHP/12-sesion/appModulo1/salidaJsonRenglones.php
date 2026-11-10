<?php
// ========================================================================================
// ARCHIVO: appModulo1/salidaJsonRenglones.php (Protegido por sesión)
// OBJETIVO: Emite el JSON con los renglones filtrados y ordenados.
//
// PRIMERA LÍNEA: Protección de sesión con include __DIR__ . '/../manejoSesion.inc';
// ========================================================================================

include __DIR__ . '/../manejoSesion.inc'; // Comprueba la sesión antes de continuar con este pedido.

require_once __DIR__ . '/models/RenglonesModel.php';

$orden = $_POST['orden'] ?? 'nroFactura'; // Usa el orden recibido o nroFactura como valor inicial.
$filtros = [ // Agrupa los cinco filtros recibidos para pasarlos al modelo.
    'f_nroFactura'   => $_POST['f_nroFactura'] ?? '',
    'f_codArticulo'  => $_POST['f_codArticulo'] ?? '',
    'f_unidadMedida' => $_POST['f_unidadMedida'] ?? '',
    'f_descripcion'  => $_POST['f_descripcion'] ?? '',
    'f_fechaFactura' => $_POST['f_fechaFactura'] ?? '',
];

// Vista del listado: responde JSON con los filtros aplicados por el modelo.
header('Content-Type: application/json; charset=utf-8');
sleep(1); // Espera didáctica del listado, en segundos.
$modelo = new RenglonesModel();
echo json_encode($modelo->getRenglones($filtros, $orden), JSON_UNESCAPED_UNICODE);
