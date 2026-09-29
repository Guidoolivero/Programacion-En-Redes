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
  <p>require incluye un archivo. Si el archivo no existe, PHP corta con fatal error (a diferencia de include, que solo avisa y sigue).</p>
<?php
  require("./config.inc");
  echo "<p>" . saludarAgencia(NOMBRE_AGENCIA) . "</p>";
  echo "<p>Año del curso: " . $anioCurso . "</p>";
  echo "<p>Constante NOMBRE_AGENCIA: " . NOMBRE_AGENCIA . "</p>";
?>
</body>
</html>
