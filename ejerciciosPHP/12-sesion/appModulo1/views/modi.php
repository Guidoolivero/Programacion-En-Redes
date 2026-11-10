<?php
include __DIR__ . '/../../manejoSesion.inc'; // Primero comprueba la sesión.
require_once __DIR__ . '/../models/Database.php'; // Conexión y registro de errores compartidos.

$pdo = Database::getConnection();
$archivoPdf = $_FILES['documentoPdf'] ?? null;
$traza = '';

// Recibe los datos, valida, ejecuta SQL y devuelve el resultado.
if (!$pdo) {
    $traza = "No se pudo conectar a la base de datos.";
    echo $traza;
    return;
}

$nro     = trim((string)($_POST['nroFactura'] ?? ''));
$fecha   = trim((string)($_POST['fechaFactura'] ?? ''));
$cod     = trim((string)($_POST['codArticulo'] ?? ''));
$desc    = trim((string)($_POST['descripcion'] ?? ''));
$um      = trim((string)($_POST['unidadMedida'] ?? ''));
$cant    = (float)($_POST['cantidad'] ?? 0); // Convierte la cantidad recibida a un número decimal.
$precio  = (float)($_POST['precioUnitario'] ?? 0); // Convierte el precio recibido a un número decimal.
$importe = $cant * $precio; // Calcula el importe del renglón en el servidor.

$traza .= "Parte Modificacion simple de datos\n";
$traza .= "nroFactura: $nro / codArticulo: $cod\n";

try {
    $cambios = 'fecha_factura=:fechaFactura, descripcion=:descripcion,
        cod_um=:unidadMedida, cantidad=:cantidad, precio_unitario=:precioUnitario';
    $binds = [
        ':fechaFactura' => $fecha, ':descripcion' => $desc, ':unidadMedida' => $um,
        ':cantidad' => $cant, ':precioUnitario' => $precio,
        ':nroFactura' => $nro, ':codArticulo' => $cod,
    ];
    // MySQL puede calcular el importe; en ese caso no se escribe esa columna.
    $columna = $pdo->query("SHOW COLUMNS FROM renglones_factura LIKE 'importe_renglon'")->fetch(PDO::FETCH_ASSOC);
    $importeCalculado = strpos(strtolower($columna['Extra'] ?? ''), 'generated') !== false;
    // Si MySQL calcula el importe, solo se actualizan cantidad y precio.
    if (!$importeCalculado) {
        $cambios .= ', importe_renglon=:importeRenglon';
        $binds[':importeRenglon'] = $importe;
    }
    // La factura y el artículo identifican la fila que se modifica.
    $sql = "UPDATE renglones_factura SET $cambios WHERE nro_factura=:nroFactura AND cod_articulo=:codArticulo";
    $stmt = $pdo->prepare($sql); // Prepara la consulta SQL; todavía no la ejecuta.
    $traza .= "\nPreparacion exitosa!";

    foreach ($binds as $ph => $val) {
        $stmt->bindValue($ph, $val); // Vincula un valor al marcador correspondiente, separado del SQL.
        //$binds (El arreglo de datos)
        //$ph (Placeholder / Marcador): Contiene la clave, por ejemplo ':id'.
        //$val (Value / Valor): Contiene el dato real a insertar, por ejemplo 15.
    }
    $traza .= "\nBinding exitoso!";

    $stmt->execute(); // UPDATE: ejecuta los cambios en los campos.
    $traza .= "\nEjecucion exitosa!";
    $traza .= "\nModificacion de renglón $nro / $cod correcta";

    // Guarda el PDF opcional como binario dentro de esta misma operación.
    if ($archivoPdf && isset($archivoPdf['tmp_name']) && !empty($archivoPdf['name'])) {
        try {
            $contenidoPdf = file_get_contents($archivoPdf['tmp_name']);
            $stmt = $pdo->prepare("UPDATE renglones_factura SET documentoPdf=:doc WHERE nro_factura=:nro AND cod_articulo=:cod");
            $stmt->bindParam(':doc', $contenidoPdf, PDO::PARAM_LOB); // LOB indica contenido binario.
            $stmt->bindParam(':nro', $nro);
            $stmt->bindParam(':cod', $cod);
            $stmt->execute();
            $traza .= "\nDocumento PDF actualizado correctamente.";
        } catch (Exception $e) {
            Database::logError('Error actualizando PDF modi: ' . $e->getMessage());
        }
    }
} catch (PDOException $e) {
    Database::logError('Modi: ' . $e->getMessage());
    $traza .= "\n" . $e->getMessage();
}

echo $traza;
