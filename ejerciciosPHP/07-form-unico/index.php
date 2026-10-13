<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PHP 07 — Formulario en un solo archivo</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <a class="btn-volver" href="../../index.html">Volver al menú</a>

  <h2>Renglones de factura de ventas — formulario en un solo PHP</h2>
  <p>Alumno: Guido Olivero — Tabla 11</p>

<?php
if (isset($_POST['nroFactura'])) {
  $nroFactura     = htmlspecialchars($_POST['nroFactura']);
  $fechaFactura   = htmlspecialchars($_POST['fechaFactura']);
  $codArticulo    = htmlspecialchars($_POST['codArticulo']);
  $descripcion    = htmlspecialchars($_POST['descripcion']);
  $unidadMedida   = htmlspecialchars($_POST['unidadMedida']);
  $cantidad       = htmlspecialchars($_POST['cantidad']);
  $precioUnitario = htmlspecialchars($_POST['precioUnitario']);
  $importeRenglon = $cantidad * $precioUnitario;

  echo "<h3>Datos recibidos</h3>";
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
  echo "<p><a href='index.php'>Cargar otro renglón</a></p>";
} else {
  ?>
  <form method="post" action="index.php">
    <p>Nro Factura: <input type="text" name="nroFactura" required></p>
    <p>Fecha Factura: <input type="date" name="fechaFactura" required></p>
    <p>Cod. artículo: <input type="text" name="codArticulo" required></p>
    <p>Descripción: <input type="text" name="descripcion" required></p>
    <p>Unidad de medida:
      <select name="unidadMedida">
        <option value="un">Unidad</option>
        <option value="kg">Kilogramo</option>
        <option value="lt">Litro</option>
        <option value="m">Metro</option>
      </select>
    </p>
    <p>Cantidad: <input type="number" name="cantidad" step="0.01" required></p>
    <p>Precio unitario: <input type="number" name="precioUnitario" step="0.01" required></p>
    <p><input type="submit" value="Guardar renglón"></p>
  </form>
  <?php
}
?>
</body>
</html>
