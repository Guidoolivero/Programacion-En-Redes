<?php
// ========================================================================================
// ARCHIVO: destruirsesion.php (en raíz de 12-sesion)
// OBJETIVO: Destruye la sesión de usuario y redirige al formulario de login.
// ========================================================================================

if (session_status() === PHP_SESSION_NONE) { // Comprueba si este pedido todavía no abrió una sesión PHP.
    session_start(); // Abre o retoma la sesión del navegador mediante su cookie.
}

// El apunte pide comprobar la sesión también antes de cerrarla.
if (empty($_SESSION['identificativo'])) {
    header('Location: ./formularioDeLogin.html');
    exit();
}

$_SESSION = []; // Vacía las variables guardadas para esta sesión.

if (ini_get("session.use_cookies")) { // Revisa si la sesión se identifica mediante una cookie.
    $params = session_get_cookie_params(); // Recupera las opciones originales de la cookie para borrarla.
    setcookie(session_name(), '', time() - 42000, // Fecha en el pasado: el navegador debe descartar esta cookie.
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy(); // Borra los datos de la sesión en el servidor.

header('Location: ./formularioDeLogin.html'); // Redirige al navegador a la página indicada.
exit(); // Termina este pedido para que no continúe el código de abajo.
