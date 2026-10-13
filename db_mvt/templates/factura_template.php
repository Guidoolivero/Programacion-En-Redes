<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>MVT — Facturación & Base de Datos con AJAX</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; background: #f0f2f5; }
    h1, h2 { color: #333; }
    .btn-volver { display: inline-block; margin-bottom: 15px; padding: 6px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; }
    .nav-vistas { margin-bottom: 20px; }
    .nav-vistas a { margin-right: 15px; color: #007bff; text-decoration: none; font-weight: bold; }
    form { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); max-width: 500px; }
    form p { margin-bottom: 12px; }
    label { display: block; font-weight: bold; margin-bottom: 4px; color: #444; }
    input, select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
    input:invalid { border-color: #dc3545; }
    button { background: #28a745; color: white; padding: 10px 18px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold; }
    button:hover { background: #218838; }
    button:disabled { background: #6c757d; cursor: not-allowed; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
    th, td { border: 1px solid #e2e8f0; padding: 10px 12px; text-align: left; }
    th { background: #f8fafc; font-weight: bold; color: #334155; }
    
    /* Ventana y Barra de progreso estilo retro Windows XP */
    #progreso-container {
      display: none;
      margin-top: 20px;
      max-width: 490px;
      background: #ece9d8;
      border: 2px solid #0055ea;
      border-radius: 6px 6px 4px 4px;
      box-shadow: 3px 3px 10px rgba(0,0,0,0.3);
      font-family: 'Tahoma', 'Segoe UI', sans-serif;
      overflow: hidden;
    }
    .win-header {
      background: linear-gradient(180deg, #0a246a 0%, #a6caf0 10%, #0055ea 30%, #0040b0 100%);
      color: white;
      padding: 5px 10px;
      font-weight: bold;
      font-size: 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .win-body {
      padding: 15px;
      background: #ece9d8;
      color: #000;
      font-size: 13px;
    }
    .win-body p {
      margin: 0 0 10px 0;
    }
    .win-progress-track {
      background: #fff;
      border: 1px solid #7f9db9;
      height: 22px;
      padding: 2px;
      box-sizing: border-box;
      display: flex;
      align-items: center;
      gap: 3px;
      overflow: hidden;
    }
    .win-block {
      width: 10px;
      height: 16px;
      background: linear-gradient(180deg, #6ee26e 0%, #30ab30 50%, #157915 100%);
      border-radius: 1px;
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
    }
    .win-footer-text {
      margin-top: 8px !important;
      font-size: 11px;
      color: #444;
      text-align: right;
    }

    #resultado { margin-top: 25px; }
    .error-box { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; border: 1px solid #f5c6cb; margin-bottom: 15px; }
  </style>
</head>
<body>
  <a class="btn-volver" href="../index.html">Volver al menú</a>
  <h1>Patrón MVT — Módulo Facturación</h1>
  <p>Alumno: Guido Olivero — Base de datos: <code>if0_42934198_facturacion</code></p>

  <div class="nav-vistas">
    <span>Vistas modulares:</span>
    <a href="listar_facturas.php">Vista: Listar Facturas</a>
    <a href="listar_unidades.php">Vista: Unidades de Medida</a>
  </div>

  <h2>Formulario de Carga (Template / Validaciones + AJAX)</h2>
  <form id="formRenglon">
    <p>
      <label>Nro Factura (Solo enteros positivos):</label>
      <input type="number" name="nroFactura" min="1" step="1" required placeholder="Ej: 1001">
    </p>
    <p>
      <label>Fecha Factura:</label>
      <input type="date" name="fechaFactura" required>
    </p>
    <p>
      <label>Cód. Artículo:</label>
      <input type="text" name="codArticulo" maxlength="50" required placeholder="Ej: ART-01">
    </p>
    <p>
      <label>Descripción:</label>
      <input type="text" name="descripcion" maxlength="255" required placeholder="Ej: Monitor LED 24 pulgadas">
    </p>
    <p>
      <label>Unidad de Medida (Cargada desde BD):</label>
      <select name="unidadMedida" required>
        <option value="" disabled selected>-- Seleccione unidad --</option>
        <?php if (!empty($unidades)): ?>
          <?php foreach ($unidades as $u): ?>
            <?php 
              $val = $u['codigo'] ?? $u['codUm'] ?? $u['cod_um'] ?? $u['id'] ?? $u['descripcion'] ?? $u['unidad_medida'] ?? '';
              $nom = $u['descripcion'] ?? $u['descripcionUm'] ?? $u['nombre'] ?? $u['codigo'] ?? 'Unidad';
            ?>
            <option value="<?= htmlspecialchars($val) ?>">
              <?= htmlspecialchars($nom) ?>
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
      <label>Cantidad (Mayor a 0):</label>
      <input type="number" name="cantidad" min="0.01" step="0.01" required placeholder="Ej: 5">
    </p>
    <p>
      <label>Precio Unitario (Mayor a 0):</label>
      <input type="number" name="precioUnitario" min="0.01" step="0.01" required placeholder="Ej: 150.50">
    </p>
    <p>
      <button type="submit" id="btnEnviar">Enviar con AJAX</button>
    </p>
  </form>

  <!-- Barra de progreso estilo Windows XP Retro -->
  <div id="progreso-container">
    <div class="win-header">
      <span>Transmitiendo datos al servidor...</span>
      <span>[ _ □ ✕ ]</span>
    </div>
    <div class="win-body">
      <p>Demostrando latencia de red. Enviando en <b id="segundos">5</b> segundos...</p>
      <div class="win-progress-track" id="xp-track"></div>
      <p class="win-footer-text" id="win-status">Conectando con base de datos remota...</p>
    </div>
  </div>

  <!-- Contenedor donde aparece la respuesta del renglón cargado por AJAX -->
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
    <tbody id="tablaRenglones">
      <?php if (!empty($renglones)): ?>
        <?php foreach ($renglones as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['nroFactura'] ?? $r['nro_factura'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['fechaFactura'] ?? $r['fecha'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['codArticulo'] ?? $r['cod_articulo'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['descripcion'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['unidadMedida'] ?? $r['unidad_medida'] ?? $r['codUm'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['cantidad'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['precioUnitario'] ?? $r['precio_unitario'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['importeRenglon'] ?? $r['importe_renglon'] ?? $r['importe'] ?? '') ?></td>
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

      if (!this.checkValidity()) {
        this.reportValidity();
        return;
      }

      const form = this;
      const btn = document.getElementById('btnEnviar');
      const container = document.getElementById('progreso-container');
      const segSpan = document.getElementById('segundos');
      const track = document.getElementById('xp-track');
      const winStatus = document.getElementById('win-status');
      const resultado = document.getElementById('resultado');
      const formData = new FormData(this);

      btn.disabled = true;
      resultado.innerHTML = '';
      container.style.display = 'block';
      track.innerHTML = '';

      let tiempoTotal = 5;
      let tiempoRestante = 5;
      segSpan.textContent = tiempoRestante;

      // Animación de los bloques verdes característicos de Windows XP
      const totalBloques = 32;
      let bloquesActuales = 0;

      const animacionXP = setInterval(() => {
        if (bloquesActuales < totalBloques) {
          const bloque = document.createElement('div');
          bloque.className = 'win-block';
          track.appendChild(bloque);
          bloquesActuales++;
        }
      }, (tiempoTotal * 1000) / totalBloques);

      const contadorIntervalo = setInterval(() => {
        tiempoRestante--;
        if (tiempoRestante > 0) {
          segSpan.textContent = tiempoRestante;
        } else {
          clearInterval(contadorIntervalo);
          clearInterval(animacionXP);
          segSpan.textContent = '0';
          winStatus.textContent = 'Petición AJAX enviada. Esperando respuesta...';

          // Llamada asincrónica AJAX hacia la vista modular guardar_renglon.php
          fetch('guardar_renglon.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.text())
          .then(html => {
            container.style.display = 'none';
            btn.disabled = false;
            resultado.innerHTML = html;
            // Scroll suave hacia el resultado para visualizarlo inmediatamente
            resultado.scrollIntoView({ behavior: 'smooth' });
          })
          .catch(error => {
            container.style.display = 'none';
            btn.disabled = false;
            resultado.innerHTML = '<p style="color:red;">Error de conexión: ' + error + '</p>';
          });
        }
      }, 1000);
    });
  </script>
</body>
</html>
