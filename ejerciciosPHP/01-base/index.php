<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PHP 01 — Base</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <a class="btn-volver" href="../../index.html">Volver al menú</a>

  Esto es texto escrito fuera de las marcas de php.
  Se entrega en la respuesta HTTP sin pasar por el preprocesador php.

  <hr>

<?php
  // Comentario de una línea
  # Otra forma de comentar
  /* Comentario
     de bloque */

  echo "<h4>Texto y/o HTML entregado por el procesador php usando la sentencia echo.</h4>";
  echo "<p class='verde'>Un párrafo color verde (agencia Trading Travel)</p>";
  echo "<hr>";

  $variableA = "valor1";
  echo "El valor de \$variableA es: " . $variableA . "<br>";
  echo "El tipo de \$variableA es: " . gettype($variableA) . "<br>";
  echo "<hr>";

  $variableB = 2;
  $variableC = 3;
  echo "El valor de \$variableB es: " . $variableB . "<br>";
  echo "El tipo de \$variableB es: " . gettype($variableB) . "<br>";
  echo "El valor de \$variableC es: " . $variableC . "<br>";
  echo "El tipo de \$variableC es: " . gettype($variableC) . "<br>";

  echo "<h3 class='titulo-banda'>variableD es la suma de variableB y variableC</h3>";
  echo "<h3 class='titulo-banda'>Si los tipos fueran incompatibles Php devolveria error.</h3>";

  $variableD = ($variableB + $variableC);
  echo "El valor de \$variableD es: " . $variableD . "<br>";
  echo "El tipo de \$variableD es: " . gettype($variableD) . "<br>";
  echo "<hr>";

  $variableE = true;
  $variableF = false;
  echo "variable tipo booleanas o logicas (verdadero) \$variableE : " . $variableE . "<br>";
  echo "El tipo de \$variableE es: " . gettype($variableE) . "<br>";
  echo "variable tipo booleanas o logicas (falso) \$variableF : " . $variableF . "<br>";
  echo "El tipo de \$variableF es: " . gettype($variableF) . "<br>";
  echo "<hr>";

  define("MICONSTANTE", "TradingTravel2026");
  echo "MICONSTANTE : " . MICONSTANTE . "<br>";
  echo "Tipo de MICONSTANTE: " . gettype(MICONSTANTE) . "<br>";
  echo "<hr>";

  echo "<h3>Arreglos:</h3>";
  $aSaludo = array("hola", "hello");
  echo "\$aSaludo[0]: " . $aSaludo[0] . "<br>";
  echo "\$aSaludo[1]: " . $aSaludo[1] . "<br>";
  echo "Tipo de \$aSaludo : " . gettype($aSaludo) . "<br>";

  echo "<p>Se agregan por programa dos elementos nuevos</p>";
  array_push($aSaludo, "ciao");
  array_push($aSaludo, "bonjour");

  echo "<p>Todos los elementos originales y agregados:</p>";
  echo "<ul>";
  foreach ($aSaludo as $saludo) {
    echo "<li>" . $saludo . "</li>";
  }
  echo "</ul>";

  echo "<h3>Arreglo de dos dimensiones (diccionario)</h3>";
  $aDiccionarioBasico = [
    ["hola", "hello", "ciao", "bonjour"],
    ["adios", "goodbye", "arrivederci", "au revoir"],
    ["viaje", "trip", "viaggio", "voyage"]
  ];

  echo "La variable \$aDiccionarioBasico tiene el siguiente tipo: " . gettype($aDiccionarioBasico) . "<br>";

  echo "<table>";
  echo "<tr><th>Español</th><th>Ingles</th><th>Italiano</th><th>Francés</th></tr>";
  foreach ($aDiccionarioBasico as $fila) {
    echo "<tr>";
    foreach ($fila as $celda) {
      echo "<td>" . $celda . "</td>";
    }
    echo "</tr>";
  }
  echo "</table>";

  echo "<p>Valor de \$aDiccionarioBasico[1][3]: " . $aDiccionarioBasico[1][3] . "</p>";
  echo "<p>Cantidad de elementos de diccionario: " . count($aDiccionarioBasico) . "</p>";

  echo "<h3>Variables tipo arreglo asociativo</h3>";
  echo "<h3 class='titulo-banda'>Cargar un arreglo asociativo y mostrar atributos, cantidad y tipos</h3>";

  $renglonReserva = [
    "codPaquete" => "tt101",
    "destino" => "Aruba",
    "pasajeros" => 4,
    "fechaSalida" => "20/12/2026"
  ];

  echo "Codigo de paquete: " . $renglonReserva["codPaquete"] . "<br>";
  echo "tipo del elemento: " . gettype($renglonReserva["codPaquete"]) . "<br>";
  echo "Destino: " . $renglonReserva["destino"] . "<br>";
  echo "tipo del elemento: " . gettype($renglonReserva["destino"]) . "<br>";
  echo "Pasajeros: " . $renglonReserva["pasajeros"] . "<br>";
  echo "tipo del elemento: " . gettype($renglonReserva["pasajeros"]) . "<br>";
  echo "Fecha de salida: " . $renglonReserva["fechaSalida"] . "<br>";
  echo "tipo del elemento: " . gettype($renglonReserva["fechaSalida"]) . "<br>";
  echo "Cantidad de elementos del arreglo: " . count($renglonReserva) . "<br>";
  echo "Tipo de dato del arreglo: " . gettype($renglonReserva) . "<br>";
  echo "<hr>";

  echo "<h3>Expresiones aritmeticas</h3>";
  $x = 3;
  $y = 4;
  echo "La variable \$x tiene el siguiente valor: " . $x . "<br>";
  echo "La variable \$y tiene el siguiente valor: " . $y . "<br>";
  echo "La variable \$x tiene el siguiente tipo: " . gettype($x) . "<br>";
  echo "La variable \$y tiene el siguiente tipo: " . gettype($y) . "<br>";
  echo "Suma (\$x + \$y) = " . ($x + $y) . "<br>";
  echo "Multiplicacion \$x * \$y = " . ($x * $y) . "<br>";
  echo "Division \$x / \$y = " . ($x / $y) . "<br>";
  echo "<hr>";

  echo "<h3>Alcances de las variables</h3>";
  echo "<p>Las variables definidas fuera de una funcion tienen alcance global y se referencian en \$GLOBALS.</p>";
  $n1 = 40;
  $n2 = 50;
  echo "El valor de \$n1 es: " . $n1 . "<br>";
  echo "El valor de \$n2 es: " . $n2 . "<br>";
  echo "Suma en ambito global \$GLOBALS['n1'] + \$GLOBALS['n2']: " . ($GLOBALS["n1"] + $GLOBALS["n2"]) . "<br>";
  echo "<p>Las variables declaradas dentro de una funcion solo existen dentro de esa funcion.</p>";
?>
</body>
</html>
