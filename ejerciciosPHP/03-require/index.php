<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PHP 03 — require</title>
</head>
<body style="padding-top:56px;font-family:Arial,sans-serif;">
  <a href="../../index.html" style="position:fixed;top:12px;left:12px;z-index:9999;display:inline-block;padding:8px 14px;background:#2b2b2b;color:#fff;text-decoration:none;font-size:13px;border-radius:4px;">Volver al menú</a>
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
