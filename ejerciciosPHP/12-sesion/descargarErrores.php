<?php
// ========================================================================================
// ARCHIVO: descargarErrores.php
// OBJETIVO: Consolida y descarga todos los registros de errores (errores.log).
// ========================================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rutas = [
    __DIR__ . "/errores.log",
    __DIR__ . "/appModulo1/errores.log",
    __DIR__ . "/../11-abm/errores.log",
    __DIR__ . "/../10-listado-orden-filtro/errores.log"
];

$lineasUnicas = [];

foreach ($rutas as $r) {
    if (file_exists($r) && filesize($r) > 0) {
        $filas = file($r, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($filas as $fila) {
            $fila = trim($fila);
            // Ignorar comentarios
            if ($fila === "" || $fila[0] === "#") continue;
            if (!in_array($fila, $lineasUnicas)) {
                $lineasUnicas[] = $fila;
            }
        }
    }
}

if (!empty($lineasUnicas)) {
    $contenido = implode(PHP_EOL, $lineasUnicas) . PHP_EOL;
} else {
    $contenido = "[" . date("Y-m-d H:i:s") . "] No hay errores registrados hasta el momento." . PHP_EOL;
}

header("Content-Description: File Transfer");
header("Content-Type: text/plain; charset=utf-8");
header("Content-Disposition: attachment; filename=\"errores.txt\"");
header("Expires: 0");
header("Cache-Control: must-revalidate");
header("Pragma: public");
header("Content-Length: " . strlen($contenido));

echo $contenido;
exit();
