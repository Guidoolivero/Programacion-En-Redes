<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PHP 04 — $_SERVER</title>
  <style>
    table { border-collapse: collapse; margin-bottom: 20px; }
    td, th { border: 1px solid #999; padding: 6px 12px; }
    th { background: #e8f0d8; text-align: left; }
  </style>
</head>
<body style="padding-top:56px;font-family:Arial,sans-serif;">
  <a href="../../index.html" style="position:fixed;top:12px;left:12px;z-index:9999;display:inline-block;padding:8px 14px;background:#2b2b2b;color:#fff;text-decoration:none;font-size:13px;border-radius:4px;">Volver al menú</a>
  <h2>Variables de servidor</h2>
  <table>
    <tr><th>SERVER_ADDR</th><td><?php echo $_SERVER["SERVER_ADDR"]; ?></td></tr>
    <tr><th>SERVER_PORT</th><td><?php echo $_SERVER["SERVER_PORT"]; ?></td></tr>
    <tr><th>SERVER_NAME</th><td><?php echo $_SERVER["SERVER_NAME"]; ?></td></tr>
    <tr><th>HTTP_HOST</th><td><?php echo $_SERVER["HTTP_HOST"]; ?></td></tr>
    <tr><th>DOCUMENT_ROOT</th><td><?php echo $_SERVER["DOCUMENT_ROOT"]; ?></td></tr>
  </table>
  <h2>Variables de cliente</h2>
  <table>
    <tr><th>REMOTE_ADDR</th><td><?php echo $_SERVER["REMOTE_ADDR"]; ?></td></tr>
    <tr><th>REMOTE_PORT</th><td><?php echo isset($_SERVER["REMOTE_PORT"]) ? $_SERVER["REMOTE_PORT"] : "-"; ?></td></tr>
  </table>
  <h2>Variables de Requerimiento</h2>
  <table>
    <tr><th>SCRIPT_NAME</th><td><?php echo $_SERVER["SCRIPT_NAME"]; ?></td></tr>
    <tr><th>REQUEST_METHOD</th><td><?php echo $_SERVER["REQUEST_METHOD"]; ?></td></tr>
    <tr><th>REQUEST_URI</th><td><?php echo $_SERVER["REQUEST_URI"]; ?></td></tr>
    <tr><th>QUERY_STRING</th><td><?php echo $_SERVER["QUERY_STRING"]; ?></td></tr>
  </table>
  <h2>TODAS</h2>
<?php
  foreach ($_SERVER as $clave => $valor) {
    if (is_array($valor)) {
      $valor = implode(", ", $valor);
    }
    echo $clave . "=" . $valor . "<br>";
  }
?>
</body>
</html>
