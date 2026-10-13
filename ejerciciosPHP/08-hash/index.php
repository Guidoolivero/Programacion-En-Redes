<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PHP 08 — Hash (md5 / sha1)</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <a class="btn-volver" href="../../index.html">Volver al menú</a>

  <h2>Encriptación unidireccional</h2>
  <p>md5() y sha1() — no se pueden desencriptar.</p>

<?php
if (isset($_POST['clave'])) {
  $clave = $_POST['clave'];
  $md5   = md5($clave);
  $sha1  = sha1($clave);

  echo "<h3>Resultados</h3>";
  echo "<p>Clave original: <strong>" . htmlspecialchars($clave) . "</strong></p>";
  echo "<p>md5 (32 caracteres hex): <code>$md5</code></p>";
  echo "<p>sha1 (40 caracteres hex): <code>$sha1</code></p>";
  echo "<p><a href='index.php'>Probar otra</a></p>";
} else {
  ?>
  <form method="post" action="index.php">
    <p>Ingresá una clave o texto:</p>
    <p><input type="text" name="clave" required></p>
    <p><input type="submit" value="Encriptar"></p>
  </form>
  <?php
}
?>
</body>
</html>
