<?php
// ========================================================================================
// ARCHIVO: descargarErrores.php
// OBJETIVO: Descarga el archivo de registro de errores (errores.log) como archivo de texto.
// ========================================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rutas posibles del archivo de log
$ruta1 = __DIR__ . '/errores.log';
$ruta2 = __DIR__ . '/appModulo1/errores.log';

$contenido = "";

if (file_exists($ruta1) && filesize($ruta1) > 0) {
    $contenido = file_get_contents($ruta1);
} elseif (file_exists($ruta2) && filesize($ruta2) > 0) {
    $contenido = file_get_contents($ruta2);
} else {
    $contenido = "[" . date('Y-m-d H:i:s') . "] No hay errores registrados hasta el momento." . PHP_EOL;
}

// Forzar la descarga en el navegador con encabezados HTTP
header('Content-Description: File Transfer');
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="errores.txt"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . strlen($contenido));

echo $contenido;
exit();
