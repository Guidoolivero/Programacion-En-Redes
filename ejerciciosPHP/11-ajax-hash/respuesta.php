<?php
if (!isset($_POST['clave'])) {
  echo "<p>Falta la clave.</p>";
  exit;
}
$clave = $_POST['clave'];
$md5   = md5($clave);
$sha1  = sha1($clave);

echo "<h3>Resultados</h3>";
echo "<p>Original: <strong>" . htmlspecialchars($clave) . "</strong></p>";
echo "<p>md5: <code>$md5</code></p>";
echo "<p>sha1: <code>$sha1</code></p>";
echo "<p><em>Respondido por AJAX.</em></p>";
