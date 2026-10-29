<?php
// ========================================================================================
// ARCHIVO: appModulo1/index.php (Pág. 1 del apunte "Como llevar una app al entorno de sesión")
// OBJETIVO: Tablero de control principal del Módulo 1 en arquitectura MVT.
//
// PRIMERA LÍNEA OBLIGATORIA: include('../manejoSesion.inc');
// Esto protege todo el módulo. Si el usuario no tiene sesión activa, manejoSesion.inc
// lo redirige automáticamente a ../index.php (que a su vez lo envía a formularioDeLogin.html).
// ========================================================================================

include('../manejoSesion.inc');

// En arquitectura MVT (Model-View-Template), index.php delega la renderización a la View
require_once __DIR__ . '/views/RenglonesView.php';

$view = new RenglonesView();
$view->render();
