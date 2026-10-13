<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PHP 09 — Objeto desde formulario + JSON</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <a class="btn-volver" href="../../index.html">Volver al menú</a>

  <h2>Armado de objeto con datos de formulario y JSON</h2>

<?php
if (isset($_POST['nroFactura'])) {
  $objRenglon = new stdClass();
  $objRenglon->nroFactura     = $_POST['nroFactura'];
  $objRenglon->fechaFactura   = $_POST['fechaFactura'];
  $objRenglon->codArticulo    = $_POST['codArticulo'];
  $objRenglon->descripcion    = $_POST['descripcion'];
  $objRenglon->cantidad       = (float) $_POST['cantidad'];
  $objRenglon->precioUnitario = (float) $_POST['precioUnitario'];
  $objRenglon->importeRenglon = $objRenglon->cantidad * $objRenglon->precioUnitario;

  echo "<h3>Objeto creado</h3>";
  echo "<pre>";
  print_r($objRenglon);
  echo "</pre>";

  $json = json_encode($objRenglon, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  echo "<h3>JSON</h3>";
  echo "<pre>$json</pre>";

  echo "<p><a href='index.php'>Nuevo</a></p>";
} else {
  ?>
  <form method="post" action="index.php">
    <p>Nro Factura: <input type="text" name="nroFactura" required></p>
    <p>Fecha: <input type="date" name="fechaFactura" required></p>
    <p>Cod. artículo: <input type="text" name="codArticulo" required></p>
    <p>Descripción: <input type="text" name="descripcion" required></p>
    <p>Cantidad: <input type="number" name="cantidad" step="0.01" required></p>
    <p>Precio unitario: <input type="number" name="precioUnitario" step="0.01" required></p>
    <p><input type="submit" value="Generar objeto y JSON"></p>
  </form>
  <?php
}
?>
</body>
</html>
