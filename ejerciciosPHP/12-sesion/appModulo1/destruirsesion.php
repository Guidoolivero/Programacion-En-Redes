<?php
// ========================================================================================
// ARCHIVO: appModulo1/destruirsesion.php (Pág. 2 del apunte "Como llevar una app al entorno de sesión"
//          y Pág. 4, 9 de "PHP Manejo de sesión")
// OBJETIVO: Destruye la sesión de usuario y redirige al formulario de login.
// ========================================================================================

include __DIR__ . '/../manejoSesion.inc'; // Comprueba la sesión antes de cerrarla.

// 1. Vaciar el arreglo $_SESSION
$_SESSION = []; // Vacía las variables guardadas para esta sesión.

// 2. Si se están utilizando cookies para la sesión, forzar expiración de la cookie
if (ini_get("session.use_cookies")) { // Revisa si la sesión se identifica mediante una cookie.
    $params = session_get_cookie_params(); // Recupera las opciones originales de la cookie para borrarla.
    setcookie(session_name(), '', time() - 42000, // Fecha en el pasado: el navegador debe descartar esta cookie.
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruir los datos de sesión en el servidor
session_destroy(); // Borra los datos de la sesión en el servidor.

// 4. Redireccionar al formulario de login (Pág. 9 del apunte)
header('Location: ../formularioDeLogin.html'); // Redirige al navegador a la página indicada.
exit(); // Termina este pedido para que no continúe el código de abajo.
