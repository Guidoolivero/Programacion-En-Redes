<?php
// ========================================================================================
// ARCHIVO: appModulo1/baja.php (Protegido por sesión)
// OBJETIVO: Endpoint AJAX para eliminación de renglón según clave compuesta.
//
// PRIMERA LÍNEA: Protección de sesión con include('../manejoSesion.inc');
// ========================================================================================

include('../manejoSesion.inc'); // Comprueba la sesión antes de continuar con este pedido.

require_once __DIR__ . '/views/RenglonesView.php'; // Carga la clase que coordina la petición con el modelo.

$nro = $_POST['nroFactura'] ?? ''; // Lee el primer componente de la clave del registro.
$cod = $_POST['codArticulo'] ?? ''; // Lee el segundo componente de la clave del registro.

// Delegación a la capa View (MVT)
$view = new RenglonesView(); // Crea la vista; su constructor prepara el modelo.
$view->procesarBaja($nro, $cod); // La vista llama a eliminarRenglon, donde está el DELETE.
