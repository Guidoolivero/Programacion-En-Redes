<?php
// ========================================================================================
// ARCHIVO: appModulo1/destruirsesion.php (Pág. 2 del apunte "Como llevar una app al entorno de sesión"
//          y Pág. 4, 9 de "PHP Manejo de sesión")
// OBJETIVO: Destruye la sesión de usuario y redirige al formulario de login.
// ========================================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Vaciar el arreglo $_SESSION
$_SESSION = [];

// 2. Si se están utilizando cookies para la sesión, forzar expiración de la cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruir los datos de sesión en el servidor
session_destroy();

// 4. Redireccionar al formulario de login (Pág. 9 del apunte)
header('Location: ../formularioDeLogin.html');
exit();
