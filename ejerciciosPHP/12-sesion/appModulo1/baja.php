<?php
// ========================================================================================
// ARCHIVO: appModulo1/baja.php (Protegido por sesión)
// OBJETIVO: Endpoint AJAX para eliminación de renglón según clave compuesta.
//
// PRIMERA LÍNEA: Protección de sesión con include __DIR__ . '/../manejoSesion.inc';
// ========================================================================================

include __DIR__ . '/../manejoSesion.inc'; // Comprueba la sesión antes de continuar con este pedido.

require_once __DIR__ . '/models/RenglonesModel.php'; // La vista usa el modelo para acceder a MySQL.

$nro = $_POST['nroFactura'] ?? ''; // Lee el primer componente de la clave del registro.
$cod = $_POST['codArticulo'] ?? ''; // Lee el segundo componente de la clave del registro.

// Vista PHP: llama al modelo y devuelve la respuesta de esta operación.
$modelo = new RenglonesModel();
$traza = '';
$modelo->eliminarRenglon($nro, $cod, $traza);
echo $traza;
