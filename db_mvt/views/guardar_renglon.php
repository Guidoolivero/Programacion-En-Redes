<?php
// views/guardar_renglon.php - Vista modular exclusivamente para recibir el AJAX POST, validar y persistir

require_once __DIR__ . '/../models/FacturaModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "<p style='color:red;'>Método no permitido.</p>";
    exit;
}

$errores = [];

// 1. Validar Nro Factura: obligatorio, entero y positivo
$nroFacturaRaw = $_POST['nroFactura'] ?? '';
if ($nroFacturaRaw === '' || !filter_var($nroFacturaRaw, FILTER_VALIDATE_INT) || (int)$nroFacturaRaw <= 0) {
    $errores[] = "El Nro de Factura es obligatorio y debe ser un número entero positivo.";
}

// 2. Validar Fecha Factura: obligatoria
$fechaFactura = trim($_POST['fechaFactura'] ?? '');
if ($fechaFactura === '') {
    $errores[] = "La Fecha de Factura es obligatoria.";
}

// 3. Validar Cód. Artículo: obligatorio
$codArticulo = trim($_POST['codArticulo'] ?? '');
if ($codArticulo === '') {
    $errores[] = "El Código de Artículo es obligatorio.";
}

// 4. Validar Descripción: obligatoria
$descripcion = trim($_POST['descripcion'] ?? '');
if ($descripcion === '') {
    $errores[] = "La Descripción es obligatoria.";
}

// 5. Validar Unidad de Medida: obligatoria
$unidadMedida = trim($_POST['unidadMedida'] ?? '');
if ($unidadMedida === '') {
    $errores[] = "Debe seleccionar una Unidad de Medida.";
}

// 6. Validar Cantidad: obligatoria, numérica y mayor a cero (sin negativos)
$cantidadRaw = $_POST['cantidad'] ?? '';
if ($cantidadRaw === '' || !is_numeric($cantidadRaw) || (float)$cantidadRaw <= 0) {
    $errores[] = "La Cantidad es obligatoria y debe ser mayor a cero (sin valores negativos).";
}

// 7. Validar Precio Unitario: obligatorio, numérico y mayor a cero (sin negativos)
$precioRaw = $_POST['precioUnitario'] ?? '';
if ($precioRaw === '' || !is_numeric($precioRaw) || (float)$precioRaw <= 0) {
    $errores[] = "El Precio Unitario es obligatorio y debe ser mayor a cero (sin valores negativos).";
}

// Si hay errores de validación, devolver mensaje de error
if (!empty($errores)) {
    echo "<div class='error-box'>";
    echo "<h4>⚠️ Errores de validación en el servidor:</h4><ul>";
    foreach ($errores as $err) {
        echo "<li>" . htmlspecialchars($err) . "</li>";
    }
    echo "</ul></div>";
    exit;
}

// Procesar datos validados
$nroFactura     = (int)$nroFacturaRaw;
$cantidad       = (float)$cantidadRaw;
$precioUnitario = (float)$precioRaw;
$importeRenglon = $cantidad * $precioUnitario;

$datos = [
    'nroFactura'     => $nroFactura,
    'fechaFactura'   => htmlspecialchars($fechaFactura),
    'codArticulo'    => htmlspecialchars($codArticulo),
    'descripcion'    => htmlspecialchars($descripcion),
    'unidadMedida'   => htmlspecialchars($unidadMedida),
    'cantidad'       => $cantidad,
    'precioUnitario' => $precioUnitario,
];

$model = new FacturaModel();
$dbSuccess = false;
try {
    $dbSuccess = $model->insertarRenglon($datos);
} catch (Exception $e) {
    $dbSuccess = false;
}

// Devolver tabla HTML de respuesta por AJAX tal como en los ejercicios
echo "<h3>Respuesta recibida por AJAX (completado tras el timer de 5s)</h3>";
if ($dbSuccess) {
    echo "<p style='color: green; font-weight: bold;'>✔ Registro insertado correctamente en InfinityFree.</p>";
} else {
    echo "<p style='color: #856404; background: #fff3cd; padding: 6px; border-radius: 4px;'>ℹ Datos recibidos y validados con éxito (aviso: la base remota no confirmó la inserción).</p>";
}

echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr><th>Nro Factura</th><td>{$datos['nroFactura']}</td></tr>";
echo "<tr><th>Fecha</th><td>{$datos['fechaFactura']}</td></tr>";
echo "<tr><th>Cód. Artículo</th><td>{$datos['codArticulo']}</td></tr>";
echo "<tr><th>Descripción</th><td>{$datos['descripcion']}</td></tr>";
echo "<tr><th>Unidad de Medida</th><td>{$datos['unidadMedida']}</td></tr>";
echo "<tr><th>Cantidad</th><td>{$datos['cantidad']}</td></tr>";
echo "<tr><th>Precio Unitario</th><td>{$datos['precioUnitario']}</td></tr>";
echo "<tr><th>Importe Renglón</th><td>{$importeRenglon}</td></tr>";
echo "</table>";
echo "<p><em>La página no se recargó (asincrónico vía AJAX).</em></p>";
