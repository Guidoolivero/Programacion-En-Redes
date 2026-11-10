<?php
include __DIR__ . '/../manejoSesion.inc'; // Primero comprueba la sesión.
require_once __DIR__ . '/models/Database.php'; // Conexión y registro de errores compartidos.

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

// Comienza el mensaje que se mostrará en el navegador.
$traza .= "Respuesta del servidor al alta. Entradas recibidas en el req http:\n";
$traza .= "nroFactura: " . $nro . "\n";
$traza .= "codArticulo: " . $cod . "\n";
$traza .= "fechaFactura: " . $fecha . "\n";
$traza .= "unidadMedida: " . $um . "\n";
$traza .= "descripcion: " . $desc . "\n";
$traza .= "cantidad: " . $cant . "\n";
$traza .= "precioUnitario: " . $precio . "\n";
$traza .= "importeRenglon: " . $importe . "\n";

try {
    // Rechaza factura + artículo repetidos, aunque se evite la validación de JavaScript.
    $existe = false;
    if ($nro !== '' && $cod !== '') {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM renglones_factura WHERE LOWER(TRIM(CAST(nro_factura AS CHAR)))=LOWER(:nro) AND LOWER(TRIM(CAST(cod_articulo AS CHAR)))=LOWER(:cod)");
            $stmt->execute([':nro'=>$nro, ':cod'=>$cod]);
            $existe = (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            Database::logError('Validar renglón: ' . $e->getMessage());
        }
    }
    if ($existe) {
        $msgDup = "En la factura '$nro' ya está cargado el artículo '$cod'. (Podés repetir '$cod' en otra factura distinta o modificar ese renglón).";
        Database::logError('Alta rechazada (renglón duplicado en misma factura): ' . $msgDup);
        $traza .= "\nERROR DE VALIDACIÓN: " . $msgDup;
        echo $traza;
        return;
    }

    // Columnas y parámetros fijos: son los nombres reales de tu tabla.
    $columnas = 'nro_factura, fecha_factura, cod_articulo, descripcion, cod_um, cantidad, precio_unitario';
    $valores = ':nroFactura, :fechaFactura, :codArticulo, :descripcion, :unidadMedida, :cantidad, :precioUnitario';
    $binds = [
        ':nroFactura' => $nro, ':fechaFactura' => $fecha, ':codArticulo' => $cod,
        ':descripcion' => $desc, ':unidadMedida' => $um, ':cantidad' => $cant,
        ':precioUnitario' => $precio,
    ];
    // MySQL puede calcular el importe; en ese caso no se escribe esa columna.
    $columna = $pdo->query("SHOW COLUMNS FROM renglones_factura LIKE 'importe_renglon'")->fetch(PDO::FETCH_ASSOC);
    $importeCalculado = strpos(strtolower($columna['Extra'] ?? ''), 'generated') !== false;
    // Una columna generada no se incluye en el INSERT.
    if (!$importeCalculado) {
        $columnas .= ', importe_renglon';
        $valores .= ', :importeRenglon';
        $binds[':importeRenglon'] = $importe;
    }
    $sql = "INSERT INTO renglones_factura ($columnas) VALUES ($valores)";
    $stmt = $pdo->prepare($sql); // Prepara la consulta SQL; todavía no la ejecuta.
    $traza .= "\nPreparacion exitosa!";

    foreach ($binds as $ph => $val) {
        $stmt->bindValue($ph, $val); // Vincula un valor al marcador correspondiente, separado del SQL.
    }
    $traza .= "\nBinding exitoso!";

    $stmt->execute(); // INSERT: ejecuta el alta del registro.
    $traza .= "\nEjecucion exitosa!";
    $traza .= "\nAlta de renglón $nro / $cod correcta";

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
            Database::logError('Error guardando PDF alta: ' . $e->getMessage());
        }
    }
} catch (PDOException $e) {
    Database::logError('Alta: ' . $e->getMessage());
    $traza .= "\n" . $e->getMessage();
}

echo $traza;
