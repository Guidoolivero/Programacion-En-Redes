<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Respuesta formulario</title>
</head>
<body style="padding-top:56px;font-family:Arial,sans-serif;">
  <a href="../../index.html" style="position:fixed;top:12px;left:12px;z-index:9999;display:inline-block;padding:8px 14px;background:#2b2b2b;color:#fff;text-decoration:none;font-size:13px;border-radius:4px;">Volver al menú</a>
  <h2>Datos recibidos</h2>
<?php
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "Metodo: POST<br>";
    echo "Nombre = " . $_POST["nombre"] . "<br>";
    echo "Apellido = " . $_POST["apellido"] . "<br>";
  } else {
    echo "Metodo: GET<br>";
    echo "Nombre = " . $_GET["nombre"] . "<br>";
    echo "Apellido = " . $_GET["apellido"] . "<br>";
  }
?>
  <p><a href="index.html">Volver al formulario</a></p>
</body>
</html>
