<?php
include __DIR__ . '/../../manejoSesion.inc'; // Protege también los datos de la sesión.

// Vista de sesión: JavaScript escribe estos valores en el template mediante textContent.
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'login' => $_SESSION['login'] ?? '',
    'contador' => $_SESSION['ContadorDeSesion'] ?? 1,
    'identificativo' => $_SESSION['identificativo'],
], JSON_UNESCAPED_UNICODE);
