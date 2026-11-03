    // Variable global de unidades (Págs. 3 y 4 del apunte)
    var objGlobalFamilias = { unidades: [] };

    // Conjunto de claves compuestas (NRO_FACTURA||COD_ARTICULO) para permitir repetir
    // el mismo artículo en distintas facturas sin duplicar el mismo renglón en la misma factura
    var renglonesExistentes = new Set();

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

      // Carga inicial de unidades (Págs. 3 y 4) y precarga silenciosa de renglones existentes
      cargarUnidades();
      sincronizarRenglonesExistentes();

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
        sincronizarRenglonesExistentes();
        poblarSelectUnidades(document.getElementById("formArticulosEntUmAlta"));
        const hoy = new Date().toISOString().substring(0, 10);
        $("#formArticulosEntFechaAlta").val(hoy);
        $("#btEnvioFormAlta").attr("disabled", true);
        $("#ventanaModalFormularioAlta").css("visibility", "visible").addClass("activo");
      });

      // Handlers para validar entradas
      $("#formArticulosAlta input, #formArticulosAlta select").on("keyup change input blur", function() {
        todoListoParaAlta();
      });
      $("#formArticulosModi input, #formArticulosModi select").on("keyup change input", function() {
        todoListoParaModi();
      });

      // Handlers de envío de formularios
      $("#formArticulosAlta").submit(function(e) {
        e.preventDefault();
        validarRenglonUnicoAlta();
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
