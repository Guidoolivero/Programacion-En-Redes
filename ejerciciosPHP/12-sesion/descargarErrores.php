<?php
// ========================================================================================
// ARCHIVO: descargarErrores.php
// OBJETIVO: Consolida y descarga todos los registros de errores (errores.log).
// ========================================================================================

if (session_status() === PHP_SESSION_NONE) { // Comprueba si este pedido todavía no abrió una sesión PHP.
    session_start(); // Abre o retoma la sesión del navegador mediante su cookie.
}

// El registro de errores se descarga dentro de una sesión autenticada.
if (empty($_SESSION['identificativo'])) {
    header('Location: ./formularioDeLogin.html');
    exit();
}

$rutas = [ // Lista los archivos de errores que se intentarán reunir.
    __DIR__ . "/errores.log",
    __DIR__ . "/appModulo1/errores.log",
    __DIR__ . "/../11-abm/errores.log",
    __DIR__ . "/../10-listado-orden-filtro/errores.log"
];

$lineasUnicas = []; // Acumula las líneas de error sin repetirlas.

foreach ($rutas as $r) { // Revisa un archivo de log por cada vuelta.
    if (file_exists($r) && filesize($r) > 0) { // Solo lee los archivos existentes que tienen contenido.
        // Lee el archivo como un arreglo de líneas no vacías.
        $filas = file($r, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($filas as $fila) {
            $fila = trim($fila); // Quita espacios y saltos de los extremos de la línea.
            // Ignorar comentarios
            if ($fila === "" || $fila[0] === "#") continue;
            if (!in_array($fila, $lineasUnicas)) { // Evita descargar dos veces el mismo mensaje.
                $lineasUnicas[] = $fila; // Agrega una línea que todavía no estaba en la lista.
            }
        }
    }
}

if (!empty($lineasUnicas)) {
    $contenido = implode(PHP_EOL, $lineasUnicas) . PHP_EOL; // Une los mensajes con saltos de línea para la descarga.
} else {
    $contenido = "[" . date("Y-m-d H:i:s") . "] No hay errores registrados hasta el momento." . PHP_EOL;
}

header("Content-Description: File Transfer");
header("Content-Type: text/plain; charset=utf-8"); // Informa que la respuesta es un archivo de texto.
header("Content-Disposition: attachment; filename=\"errores.txt\""); // Hace que el navegador ofrezca descargar errores.txt.
header("Expires: 0");
header("Cache-Control: must-revalidate");
header("Pragma: public");
header("Content-Length: " . strlen($contenido));

echo $contenido; // Envía al navegador el texto final del archivo.
exit(); // Termina este pedido para que no continúe el código de abajo.
