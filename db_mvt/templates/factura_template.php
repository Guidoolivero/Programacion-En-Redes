<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>MVT — Facturación & Base de Datos con AJAX</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; background: #f9f9f9; }
    h1, h2 { color: #333; }
    .btn-volver { display: inline-block; margin-bottom: 15px; padding: 6px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
    form { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); max-width: 500px; }
    form p { margin-bottom: 12px; }
    label { display: block; font-weight: bold; margin-bottom: 4px; }
    input, select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
    button { background: #28a745; color: white; padding: 10px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
    button:hover { background: #218838; }
    button:disabled { background: #6c757d; cursor: not-allowed; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
    th, td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
    th { background: #f2f2f2; }
    #progreso { display: none; margin-top: 15px; padding: 12px; background: #e8f4fd; border: 1px solid #b8daff; border-radius: 4px; color: #004085; font-weight: bold; }
    #resultado { margin-top: 20px; }
  </style>
</head>
<body>
  <a class="btn-volver" href="../index.html">Volver al menú</a>
  <h1>Patrón MVT — Módulo Facturación</h1>
  <p>Alumno: Guido Olivero — Base de datos: <code>if0_42934198_facturacion</code></p>

  <h2>Formulario de Carga (Template / AJAX con retardo de 5s)</h2>
  <form id="formRenglon">
    <p>
      <label>Nro Factura:</label>
      <input type="text" name="nroFactura" required>
    </p>
    <p>
      <label>Fecha Factura:</label>
      <input type="date" name="fechaFactura" required>
    </p>
    <p>
      <label>Cód. Artículo:</label>
      <input type="text" name="codArticulo" required>
    </p>
    <p>
      <label>Descripción:</label>
      <input type="text" name="descripcion" required>
    </p>
    <p>
      <label>Unidad de Medida (Cargada desde BD):</label>
      <select name="unidadMedida">
        <?php if (!empty($unidades)): ?>
          <?php foreach ($unidades as $u): ?>
            <option value="<?= htmlspecialchars($u['codigo'] ?? $u['id'] ?? $u['descripcion'] ?? $u['unidad_medida']) ?>">
              <?= htmlspecialchars($u['descripcion'] ?? $u['nombre'] ?? $u['codigo'] ?? 'Unidad') ?>
            </option>
          <?php endforeach; ?>
        <?php else: ?>
          <option value="un">Unidad</option>
          <option value="kg">Kilogramo</option>
          <option value="lt">Litro</option>
          <option value="m">Metro</option>
        <?php endif; ?>
      </select>
    </p>
    <p>
      <label>Cantidad:</label>
      <input type="number" name="cantidad" step="0.01" required>
    </p>
    <p>
      <label>Precio Unitario:</label>
      <input type="number" name="precioUnitario" step="0.01" required>
    </p>
    <p>
      <button type="submit" id="btnEnviar">Enviar con AJAX</button>
    </p>
  </form>

  <div id="progreso">
    ⏳ Demostrando tiempo de envío... Enviando petición en <span id="segundos">5</span> segundos...
  </div>

  <div id="resultado"></div>

  <h2>Renglones Existentes en BD</h2>
  <table>
    <thead>
      <tr>
        <th>Nro Factura</th>
        <th>Fecha</th>
        <th>Cód. Art.</th>
        <th>Descripción</th>
        <th>U. Medida</th>
        <th>Cantidad</th>
        <th>Precio Unitario</th>
        <th>Importe</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($renglones)): ?>
        <?php foreach ($renglones as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['nroFactura'] ?? $r['nro_factura'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['fechaFactura'] ?? $r['fecha'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['codArticulo'] ?? $r['cod_articulo'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['descripcion'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['unidadMedida'] ?? $r['unidad_medida'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['cantidad'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['precioUnitario'] ?? $r['precio_unitario'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['importeRenglon'] ?? $r['importe'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="8" style="text-align:center;">No hay renglones cargados aún o sin datos disponibles.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <script>
    document.getElementById('formRenglon').addEventListener('submit', function(e) {
      e.preventDefault();

      const btn = document.getElementById('btnEnviar');
      const progreso = document.getElementById('progreso');
      const segSpan = document.getElementById('segundos');
      const resultado = document.getElementById('resultado');
      const formData = new FormData(this);

      // Deshabilitar botón y limpiar resultado anterior
      btn.disabled = true;
      resultado.innerHTML = '';
      progreso.style.display = 'block';

      let tiempoRestante = 5;
      segSpan.textContent = tiempoRestante;

      // Timer visual de cuenta regresiva de 5 segundos
      const intervalo = setInterval(() => {
        tiempoRestante--;
        if (tiempoRestante > 0) {
          segSpan.textContent = tiempoRestante;
        } else {
          clearInterval(intervalo);
          segSpan.textContent = '0 (conectando y enviando por AJAX...)';

          // Disparo de la petición asincrónica AJAX (fetch) tras los 5 segundos
          fetch('index.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.text())
          .then(html => {
            progreso.style.display = 'none';
            btn.disabled = false;
            resultado.innerHTML = html;
          })
          .catch(error => {
            progreso.style.display = 'none';
            btn.disabled = false;
            resultado.innerHTML = '<p style="color:red;">Error de conexión: ' + error + '</p>';
          });
        }
      }, 1000);
    });
  </script>
</body>
</html>
