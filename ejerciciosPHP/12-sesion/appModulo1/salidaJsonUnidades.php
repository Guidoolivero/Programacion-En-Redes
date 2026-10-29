<?php
// ========================================================================================
// ARCHIVO: appModulo1/salidaJsonUnidades.php (Protegido por sesión)
// OBJETIVO: Emite el JSON con la lista de unidades de medida para poblar selects.
//
// PRIMERA LÍNEA: Protección de sesión con include('../manejoSesion.inc');
// ========================================================================================

include('../manejoSesion.inc');

require_once __DIR__ . '/views/RenglonesView.php';

// Delegación a la capa View (MVT)
$view = new RenglonesView();
$view->emitirJsonUnidades();
