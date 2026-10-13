<?php
// Respuesta AJAX — Renglones de factura de ventas
// (el PHP no cambia respecto a un form normal)

if (!isset($_POST['nroFactura'])) {
  echo "<p>No llegaron datos.</p>";
  exit;
}

$nroFactura     = htmlspecialchars($_POST['nroFactura']);
$fechaFactura   = htmlspecialchars($_POST['fechaFactura']);
$codArticulo    = htmlspecialchars($_POST['codArticulo']);
$descripcion    = htmlspecialchars($_POST['descripcion']);
$unidadMedida   = htmlspecialchars($_POST['unidadMedida']);
$cantidad       = (float) $_POST['cantidad'];
$precioUnitario = (float) $_POST['precioUnitario'];
$importeRenglon = $cantidad * $precioUnitario;

echo "<h3>Datos recibidos por AJAX</h3>";
echo "<table>";
echo "<tr><th>Nro Factura</th><td>$nroFactura</td></tr>";
echo "<tr><th>Fecha</th><td>$fechaFactura</td></tr>";
echo "<tr><th>Cod. artículo</th><td>$codArticulo</td></tr>";
echo "<tr><th>Descripción</th><td>$descripcion</td></tr>";
echo "<tr><th>Unidad de medida</th><td>$unidadMedida</td></tr>";
echo "<tr><th>Cantidad</th><td>$cantidad</td></tr>";
echo "<tr><th>Precio unitario</th><td>$precioUnitario</td></tr>";
echo "<tr><th>Importe renglón</th><td>$importeRenglon</td></tr>";
echo "</table>";
echo "<p><em>La página no se recargó.</em></p>";
