<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Unidades de Medida</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; }
    table { border-collapse: collapse; width: 60%; background: white; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #eee; }
    a { display: inline-block; margin-bottom: 12px; }
  </style>
</head>
<body>
  <a href="listar_facturas.php">← Volver a Facturas</a>
  <h2>Unidades de Medida (Cargadas de la BD)</h2>
  <table>
    <tr><th>ID / Código</th><th>Descripción</th></tr>
    <?php if (!empty($unidades)): ?>
      <?php foreach ($unidades as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['codigo'] ?? $u['id'] ?? '') ?></td>
          <td><?= htmlspecialchars($u['descripcion'] ?? $u['nombre'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="2">No se encontraron unidades de medida.</td></tr>
    <?php endif; ?>
  </table>
</body>
</html>
