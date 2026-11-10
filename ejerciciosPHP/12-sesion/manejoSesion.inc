<?php
// ========================================================================================
// ARCHIVO: manejoSesion.inc (Págs. 1 y 5 del apunte)
// OBJETIVO: Middleware de protección de sesión para aislar los módulos privados.
// Se incluye en la primera línea de todos los scripts protegidos dentro de appModulo1.
// Si no hay un identificativo de sesión válido, redirecciona al index del manejador.
// ========================================================================================

if (session_status() === PHP_SESSION_NONE) { // Comprueba si este pedido todavía no abrió una sesión PHP.
    session_start(); // Abre o retoma la sesión del navegador mediante su cookie.
}

// ========================================================================================
// VALIDACIÓN: ¿Existe la variable 'identificativo' en el arreglo $_SESSION?
// Si no existe, significa que el usuario intenta entrar de forma ilegal sin autenticarse.
// ========================================================================================
// Comprueba el dato que indica si este usuario ya se autenticó.
if (empty($_SESSION['identificativo'])) { // empty también contempla que la variable no exista.
    header('Location: /ejerciciosPHP/12-sesion/index.php'); // Ruta desde la raíz del sitio: sirve también desde views/.
    exit(); // Termina este pedido para que no continúe el código de abajo.
}
