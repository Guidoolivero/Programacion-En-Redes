<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>appModulo1 — Renglones de Factura (Protegido por Sesión)</title>
  <link rel="stylesheet" href="estilo.css?v=20261103-2">
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

    <div class="tabla-wrap" role="region" aria-label="Renglones de factura" tabindex="0">
      <table role="table">
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

  <!-- Modal Formulario de Alta -->
  <div class="modal" id="ventanaModalFormularioAlta">
    <div class="modal-box">
      <span class="cerrar" onclick="cerrarModalAlta()">&times;</span>
      <h3>Formulario de Alta</h3>
      <form id="formArticulosAlta" method="post" enctype="multipart/form-data">
        <label>Nro Factura: <input type="text" name="nroFactura" id="formArticulosEntNroAlta" required></label>
        <label>Fecha: <input type="date" name="fechaFactura" id="formArticulosEntFechaAlta" required></label>
        <label>Cod. artículo:
          <input type="text" name="codArticulo" id="formArticulosEntCodArtAlta" required>
          <span id="msgErrorCodArtAlta" style="color: #c53030; font-size: 12px; font-weight: bold; display: none; margin-top: 4px;"></span>
        </label>
        <label>Descripción: <input type="text" name="descripcion" id="formArticulosEntDescripcionAlta" required></label>
        <label>Unidad de medida: <select name="unidadMedida" id="formArticulosEntUmAlta" required></select></label>
        <label>Cantidad: <input type="number" name="cantidad" id="formArticulosEntCantidadAlta" step="0.01" required></label>
        <label>Precio unitario: <input type="number" name="precioUnitario" id="formArticulosEntPrecioAlta" step="0.01" required></label>
        <label>Documento PDF: <input type="file" name="documentoPdf" id="formArticulosEntDocumentoPdfAlta" accept="application/pdf"></label>
        <button type="submit" id="btEnvioFormAlta" disabled>Enviar Alta</button>
      </form>
    </div>
  </div>

  <!-- Modal Formulario de Modificación -->
  <div class="modal" id="ventanaModalFormularioModi">
    <div class="modal-box">
      <span class="cerrar" onclick="cerrarModalModi()">&times;</span>
      <h3>Formulario de Modificación</h3>
      <form id="formArticulosModi" method="post" enctype="multipart/form-data">
        <label>Nro Factura: <input type="text" name="nroFactura" id="formArticulosEntNroModi" readonly required></label>
        <label>Fecha: <input type="date" name="fechaFactura" id="formArticulosEntFechaModi" required></label>
        <label>Cod. artículo: <input type="text" name="codArticulo" id="formArticulosEntCodArtModi" readonly required></label>
        <label>Descripción: <input type="text" name="descripcion" id="formArticulosEntDescripcionModi" required></label>
        <label>Unidad de medida: <select name="unidadMedida" id="formArticulosEntUmModi" required></select></label>
        <label>Cantidad: <input type="number" name="cantidad" id="formArticulosEntCantidadModi" step="0.01" required></label>
        <label>Precio unitario: <input type="number" name="precioUnitario" id="formArticulosEntPrecioModi" step="0.01" required></label>
        <label>Documento PDF: <input type="file" name="documentoPdf" id="formArticulosEntDocumentoPdfModi" accept="application/pdf"></label>
        <button type="submit" id="btEnvioFormModi" disabled>Enviar Modificación</button>
      </form>
    </div>
  </div>

  <!-- Modal de Respuesta del Servidor -->
  <div class="modal" id="ventanaModalRespuesta">
    <div class="modal-box">
      <span class="cerrar" onclick="cerrarModalRespuesta()">&times;</span>
      <div id="encabezadoModalRespuesta">Respuesta del server:</div>
      <div id="contenidoModalRespuesta"></div>
      <div id="pieModalRespuesta">
        <button type="button" onclick="cerrarModalRespuesta()">Aceptar</button>
      </div>
    </div>
  </div>

  <!-- Modal Visor PDF -->
  <div class="modal" id="ventanaModalPdf">
    <div class="modal-box" style="max-width: 750px; height: 80vh;">
      <span class="cerrar" onclick="cerrarModalPdf()">&times;</span>
      <h3>Visualizador de PDF</h3>
      <iframe id="iframePdf" style="width: 100%; height: calc(100% - 40px); border: none;"></iframe>
    </div>
  </div>

  <script src="abm.js?v=20261106-fetch"></script>
</body>
</html>
