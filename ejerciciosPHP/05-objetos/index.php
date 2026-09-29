<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PHP 05 — Objetos</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <a class="btn-volver" href="../../index.html">Volver al menú</a>

  <h2>Variables tipo objeto en PHP. Objeto renglon de pedido</h2>
  <h3 style="color:navy;">$objRenglonPedido</h3>

<?php
  $objRenglonPedido = new stdClass();
  $objRenglonPedido->codArt = "tt101";
  $objRenglonPedido->descripcion = "Paquete Aruba 7 noches";
  $objRenglonPedido->precioUnitario = 890;
  $objRenglonPedido->cantidad = 2;

  echo "Codigo de articulo: " . $objRenglonPedido->codArt . "<br>";
  echo "Descripcion del articulo: " . $objRenglonPedido->descripcion . "<br>";
  echo "Precio unitario: " . $objRenglonPedido->precioUnitario . "<br>";
  echo "Cantidad: " . $objRenglonPedido->cantidad . "<br>";
  echo "<h3>Tipo de \$objRenglonPedido: " . gettype($objRenglonPedido) . "</h3>";

  echo "<h3>Definamos arreglo de pedidos:</h3>";
  echo "<h3 style='color:navy;'>\$renglonesPedido</h3>";

  $objRenglon2 = new stdClass();
  $objRenglon2->codArt = "tt202";
  $objRenglon2->descripcion = "Paquete Barcelona 5 noches";
  $objRenglon2->precioUnitario = 720;
  $objRenglon2->cantidad = 3;

  $renglonesPedido = [];
  array_push($renglonesPedido, $objRenglonPedido);
  array_push($renglonesPedido, $objRenglon2);

  echo "<p>Tipo de \$renglonesPedido: " . gettype($renglonesPedido) . "</p>";
  echo "<h3>Tabula \$renglonesPedido. Recorrer el arreglo de renglones y tabularlos con html:</h3>";
  echo "<table border='1' cellpadding='4'>";
  foreach ($renglonesPedido as $renglon) {
    echo "<tr>";
    echo "<td>" . $renglon->codArt . "</td>";
    echo "<td>" . $renglon->descripcion . "</td>";
    echo "<td>" . $renglon->precioUnitario . "</td>";
    echo "<td>" . $renglon->cantidad . "</td>";
    echo "</tr>";
  }
  echo "</table>";

  $objRenglonesPedido = new stdClass();
  $objRenglonesPedido->renglonesPedido = $renglonesPedido;
  $objRenglonesPedido->cantidadDeRenglones = count($renglonesPedido);

  echo "<h3>Produccion de un objeto \$objRenglonesPedido con dos atributos array renglonesPedido y cantidadDeRenglones</h3>";
  echo "<p>Cantidad de renglones: " . $objRenglonesPedido->cantidadDeRenglones . "</p>";

  $jsonRenglonesPedido = json_encode($objRenglonesPedido, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  echo "<h3>Produccion de un JSON jsonRenglones:</h3>";
  echo "<pre>" . $jsonRenglonesPedido . "</pre>";
?>
</body>
</html>
