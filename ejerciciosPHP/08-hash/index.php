<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clave'])) {
  $clave = $_POST['clave'];
  $md5   = md5($clave);
  $sha1  = sha1($clave);

  echo "<h3>Resultados</h3>";
  echo "<p>Original: <strong>" . htmlspecialchars($clave) . "</strong></p>";
  echo "<p>md5: <code>$md5</code></p>";
  echo "<p>sha1: <code>$sha1</code></p>";
  echo "<p><em>Respondido por AJAX.</em></p>";
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PHP 08 — Hash AJAX en un solo archivo</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <a class="btn-volver" href="../../index.html">Volver al menú</a>

  <h2>Encriptación unidireccional con AJAX</h2>
  <p>md5() y sha1() — todo en un solo index.php.</p>

  <form id="formHash">
    <p>Texto o clave: <input type="text" name="clave" required></p>
    <p><button type="submit">Calcular hash</button></p>
  </form>

  <div id="resultado"></div>

  <script>
    document.getElementById('formHash').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);

      fetch('index.php', { method: 'POST', body: formData })
        .then(r => r.text())
        .then(html => document.getElementById('resultado').innerHTML = html)
        .catch(err => document.getElementById('resultado').innerHTML = 'Error: ' + err);
    });
  </script>
</body>
</html>
