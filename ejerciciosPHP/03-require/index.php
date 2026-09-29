<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PHP 03 — require</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <a class="btn-volver" href="../../index.html">Volver al menú</a>

  <h2>require()</h2>
  <p>require incluye un archivo .php. Si el archivo no existe, PHP corta con fatal error (a diferencia de include, que solo avisa y sigue).</p>
  <p>Los arrays estan declarados en datos.php y se usan aca despues del require.</p>

<?php
  require("./datos.php");

  echo "<p>Agencia: " . NOMBRE_AGENCIA . "</p>";
  echo "<p>Año del curso: " . $anioCurso . "</p>";

  echo "<h3>Destinos (array \$destinos)</h3>";
  echo "<table>";
  echo "<tr><th>Destino</th><th>Precio</th></tr>";
  for ($i = 0; $i < count($destinos); $i++) {
    echo "<tr><td>" . $destinos[$i] . "</td><td>" . $precios[$i] . "</td></tr>";
  }
  echo "</table>";
  echo "<p>Cantidad de destinos: " . count($destinos) . "</p>";
?>
</body>
</html>
