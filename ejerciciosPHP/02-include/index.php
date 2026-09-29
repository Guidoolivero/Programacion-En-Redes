<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PHP 02 — include</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <a class="btn-volver" href="../../index.html">Volver al menú</a>
  <h3>Antes de insertar el include las variables declaradas en el mismo no existen</h3>
  <h3>Pero a pesar de ello el ciclo de ejecución continuará hasta el final</h3>
  <p>Las variables son:</p>
<?php
  echo $arreglo1[0];
  echo $arreglo1[1];
  echo $arreglo1[2];
  echo $arreglo2[0];
  echo $arreglo2[1];
  echo $arreglo2[2];
?>
  <hr>
  <p>En este punto se ejecuta la función include(). Cuando se usa include ocurre que si el archivo asociado no existe, se visualiza un warning y el script sigue ejecutandose hasta el final</p>
  <p>Las 2 variables de tipo array en el archivo asociado son:</p>
<?php
  include("./pasajeros.inc");
  echo "<table border='1' cellpadding='4'>";
  echo "<tr><td>" . $arreglo1[0] . "</td><td>" . $arreglo1[1] . "</td><td>" . $arreglo1[2] . "</td></tr>";
  echo "<tr><td>" . $arreglo2[0] . "</td><td>" . $arreglo2[1] . "</td><td>" . $arreglo2[2] . "</td></tr>";
  echo "</table>";
  echo "<p>La longitud del arreglo1 es : " . count($arreglo1) . "</p>";
  echo "<p>La longitud del arreglo2 es : " . count($arreglo2) . "</p>";
?>
</body>
</html>
