<?php
include __DIR__ . '/../manejoSesion.inc'; // Primero comprueba la sesión.
require_once __DIR__ . '/models/Database.php'; // Conexión y registro de errores compartidos.

$pdo = Database::getConnection();
header('Content-Type: application/json; charset=utf-8');
$nroFactura = $_GET['nroFactura'] ?? ($_POST['nroFactura'] ?? '');
$codArticulo = $_GET['codArticulo'] ?? ($_POST['codArticulo'] ?? '');
$pdf = null;
if ($pdo) {

    try {
        // SELECT PDF: busca únicamente el binario del renglón elegido.
        $stmt = $pdo->prepare("SELECT `documentoPdf` FROM renglones_factura WHERE `nro_factura` = :nro AND `cod_articulo` = :cod");
        $stmt->bindParam(':nro', $nroFactura);
        $stmt->bindParam(':cod', $codArticulo);
        $stmt->execute(); // Ejecuta el SELECT del archivo PDF.
        $fila = $stmt->fetch(); // Obtiene una sola fila de la respuesta SQL.
        if ($fila && !empty($fila['documentoPdf'])) {
            $pdf = base64_encode($fila['documentoPdf']); // Convierte el binario a texto Base64 para poder enviarlo en JSON.
        }

    } catch (PDOException $e) {
        Database::logError('traeBinario: ' . $e->getMessage());

    }
}
echo json_encode(['documentoPdf'=>$pdf], JSON_INVALID_UTF8_SUBSTITUTE);
