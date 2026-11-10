<?php
include __DIR__ . '/../manejoSesion.inc'; // Primero comprueba la sesión.
require_once __DIR__ . '/models/Database.php'; // Conexión y registro de errores compartidos.

$pdo = Database::getConnection();
$nroFactura = $_POST['nroFactura'] ?? '';
$codArticulo = $_POST['codArticulo'] ?? '';
$traza = '';

// Recibe los datos, valida, ejecuta SQL y devuelve el resultado.
if (!$pdo) {
    $traza = "No se pudo conectar a la base de datos.";
    echo $traza;
    return;
}

try {
    // DELETE: borra solo el renglón que coincide con los dos datos de la clave.
    $sql = "DELETE FROM renglones_factura WHERE `nro_factura`=:nroFactura AND `cod_articulo`=:codArticulo";
    $stmt = $pdo->prepare($sql); // Prepara la consulta SQL; todavía no la ejecuta.
    $traza .= "Preparacion exitosa!\n";

    $stmt->bindParam(':nroFactura', $nroFactura);
    $stmt->bindParam(':codArticulo', $codArticulo);
    $traza .= "Binding exitoso!\n";

    $stmt->execute(); // DELETE: ejecuta el borrado definitivo de la fila.
    $traza .= "Ejecucion exitosa!\n";
    $traza .= "Baja de renglón $nroFactura / $codArticulo correcta";

} catch (PDOException $e) {
    Database::logError('Baja: ' . $e->getMessage());
    $traza .= "\n" . $e->getMessage();
}

echo $traza;
