<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nroFactura'])) {
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
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PHP 07 — Formulario AJAX en un solo archivo</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <a class="btn-volver" href="../../index.html">Volver al menú</a>

  <h2>Renglones de factura de ventas — AJAX en un solo PHP</h2>
  <p>Alumno: Guido Olivero — Tabla 11.</p>

  <form id="formRenglon">
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
    <p><button type="submit">Enviar con AJAX</button></p>
  </form>

  <div id="resultado"></div>

  <script>
    document.getElementById('formRenglon').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);

      fetch('index.php', { method: 'POST', body: formData })
        .then(respuesta => respuesta.text())
        .then(html => {
          document.getElementById('resultado').innerHTML = html;
        })
        .catch(error => {
          document.getElementById('resultado').innerHTML = '<p style="color:red;">Error: ' + error + '</p>';
        });
    });
  </script>
</body>
</html>
