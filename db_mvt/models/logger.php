<?php
// logger.php - Función helper para registrar errores en db_mvt/errores.txt

function registrarError($mensaje, $contexto = 'GENERAL') {
    $archivoLog = __DIR__ . '/../errores.txt';
    $fecha = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
    
    $linea = "[$fecha] [$contexto] [IP: $ip] $mensaje" . PHP_EOL;
    
    // Escribe al final del archivo de manera segura con bloqueo (FILE_APPEND | LOCK_EX)
    @file_put_contents($archivoLog, $linea, FILE_APPEND | LOCK_EX);
}
