<?php
// ========================================================================================
// ARCHIVO: manejoSesion.inc (Págs. 1 y 5 del apunte)
// OBJETIVO: Middleware de protección de sesión para aislar los módulos privados.
// Se incluye en la primera línea de todos los scripts protegidos dentro de appModulo1.
// Si no hay un identificativo de sesión válido, redirecciona al index del manejador.
// ========================================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================================================================
// VALIDACIÓN: ¿Existe la variable 'identificativo' en el arreglo $_SESSION?
// Si no existe, significa que el usuario intenta entrar de forma ilegal sin autenticarse.
// ========================================================================================
if (!isset($_SESSION['identificativo']) || empty($_SESSION['identificativo'])) {
    header('Location: ../index.php');
    exit();
}
