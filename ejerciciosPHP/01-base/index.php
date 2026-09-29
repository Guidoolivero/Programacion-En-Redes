<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PHP 01 — Base</title>
</head>
<body style="padding-top:56px;font-family:Arial,sans-serif;">
  <a href="../../index.html" style="position:fixed;top:12px;left:12px;z-index:9999;display:inline-block;padding:8px 14px;background:#2b2b2b;color:#fff;text-decoration:none;font-size:13px;border-radius:4px;">Volver al menú</a>
  Esto es texto escrito fuera de las marcas de php. Se entrega en la respuesta HTTP sin pasar por el preprocesador php.
  <hr>
<?php
  echo "<h4>Texto y/o HTML entregado por el procesador php usando la sentencia echo.</h4>";
  echo "<p style='color:green'>Un párrafo color verde (agencia Trading Travel)</p>";
  echo "<hr />";
  $variableA = "valor1";
  echo "El valor de \$variableA es: " . $variableA . "<br>";
  echo "El tipo de \$variableA es: " . gettype($variableA) . "<br><hr>";
  $variableB = 2;
  $variableC = 3;
  echo "El valor de \$variableB es: " . $variableB . "<br>";
  echo "El tipo de \$variableB es: " . gettype($variableB) . "<br>";
  echo "El valor de \$variableC es: " . $variableC . "<br>";
  echo "El tipo de \$variableC es: " . gettype($variableC) . "<br>";
  echo "<h3 style='background:#b8d4e8;color:navy;text-align:center;padding:8px;'>variableD es la suma de variableB y variableC</h3>";
  $variableD = ($variableB + $variableC);
  echo "El valor de \$variableD es: " . $variableD . "<br>";
  echo "El tipo de \$variableD es: " . gettype($variableD) . "<br><hr>";
  $variableE = true;
  $variableF = false;
  echo "variable tipo booleanas o logicas (verdadero) \$variableE : " . $variableE . "<br>";
  echo "El tipo de \$variableE es: " . gettype($variableE) . "<br>";
  echo "variable tipo booleanas o logicas (falso) \$variableF : " . $variableF . "<br>";
  echo "El tipo de \$variableF es: " . gettype($variableF) . "<br><hr>";
  define("MICONSTANTE", "TradingTravel2026");
  echo "MICONSTANTE : " . MICONSTANTE . "<br>";
  echo "Tipo de MICONSTANTE: " . gettype(MICONSTANTE) . "<br><hr>";
  echo "<h3>Arreglos:</h3>";
  $aSaludo = array("hola", "hello");
  echo "\$aSaludo[0]:" . $aSaludo[0] . "<br>";
  echo "\$aSaludo[1]:" . $aSaludo[1] . "<br>";
  echo "Tipo de \$aSaludo : " . gettype($aSaludo) . "<br>";
  array_push($aSaludo, "ciao");
  array_push($aSaludo, "bonjour");
  echo "<p>Todos los elementos originales y agregados:</p><ul>";
  foreach ($aSaludo as $saludo) { echo "<li>" . $saludo . "</li>"; }
  echo "</ul>";
  echo "<h3>Arreglo de dos dimensiones (diccionario)</h3>";
  $aDiccionarioBasico = [
    ["hola", "hello", "ciao", "bonjour"],
    ["adios", "goodbye", "arrivederci", "au revoir"],
    ["viaje", "trip", "viaggio", "voyage"]
  ];
  echo "La variable \$aDiccionarioBasico tiene el siguiente tipo: " . gettype($aDiccionarioBasico) . "<br>";
  echo "<table border='1' cellpadding='6' cellspacing='0'>";
  echo "<tr><th>Español</th><th>Ingles</th><th>Italiano</th><th>Francés</th></tr>";
  foreach ($aDiccionarioBasico as $fila) {
    echo "<tr>";
    foreach ($fila as $celda) { echo "<td>" . $celda . "</td>"; }
    echo "</tr>";
  }
  echo "</table>";
  echo "<p>Tambien asi se puede expresar el valor de \$aDiccionarioBasico[1][3]: " . $aDiccionarioBasico[1][3] . "</p>";
  echo "<p>Cantidad de elementos de diccionario: " . count($aDiccionarioBasico) . "</p>";
  echo "<h3>Variables tipo arreglo asociativo</h3>";
  $renglonReserva = ["codPaquete" => "tt101", "destino" => "Aruba", "pasajeros" => 4, "fechaSalida" => "20/12/2026"];
  echo "Codigo de paquete: " . $renglonReserva["codPaquete"] . "<br>";
  echo "tipo del elemento: " . gettype($renglonReserva["codPaquete"]) . "<br>";
  echo "Destino: " . $renglonReserva["destino"] . "<br>";
  echo "tipo del elemento: " . gettype($renglonReserva["destino"]) . "<br>";
  echo "Pasajeros: " . $renglonReserva["pasajeros"] . "<br>";
  echo "tipo del elemento: " . gettype($renglonReserva["pasajeros"]) . "<br>";
  echo "Fecha de salida: " . $renglonReserva["fechaSalida"] . "<br>";
  echo "tipo del elemento: " . gettype($renglonReserva["fechaSalida"]) . "<br>";
  echo "Cantidad de elementos del arreglo: " . count($renglonReserva) . "<br>";
  echo "Tipo de dato del arreglo: " . gettype($renglonReserva) . "<br><hr>";
  echo "<h3>Expresiones aritmeticas</h3>";
  $x = 3; $y = 4;
  echo "La variable \$x tiene el siguiente valor: " . $x . "<br>";
  echo "La variable \$y tiene el siguiente valor: " . $y . "<br>";
  echo "Asi se imprime una expresión aritmetica por ejemplo de Suma: (\$x + \$y) = " . ($x + $y) . "<br>";
  echo "Asi se imprime una expresión aritmetica por ejemplo de Multiplicación: \$x * \$y = " . ($x * $y) . "<br>";
  echo "Asi se imprime una expresión aritmetica por ejemplo de División: \$x / \$y = " . ($x / $y) . "<br><hr>";
  echo "<h3>Alcances de las variables:</h3>";
  $n1 = 40; $n2 = 50;
  echo "El valor de \$n1 es: " . $n1 . "<br>";
  echo "El valor de \$n2 es: " . $n2 . "<br>";
  echo "Suma de variables en el ambito global: \$GLOBALS['n1']+\$GLOBALS['n2']: " . ($GLOBALS["n1"] + $GLOBALS["n2"]) . "<br>";
?>
</body>
</html>
