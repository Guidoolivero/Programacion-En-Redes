<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nroFactura'])) {
  header('Content-Type: application/json; charset=utf-8');

  $obj = new stdClass();
  $obj->nroFactura     = $_POST['nroFactura'];
  $obj->fechaFactura   = $_POST['fechaFactura'];
  $obj->codArticulo    = $_POST['codArticulo'];
  $obj->descripcion    = $_POST['descripcion'];
  $obj->cantidad       = (float) $_POST['cantidad'];
  $obj->precioUnitario = (float) $_POST['precioUnitario'];
  $obj->importeRenglon = $obj->cantidad * $obj->precioUnitario;

  echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PHP 09 — Objeto + JSON con AJAX</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <a class="btn-volver" href="../../index.html">Volver al menú</a>

  <h2>Armado de objeto y JSON con AJAX</h2>
  <p>Todo en un solo index.php.</p>

  <form id="formObj">
    <p>Nro Factura: <input type="text" name="nroFactura" required></p>
    <p>Fecha: <input type="date" name="fechaFactura" required></p>
    <p>Cod. artículo: <input type="text" name="codArticulo" required></p>
    <p>Descripción: <input type="text" name="descripcion" required></p>
    <p>Cantidad: <input type="number" name="cantidad" step="0.01" required></p>
    <p>Precio unitario: <input type="number" name="precioUnitario" step="0.01" required></p>
    <p><button type="submit">Generar JSON</button></p>
  </form>

  <div id="resultado"></div>

  <script>
    document.getElementById('formObj').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);

      fetch('index.php', { method: 'POST', body: formData })
        .then(r => r.text())
        .then(json => {
          document.getElementById('resultado').innerHTML =
            '<h3>JSON recibido</h3><pre>' + json + '</pre>';
        })
        .catch(err => document.getElementById('resultado').innerHTML = 'Error: ' + err);
    });
  </script>
</body>
</html>
