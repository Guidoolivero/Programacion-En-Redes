<?php
include __DIR__ . '/../../manejoSesion.inc'; // Protección de sesión antes del template.
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>appModulo1 — Renglones de Factura (Protegido por Sesión)</title>
  <link rel="stylesheet" href="templates/estilo.css?v=20261110-css">
</head>
<body>
  <!-- Enlaces de navegación y descarga; no necesitan funciones de abm.js. -->
  <div class="nav-volver-top">
    <a class="btn-volver" href="../../../index.html">Volver al menú</a>
    <a class="btn-volver" href="../index.php">Menú Sesión</a>
    <a class="btn-volver btn-errores" href="../descargarErrores.php">📥 Errores.txt</a>
  </div>

  <div id="contenedorTablaArticulos" class="contenedorActivo">
    <h1>Renglones de factura de ventas (Módulo 1)</h1>
    <p>Alumno: Guido Olivero — Ejercicio 12: Manejo de Sesión + CRUD MVT</p>

    <!-- cargarSesion() completa este recuadro con la respuesta del PHP protegido. -->
      <div class="info-sesion">
        <div>
          <strong>Usuario en sesión:</strong> <code id="usuarioSesion"></code> |
          <strong>Contador de accesos:</strong> <span id="contadorSesion"></span> |
          <strong>ID Sesión:</strong> <code id="identificativoSesion"></code>
        </div>
        <div>
          <span class="aviso-sesion">● Módulo protegido con <code>manejoSesion.inc</code></span>
        </div>
      </div>

    <!-- Los ID de estos botones se buscan al iniciar abm.js. -->
    <div class="controles">
      <!-- Este campo conserva el criterio elegido al tocar un encabezado. -->
      <label>Orden: <input type="text" id="orden" value="nroFactura" readonly></label>
      <button type="button" id="btnCargar">Cargar datos</button>
      <button type="button" id="btnVaciar">Vaciar datos</button>
      <button type="button" id="btnLimpiar">Limpiar filtros</button>
      <button type="button" id="btnAlta">Alta registro</button>
      <button type="button" id="btCierraSesion">Cierra Sesión</button>
    </div>

    <div class="tabla-wrap" role="region" aria-label="Renglones de factura" tabindex="0">
      <table role="table">
        <thead>
          <!-- data-campo indica por qué dato ordenar cuando se toca el título. -->
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
          <!-- Cada ID de filtro se envía con el mismo nombre al PHP del listado. -->
          <tr class="filtros">
            <td><input type="text" id="f_nroFactura" placeholder="Buscar nro..."></td>
            <td><input type="text" id="f_codArticulo" placeholder="Buscar cód..."></td>
            <td><select id="f_unidadMedida"><option value="">— todas —</option></select></td>
            <td><input type="text" id="f_descripcion" placeholder="Buscar descripción..."></td>
            <td><input type="date" id="f_fechaFactura"></td>
            <td></td><td></td><td></td><td></td><td></td><td></td>
          </tr>
        </thead>
        <!-- abm.js reemplaza este contenido cuando dibuja los registros. -->
        <tbody id="tbDatos"><tr><td colspan="11">Sin datos. Pulsa “Cargar datos”.</td></tr></tbody>
        <tfoot><tr><td colspan="11" id="pie">Nro de registros: 0</td></tr></tfoot>
      </table>
    </div>

    <footer><p>Renglones de factura de ventas — Módulo 1 protegido por Sesión — Guido Olivero</p></footer>
  </div>

  <!-- Modal Formulario de Alta -->
  <div class="modal" id="ventanaModalFormularioAlta">
    <div class="modal-box">
      <span class="cerrar" onclick="cerrarFormularioAlta()">&times;</span>
      <h3>Formulario de Alta</h3>
      <!-- El submit dispara guardarAlta; multipart permite incluir el PDF. -->
      <form id="formArticulosAlta" method="post" enctype="multipart/form-data">
        <label>Nro Factura: <input type="text" name="nroFactura" id="formArticulosEntNroAlta" placeholder="Ej: 1005" required></label>
        <label>Fecha: <input type="date" name="fechaFactura" id="formArticulosEntFechaAlta" required></label>
        <label>Cod. artículo:
          <!-- value deja ART- escrito; reset lo restaura cada vez que se abre el alta. -->
          <input type="text" name="codArticulo" id="formArticulosEntCodArtAlta" value="ART-" placeholder="Ej: ART-04" required>
          <span id="msgErrorCodArtAlta" hidden></span>
        </label>
        <label>Descripción: <input type="text" name="descripcion" id="formArticulosEntDescripcionAlta" placeholder="Ej: Cable de red Cat 6" required></label>
        <label>Unidad de medida: <select name="unidadMedida" id="formArticulosEntUmAlta" required></select></label>
        <label>Cantidad: <input type="number" name="cantidad" id="formArticulosEntCantidadAlta" placeholder="Ej: 10" step="0.01" required></label>
        <label>Precio unitario: <input type="number" name="precioUnitario" id="formArticulosEntPrecioAlta" placeholder="Ej: 1500.00" step="0.01" required></label>
        <!-- El archivo elegido viaja en FormData bajo el nombre documentoPdf. -->
        <label>Documento PDF: <input type="file" name="documentoPdf" id="formArticulosEntDocumentoPdfAlta" accept="application/pdf"></label>
        <!-- disabled se retira cuando validarFormularioAlta confirma los datos. -->
        <button type="submit" id="btEnvioFormAlta" disabled>Enviar Alta</button>
      </form>
    </div>
  </div>

  <!-- Modal Formulario de Modificación -->
  <div class="modal" id="ventanaModalFormularioModi">
    <div class="modal-box">
      <span class="cerrar" onclick="cerrarFormularioModificacion()">&times;</span>
      <h3>Formulario de Modificación</h3>
      <!-- Al pulsar Enviar Modificación se ejecuta su evento submit en abm.js. -->
      <form id="formArticulosModi" method="post" enctype="multipart/form-data">
        <!-- readonly impide editar la factura; su valor se envía para el WHERE del UPDATE. -->
        <label>Nro Factura: <input type="text" name="nroFactura" id="formArticulosEntNroModi" readonly required></label>
        <label>Fecha: <input type="date" name="fechaFactura" id="formArticulosEntFechaModi" required></label>
        <!-- readonly conserva el artículo que identifica el registro a actualizar. -->
        <label>Cod. artículo: <input type="text" name="codArticulo" id="formArticulosEntCodArtModi" readonly required></label>
        <label>Descripción: <input type="text" name="descripcion" id="formArticulosEntDescripcionModi" required></label>
        <label>Unidad de medida: <select name="unidadMedida" id="formArticulosEntUmModi" required></select></label>
        <label>Cantidad: <input type="number" name="cantidad" id="formArticulosEntCantidadModi" step="0.01" required></label>
        <label>Precio unitario: <input type="number" name="precioUnitario" id="formArticulosEntPrecioModi" step="0.01" required></label>
        <!-- El archivo elegido viaja en FormData bajo el nombre documentoPdf. -->
        <label>Documento PDF: <input type="file" name="documentoPdf" id="formArticulosEntDocumentoPdfModi" accept="application/pdf"></label>
        <!-- Este botón envía el formulario; Modificar en la tabla solo lo abre. -->
        <button type="submit" id="btEnvioFormModi" disabled>Enviar Modificación</button>
      </form>
    </div>
  </div>

  <!-- Modal de Respuesta del Servidor -->
  <div class="modal" id="ventanaModalRespuesta">
    <div class="modal-box">
      <span class="cerrar" onclick="cerrarRespuestaServidor()">&times;</span>
      <div id="encabezadoModalRespuesta">Respuesta del server:</div>
      <!-- Aquí se muestra la traza que devuelven alta.php, modi.php y baja.php. -->
      <div id="contenidoModalRespuesta"></div>
      <div id="pieModalRespuesta">
        <button type="button" onclick="cerrarRespuestaServidor()">Aceptar</button>
      </div>
    </div>
  </div>

  <!-- Modal Visor PDF -->
  <div class="modal" id="ventanaModalPdf">
    <div class="modal-box">
      <span class="cerrar" onclick="cerrarVistaPreviaPdf()">&times;</span>
      <h3>Visualizador de PDF</h3>
      <!--Establece la altura de un elemento para que ocupe el 100% de la altura disponible de su contenedor padre, restándole 40 píxeles.-->
      <!-- mostrarPdf carga en src el Base64; el visor del navegador muestra el PDF. -->
      <iframe id="iframePdf"></iframe>
    </div>
  </div>

  <!-- Carga los eventos y funciones JavaScript después del HTML. -->
  <script src="templates/abm.js?v=20261110-unidades"></script>
</body>
</html>
