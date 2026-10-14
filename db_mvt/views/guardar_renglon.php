<?php
// views/guardar_renglon.php - Vista modular para validar, verificar duplicados e insertar/actualizar en BD

require_once __DIR__ . '/../models/FacturaModel.php';
require_once __DIR__ . '/../models/logger.php';

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

// Si hay errores de validación, registrar en log y devolver mensaje de error
if (!empty($errores)) {
    registrarError("Errores de validación en formulario: " . implode(' | ', $errores), 'VALIDACION_FORM');
    echo "<div class='error-box'>";
    echo "<h4>⚠️ Errores de validación en el servidor:</h4><ul>";
    foreach ($errores as $err) {
        echo "<li>" . htmlspecialchars($err) . "</li>";
    }
    echo "</ul></div>";
    exit;
}

$nroFactura     = (int)$nroFacturaRaw;
$cantidad       = (float)$cantidadRaw;
$precioUnitario = (float)$precioRaw;
$importeRenglon = $cantidad * $precioUnitario;
$modoEdicion    = isset($_POST['modoEdicion']) && $_POST['modoEdicion'] === '1';

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

// Verificación de unicidad: Si no es edición y ya existe el Nro de Factura, rechazar
if (!$modoEdicion && $model->existeFactura($nroFactura)) {
    registrarError("Intento de insertar factura duplicada Nro: $nroFactura", 'UNICIDAD_DUPLICADO');
    echo "<div class='error-box'>";
    echo "<h4>⚠️ Factura duplicada:</h4>";
    echo "<p>El Nro de Factura <b>{$nroFactura}</b> ya se encuentra registrado en la base de datos. Cada factura debe ser única.</p>";
    echo "</div>";
    exit;
}

$errorBD = '';
if ($modoEdicion) {
    $dbSuccess = $model->actualizarRenglon($datos, $errorBD);
    $accionTexto = "modificado";
} else {
    $dbSuccess = $model->insertarRenglon($datos, $errorBD);
    $accionTexto = "insertado";
}

echo "<h3>Respuesta recibida por AJAX (completado tras el timer de 5s)</h3>";
if ($dbSuccess) {
    echo "<p style='color: green; font-weight: bold;'>✔ Registro {$accionTexto} correctamente en la base de datos de InfinityFree.</p>";
} else {
    registrarError("Fallo en base de datos al $accionTexto factura $nroFactura: $errorBD", 'BD_PERSISTENCIA');
    echo "<p style='color: red; font-weight: bold;'>✖ Error al persistir en la base de datos: " . htmlspecialchars($errorBD) . "</p>";
}

echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; width:100%;'>";
echo "<tr><th>Nro Factura</th><td>{$datos['nroFactura']}</td></tr>";
echo "<tr><th>Fecha</th><td>{$datos['fechaFactura']}</td></tr>";
echo "<tr><th>Cód. Artículo</th><td>{$datos['codArticulo']}</td></tr>";
echo "<tr><th>Descripción</th><td>{$datos['descripcion']}</td></tr>";
echo "<tr><th>Unidad de Medida</th><td>{$datos['unidadMedida']}</td></tr>";
echo "<tr><th>Cantidad</th><td>{$datos['cantidad']}</td></tr>";
echo "<tr><th>Precio Unitario</th><td>{$datos['precioUnitario']}</td></tr>";
echo "<tr><th>Importe Renglón</th><td>{$importeRenglon}</td></tr>";
echo "</table>";
