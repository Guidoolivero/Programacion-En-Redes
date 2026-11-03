<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>appModulo1 — Renglones de Factura (Protegido por Sesión)</title>
  <link rel="stylesheet" href="estilo.css">
  <script src="jquery.js"></script>
</head>
<body>
  <div class="nav-volver-top">
    <a class="btn-volver" href="../../../index.html">Volver al menú</a>
    <a class="btn-volver" href="../index.php">Menú Sesión</a>
    <a class="btn-volver" href="../descargarErrores.php" style="background:#4a5568;">📥 Errores.txt</a>
  </div>

  <div id="contenedorTablaArticulos" class="contenedorActivo">
    <h1>Renglones de factura de ventas (Módulo 1)</h1>
    <p>Alumno: Guido Olivero — Ejercicio 12: Manejo de Sesión + CRUD MVT</p>

    <?php if (isset($_SESSION['login'])): ?>
      <div style="background: #e6fffa; border: 1px solid #38b2ac; color: #234e52; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
          <strong>Usuario en sesión:</strong> <code><?= htmlspecialchars($_SESSION['login']) ?></code> |
          <strong>Contador de accesos:</strong> <span style="background:#319795; color:#fff; padding:2px 8px; border-radius:10px; font-weight:bold;"><?= htmlspecialchars($_SESSION['ContadorDeSesion'] ?? '1') ?></span> |
          <strong>ID Sesión:</strong> <code style="font-size:12px;"><?= htmlspecialchars($_SESSION['identificativo'] ?? '') ?></code>
        </div>
        <div>
          <span style="font-size: 12px; color: #2c7a7b;">● Módulo protegido con <code>manejoSesion.inc</code></span>
        </div>
      </div>
    <?php endif; ?>

    <div class="controles">
      <label>Orden: <input type="text" id="orden" value="nroFactura" readonly></label>
      <button type="button" id="btnCargar">Cargar datos</button>
      <button type="button" id="btnVaciar">Vaciar datos</button>
      <button type="button" id="btnLimpiar">Limpiar filtros</button>
      <button type="button" id="btnAlta">Alta registro</button>
      <button type="button" id="btCierraSesion" style="background:#e53e3e; color:white; border-color:#c53030; font-weight:bold;">Cierra Sesión</button>
    </div>

    <div class="tabla-wrap">
      <table>
        <thead>
          <tr class="titulos">
            <th data-campo="nroFactura">Nro Factura</th>
            <th data-campo="codArticulo">Cod. artículo</th>
            <th data-campo="unidadMedida">UM</th>
            <th data-campo="descripcion">Descripción</th>
            <th data-campo="fechaFactura">Fecha</th>
            <th data-campo="cantidad">Cantidad</th>
            <th data-campo="precioUnitario">Precio</th>
            <th data-campo="importeRenglon">Importe</th>
            <th>PDFs</th>
            <th>Modificar</th>
            <th>Bajas</th>
          </tr>
          <tr class="filtros">
            <td><input type="text" id="f_nroFactura" placeholder="Buscar nro..."></td>
            <td><input type="text" id="f_codArticulo" placeholder="Buscar cód..."></td>
            <td><select id="f_unidadMedida"><option value="">— todas —</option></select></td>
            <td><input type="text" id="f_descripcion" placeholder="Buscar descripción..."></td>
            <td><input type="date" id="f_fechaFactura"></td>
            <td></td><td></td><td></td><td></td><td></td><td></td>
          </tr>
        </thead>
        <tbody id="tbDatos"><tr><td colspan="11">Sin datos. Pulsa “Cargar datos”.</td></tr></tbody>
        <tfoot><tr><td colspan="11" id="pie">Nro de registros: 0</td></tr></tfoot>
      </table>
    </div>

    <footer><p>Renglones de factura de ventas — Módulo 1 protegido por Sesión — Guido Olivero</p></footer>
  </div>

  <!-- Modales (Alta, Modi, Respuesta, PDF) se mantienen igual -->
  <div class="modal" id="ventanaModalFormularioAlta"><!-- ... --></div>
  <div class="modal" id="ventanaModalFormularioModi"><!-- ... --></div>
  <div class="modal" id="ventanaModalRespuesta"><!-- ... --></div>
  <div class="modal" id="ventanaModalPdf"><!-- ... --></div>

  <script src="jquery.js"></script>
  <script src="abm.js"></script>
</body>
</html>
