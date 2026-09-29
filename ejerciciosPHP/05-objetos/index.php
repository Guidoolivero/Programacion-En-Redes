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
  <h3 class="titulo-navy">$objRenglonPedido</h3>
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
  $objRenglon2 = new stdClass();
  $objRenglon2->codArt = "tt202";
  $objRenglon2->descripcion = "Paquete Barcelona 5 noches";
  $objRenglon2->precioUnitario = 720;
  $objRenglon2->cantidad = 3;
  $renglonesPedido = [];
  array_push($renglonesPedido, $objRenglonPedido);
  array_push($renglonesPedido, $objRenglon2);
  echo "<p>Tipo de \$renglonesPedido: " . gettype($renglonesPedido) . "</p>";
  echo "<table border='1' cellpadding='4'>";
  foreach ($renglonesPedido as $renglon) {
    echo "<tr><td>" . $renglon->codArt . "</td><td>" . $renglon->descripcion . "</td><td>" . $renglon->precioUnitario . "</td><td>" . $renglon->cantidad . "</td></tr>";
  }
  echo "</table>";
  $objRenglonesPedido = new stdClass();
  $objRenglonesPedido->renglonesPedido = $renglonesPedido;
  $objRenglonesPedido->cantidadDeRenglones = count($renglonesPedido);
  echo "<p>Cantidad de renglones: " . $objRenglonesPedido->cantidadDeRenglones . "</p>";
  echo "<h3>Produccion de un JSON jsonRenglones:</h3>";
  echo json_encode($objRenglonesPedido);
?>
</body>
</html>
