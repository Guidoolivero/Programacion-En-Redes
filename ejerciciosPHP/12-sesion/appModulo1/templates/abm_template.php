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
    <a class="btn-volver" href="../../index.html">Menú General</a>
    <a class="btn-volver" href="../index.php">Menú Sesión</a>
    <a class="btn-volver" href="../descargarErrores.php" style="background:#4a5568;">📥 Errores.txt</a>
  </div>

  <div id="contenedorTablaArticulos" class="contenedorActivo">
    <h1>Renglones de factura de ventas (Módulo 1)</h1>
    <p>Alumno: Guido Olivero — Ejercicio 12: Manejo de Sesión + CRUD MVT</p>

    <!-- Indicador visual del estado de sesión del usuario -->
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
      <!-- Botón de cierre de sesión requerido en la pág. 2 del apunte "Como llevar una app al entorno de sesión" -->
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

  <!-- Modal Formulario de Alta (Págs. 1, 5, 7, 15) -->
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

  <!-- Modal Formulario de Modificación (Págs. 1, 5, 8, 9, 12, 19) -->
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

  <!-- Modal de Respuesta del Servidor (Págs. 5, 10, 11) -->
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

  <!-- Modal Visor PDF (Págs. 2, 23) -->
  <div class="modal" id="ventanaModalPdf">
    <div class="modal-box" style="max-width: 750px; height: 80vh;">
      <span class="cerrar" onclick="cerrarModalPdf()">&times;</span>
      <h3>Visualizador de PDF</h3>
      <iframe id="iframePdf" style="width: 100%; height: calc(100% - 40px); border: none;"></iframe>
    </div>
  </div>

  <script>
    // Variable global de unidades (Págs. 3 y 4 del apunte)
    var objGlobalFamilias = { unidades: [] };

    // Conjunto de códigos de artículo existentes para impedir duplicados en el Alta
    var codigosExistentes = new Set();

    // Control de debounce y concurrencia para filtros en tiempo real
    var timerFiltro = null;
    var reqRenglonesActual = 0;

    // Declaraciones de objetos del DOM al arranque (Pág. 5 del apunte)
    var objTbDatos;
    var objCodArtAlta;

    $(document).ready(function() {
      // Código asociado al botón de abandono de sesión (Pág. 2 de "Como llevar una app al entorno de sesión")
      $("#btCierraSesion").click(function() {
        location.href = "../destruirsesion.php";
      });

      objTbDatos = document.getElementById("tbDatos");
      objCodArtAlta = document.getElementById("formArticulosEntCodArtAlta");

      $("#orden").val("nroFactura");
      $("#contenedorTablaArticulos").addClass("contenedorActivo");

      $("#ventanaModalFormularioAlta").css("visibility", "hidden");
      $("#ventanaModalFormularioModi").css("visibility", "hidden");
      $("#ventanaModalRespuesta").css("visibility", "hidden");
      $("#ventanaModalPdf").css("visibility", "hidden");

      $("#btEnvioFormModi").attr("disabled", true);
      $("#btEnvioFormAlta").attr("disabled", true);

      // Carga inicial de unidades (Págs. 3 y 4) y precarga silenciosa de códigos existentes
      cargarUnidades();
      sincronizarCodigosExistentes();

      // Eventos de encabezados para ordenar automáticamente al hacer click
      document.querySelectorAll('th[data-campo]').forEach(th => {
        th.addEventListener('click', () => {
          $("#orden").val(th.dataset.campo);
          cargarDatos(true);
        });
      });

      // Botón Cargar datos (muestra las alertas didácticas del apunte)
      $("#btnCargar").click(function() {
        cargarDatos(false);
      });

      $("#btnVaciar").click(function() {
        $("#tbDatos").html('<tr><td colspan="11">Sin datos.</td></tr>');
        $("#pie").text('Nro de registros: 0');
      });

      // Limpiar filtros y recargar tabla automáticamente
      $("#btnLimpiar").click(function() {
        ['f_nroFactura','f_codArticulo','f_descripcion','f_fechaFactura'].forEach(id => document.getElementById(id).value = '');
        $("#f_unidadMedida").val('');
        cargarDatos(true);
      });

      // Eventos de búsqueda/filtrado en vivo sobre los 5 campos de filtro
      $("#f_nroFactura, #f_codArticulo, #f_descripcion").on("input keyup", function() {
        clearTimeout(timerFiltro);
        timerFiltro = setTimeout(function() {
          cargarDatos(true);
        }, 300);
      });

      $("#f_nroFactura, #f_codArticulo, #f_descripcion").on("keydown", function(e) {
        if (e.key === "Enter") {
          e.preventDefault();
          clearTimeout(timerFiltro);
          cargarDatos(true);
        }
      });

      $("#f_unidadMedida, #f_fechaFactura").on("change input", function() {
        clearTimeout(timerFiltro);
        cargarDatos(true);
      });

      // Botón Alta abre modal
      $("#btnAlta").click(function() {
        $("#contenedorTablaArticulos").addClass("contenedorPasivo");
        document.getElementById("formArticulosAlta").reset();
        objCodArtAlta.setCustomValidity("");
        $("#msgErrorCodArtAlta").hide().text("");
        $(objCodArtAlta).css("border-color", "");
        sincronizarCodigosExistentes();
        poblarSelectUnidades(document.getElementById("formArticulosEntUmAlta"));
        // Asignar por defecto la fecha de hoy en formato YYYY-MM-DD (modificable por el usuario)
        const hoy = new Date().toISOString().substring(0, 10);
        $("#formArticulosEntFechaAlta").val(hoy);
        $("#btEnvioFormAlta").attr("disabled", true);
        $("#ventanaModalFormularioAlta").css("visibility", "visible").addClass("activo");
      });

      // Handlers para validar entradas con keyup, change e input (Págs. 1, 6, 7)
      $("#formArticulosAlta input, #formArticulosAlta select").on("keyup change input blur", function() {
        todoListoParaAlta();
      });
      $("#formArticulosModi input, #formArticulosModi select").on("keyup change input", function() {
        todoListoParaModi();
      });

      // Handlers de envío de formularios (Págs. 10 y 11)
      $("#formArticulosAlta").submit(function(e) {
        e.preventDefault();
        validarCodArtUnico();
        if (!document.getElementById("formArticulosAlta").checkValidity()) {
          todoListoParaAlta();
          return;
        }
        alta();
      });
      $("#formArticulosModi").submit(function(e) {
        e.preventDefault();
        modi();
      });
    });

    // Sincroniza en segundo plano la lista completa de codArticulo para validar unicidad
    async function sincronizarCodigosExistentes() {
      try {
        const params = new URLSearchParams();
        params.append('orden', 'nroFactura');
        const resp = await fetch('salidaJsonRenglones.php', { method: 'POST', body: params });
        const data = await resp.json();
        codigosExistentes.clear();
        (data.renglones || []).forEach(function(r) {
          if (r.codArticulo) {
            codigosExistentes.add(String(r.codArticulo).trim().toUpperCase());
          }
        });
        if ($("#ventanaModalFormularioAlta").hasClass("activo")) {
          todoListoParaAlta();
        }
      } catch (e) {
        // Silencioso en segundo plano
      }
    }

    // Valida que el código de artículo ingresado en el Alta no esté repetido
    function validarCodArtUnico() {
      if (!objCodArtAlta) return;
      const valorRaw = (objCodArtAlta.value || "").trim();
      const valorNorm = valorRaw.toUpperCase();

      if (valorNorm !== "" && codigosExistentes.has(valorNorm)) {
        objCodArtAlta.setCustomValidity("El código de artículo ya existe.");
        $("#msgErrorCodArtAlta").text('⚠️ El código "' + valorRaw + '" ya está registrado. No se permite repetir.').show();
        $(objCodArtAlta).css("border-color", "#e53e3e");
      } else {
        objCodArtAlta.setCustomValidity("");
        $("#msgErrorCodArtAlta").hide().text("");
        $(objCodArtAlta).css("border-color", "");
      }
    }

    // Poblamiento de unidades (Págs. 3 y 4)
    async function cargarUnidades() {
      try {
        const resp = await fetch('salidaJsonUnidades.php');
        const texto = await resp.text();
        alert(texto);
        var objFamilias = JSON.parse(texto);
        llenaFiltroFamilias(objFamilias);
        objGlobalFamilias = objFamilias;
      } catch (e) {
        alert('Error unidades: ' + e);
      }
    }

    function llenaFiltroFamilias(objFamilias) {
      const select = document.getElementById('f_unidadMedida');
      $(select).find('option:not(:first)').remove();
      (objFamilias.unidades || []).forEach(function(argValor, argIndice) {
        var objOption = document.createElement("option");
        objOption.setAttribute("class", "elementoOptionSelect");
        const val = argValor.cod_um || argValor.codigo || argValor.codUm || argValor.id || argValor.descripcion || '';
        const nom = argValor.descripcion || argValor.descripcionUm || argValor.nombre || val;
        objOption.setAttribute("value", val);
        objOption.innerHTML = nom;
        select.appendChild(objOption);
      });
    }

    function poblarSelectUnidades(selectElement, valorSeleccionado = '') {
      $(selectElement).empty();
      var optDefault = document.createElement("option");
      optDefault.value = "";
      optDefault.textContent = "-- Seleccione UM --";
      selectElement.appendChild(optDefault);

      (objGlobalFamilias.unidades || []).forEach(function(argValor, argIndice) {
        var objOption = document.createElement("option");
        objOption.setAttribute("class", "elementoOptionSelect");
        const val = argValor.cod_um || argValor.codigo || argValor.codUm || argValor.id || argValor.descripcion || '';
        const nom = argValor.descripcion || argValor.descripcionUm || argValor.nombre || val;
        objOption.setAttribute("value", val);
        objOption.innerHTML = nom;
        if (val == valorSeleccionado) {
          objOption.setAttribute("selected", "selected");
        }
        selectElement.appendChild(objOption);
      });
    }

    // Funciones de validación con checkValidity (Págs. 6 y 7) + unicidad de codArticulo
    function todoListoParaAlta() {
      validarCodArtUnico();
      if (document.getElementById("formArticulosAlta").checkValidity()) {
        $("#btEnvioFormAlta").attr("disabled", false);
      } else {
        $("#btEnvioFormAlta").attr("disabled", true);
      }
    }

    function todoListoParaModi() {
      if (document.getElementById("formArticulosModi").checkValidity()) {
        $("#btEnvioFormModi").attr("disabled", false);
      } else {
        $("#btEnvioFormModi").attr("disabled", true);
      }
    }

    // Consulta y barrido de filas construyendo celdas y eventos dinámicos (Págs. 8 y 9)
    async function cargarDatos(silencioso = false) {
      const miReq = ++reqRenglonesActual;

      const fNro   = $("#f_nroFactura").val() || '';
      const fCod   = $("#f_codArticulo").val() || '';
      const fUm    = $("#f_unidadMedida").val() || '';
      const fDesc  = $("#f_descripcion").val() || '';
      const fFecha = $("#f_fechaFactura").val() || '';

      const params = new URLSearchParams();
      params.append('orden', $("#orden").val());
      params.append('f_nroFactura', fNro);
      params.append('f_codArticulo', fCod);
      params.append('f_unidadMedida', fUm);
      params.append('f_descripcion', fDesc);
      params.append('f_fechaFactura', fFecha);

      if (!silencioso) {
        alert('Lo que sale del navegador en el body:\n' + params.toString());
      }
      $("#tbDatos").html('<tr><td colspan="11">Esperando respuesta…</td></tr>');

      try {
        const resp = await fetch('salidaJsonRenglones.php', { method: 'POST', body: params });
        const texto = await resp.text();
        if (miReq !== reqRenglonesActual) return; // Ignorar respuesta vieja si el usuario siguió escribiendo

        if (!silencioso) {
          alert(texto);
        }
        const data = JSON.parse(texto);
        const filas = data.renglones || [];

        // Si no hay filtros activos, actualizamos también el set completo de códigos existentes
        if (!fNro && !fCod && !fUm && !fDesc && !fFecha) {
          codigosExistentes.clear();
          filas.forEach(function(r) {
            if (r.codArticulo) {
              codigosExistentes.add(String(r.codArticulo).trim().toUpperCase());
            }
          });
        }

        cargarTabla(filas);
        $("#pie").text('Nro de registros: ' + (data.cantidad || 0));
      } catch (e) {
        if (!silencioso) {
          alert('Error: ' + e);
        }
      }
    }

    function cargarTabla(filas) {
      const tbody = document.getElementById('tbDatos');
      tbody.innerHTML = '';
      if (!filas.length) {
        tbody.innerHTML = '<tr><td colspan="11">Sin resultados.</td></tr>';
        return;
      }

      filas.forEach(function(argValor) {
        var objTr = document.createElement("tr");

        // Datos de columnas comunes
        const campos = ['nroFactura', 'codArticulo', 'unidadMedida', 'descripcion', 'fechaFactura', 'cantidad', 'precioUnitario', 'importeRenglon'];
        campos.forEach(c => {
          var td = document.createElement("td");
          td.textContent = argValor[c] ?? '';
          objTr.appendChild(td);
        });

        // Columna PDF (Págs. 2 y 23)
        var tdPdf = document.createElement("td");
        tdPdf.setAttribute("campo-dato", "articulos_btPdf");
        tdPdf.innerHTML = "<button type='button' class='btCelda btCeldaPdf'>PDF</button>";
        tdPdf.onclick = function() {
          verPdf(argValor.nroFactura, argValor.codArticulo);
        };
        objTr.appendChild(tdPdf);

        // Columna Modificar (Pág. 8)
        var tdModi = document.createElement("td");
        tdModi.setAttribute("campo-dato", "articulos_btModi");
        tdModi.innerHTML = "<button type='button' class='btCelda btCeldaModi'>Modificar</button>";
        tdModi.onclick = function() {
          $("#contenedorTablaArticulos").addClass("contenedorPasivo");
          $("#ventanaModalFormularioModi").css("visibility", "visible").addClass("activo");
          CompletaFichaArticulo(argValor);
        };
        objTr.appendChild(tdModi);

        // Columna Baja (Pág. 8)
        var tdBaja = document.createElement("td");
        tdBaja.setAttribute("campo-dato", "articulos_btBaja");
        tdBaja.innerHTML = "<button type='button' class='btCelda btCeldaBaja'>Borrar</button>";
        tdBaja.onclick = function() {
          baja(argValor.nroFactura, argValor.codArticulo);
        };
        objTr.appendChild(tdBaja);

        tbody.appendChild(objTr);
      });
    }

    // Completa el formulario de Modi (Pág. 9)
    function CompletaFichaArticulo(argArticulo) {
      $("#formArticulosEntNroModi").val(argArticulo.nroFactura);
      $("#formArticulosEntFechaModi").val((argArticulo.fechaFactura || '').substring(0, 10));
      $("#formArticulosEntCodArtModi").val(argArticulo.codArticulo);
      $("#formArticulosEntDescripcionModi").val(argArticulo.descripcion);
      $("#formArticulosEntCantidadModi").val(argArticulo.cantidad);
      $("#formArticulosEntPrecioModi").val(argArticulo.precioUnitario);

      const selectUm = document.getElementById("formArticulosEntUmModi");
      poblarSelectUnidades(selectUm, argArticulo.unidadMedida);

      todoListoParaModi();
    }

    // Petición AJAX Alta (Págs. 10 y 11)
    async function alta() {
      var objFormulario = document.getElementById("formArticulosAlta");
      var objDatosFormulario = new FormData(objFormulario);
      const options = {
        method: 'POST',
        headers: {},
        body: objDatosFormulario
      };

      try {
        const resp = await fetch('alta.php', options);
        const datos = await resp.text();
        alert(datos);
        mostrarModalRespuesta(datos);
        cerrarModalAlta();
        await sincronizarCodigosExistentes();
        cargarDatos(true);
      } catch (err) {
        alert('Error en alta: ' + err);
      }
    }

    // Petición AJAX Modi (Págs. 10 y 11)
    async function modi() {
      var objFormulario = document.getElementById("formArticulosModi");
      var objDatosFormulario = new FormData(objFormulario);
      const options = {
        method: 'POST',
        headers: {},
        body: objDatosFormulario
      };

      try {
        const resp = await fetch('modi.php', options);
        const datos = await resp.text();
        alert(datos);
        mostrarModalRespuesta(datos);
        cerrarModalModi();
        cargarDatos(true);
      } catch (err) {
        alert('Error en modi: ' + err);
      }
    }

    // Petición AJAX Baja (Pág. 10)
    async function baja(nro, cod) {
      if (!confirm('¿Confirma la eliminación del renglón Factura ' + nro + ' / Artículo ' + cod + '?')) return;
      var objDatosFormulario = new FormData();
      objDatosFormulario.append('nroFactura', nro);
      objDatosFormulario.append('codArticulo', cod);

      try {
        const resp = await fetch('baja.php', { method: 'POST', body: objDatosFormulario });
        const datos = await resp.text();
        alert(datos);
        mostrarModalRespuesta(datos);
        await sincronizarCodigosExistentes();
        cargarDatos(true);
      } catch (err) {
        alert('Error en baja: ' + err);
      }
    }

    // Visualizar PDF con traeBinario.php (Pág. 23)
    async function verPdf(nro, cod) {
      try {
        const resp = await fetch(`traeBinario.php?nroFactura=${encodeURIComponent(nro)}&codArticulo=${encodeURIComponent(cod)}`);
        const json = await resp.json();
        if (json && json.documentoPdf) {
          $("#contenedorTablaArticulos").addClass("contenedorPasivo");
          document.getElementById('iframePdf').src = "data:application/pdf;base64," + json.documentoPdf;
          $("#ventanaModalPdf").css("visibility", "visible").addClass("activo");
        } else {
          alert('No hay documento PDF asociado a este renglón.');
        }
      } catch (e) {
        alert('Error al obtener PDF: ' + e);
      }
    }

    // Modal de respuesta del servidor (Págs. 10 y 11)
    function mostrarModalRespuesta(datos) {
      $("#contenedorTablaArticulos").addClass("contenedorPasivo");
      $("#ventanaModalRespuesta").css("visibility", "visible").addClass("activo");
      $("#contenidoModalRespuesta").empty();
      $("#encabezadoModalRespuesta").html("Respuesta del server: ");
      $("#contenidoModalRespuesta").html(datos);
    }

    // Funciones de cierre de modales y restauración de opacidad
    function cerrarModalAlta() {
      $("#ventanaModalFormularioAlta").css("visibility", "hidden").removeClass("activo");
      $("#contenedorTablaArticulos").removeClass("contenedorPasivo");
    }
    function cerrarModalModi() {
      $("#ventanaModalFormularioModi").css("visibility", "hidden").removeClass("activo");
      $("#contenedorTablaArticulos").removeClass("contenedorPasivo");
    }
    function cerrarModalRespuesta() {
      $("#ventanaModalRespuesta").css("visibility", "hidden").removeClass("activo");
      $("#contenedorTablaArticulos").removeClass("contenedorPasivo");
    }
    function cerrarModalPdf() {
      $("#ventanaModalPdf").css("visibility", "hidden").removeClass("activo");
      document.getElementById('iframePdf').src = "";
      $("#contenedorTablaArticulos").removeClass("contenedorPasivo");
    }
  </script>
</body>
</html>
