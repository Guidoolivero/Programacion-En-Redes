<?php
// ========================================================================================
// ARCHIVO: appModulo1/salidaJsonUnidades.php (Protegido por sesión)
// OBJETIVO: Emite el JSON con la lista de unidades de medida para poblar selects.
//
// PRIMERA LÍNEA: Protección de sesión con include('../manejoSesion.inc');
// ========================================================================================

include('../manejoSesion.inc'); // Comprueba la sesión antes de continuar con este pedido.

require_once __DIR__ . '/views/RenglonesView.php'; // Carga la clase que coordina la petición con el modelo.

// Delegación a la capa View (MVT)
$view = new RenglonesView(); // Crea la vista; su constructor prepara el modelo.
$view->emitirJsonUnidades(); // La vista consulta las unidades y las devuelve en JSON.
