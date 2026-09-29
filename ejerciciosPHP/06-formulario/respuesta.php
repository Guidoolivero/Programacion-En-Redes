<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Respuesta formulario</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <a class="btn-volver" href="../../index.html">Volver al menú</a>
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
