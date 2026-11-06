var datosUnidades = { unidades: [] };
var clavesRenglonesExistentes = [];
var temporizadorFiltro = null;
var ultimaPeticionListado = 0;
var cuerpoTabla;
var campoCodigoArticuloAlta;

document.addEventListener("DOMContentLoaded", function() {
  cuerpoTabla = document.getElementById("tbDatos");
  campoCodigoArticuloAlta = document.getElementById("formArticulosEntCodArtAlta");
  document.getElementById("orden").value = "nroFactura";
  document.getElementById("btEnvioFormAlta").disabled = true;
  document.getElementById("btEnvioFormModi").disabled = true;
  cargarUnidades();
  actualizarClavesExistentes();

  document.getElementById("btCierraSesion").onclick = function() {
    location.href = "../destruirsesion.php";
  };
  document.getElementById("btnCargar").onclick = function() {
    cargarDatos(false);
  };
  document.getElementById("btnVaciar").onclick = function() {
    cuerpoTabla.innerHTML = '<tr><td colspan="11">Sin datos.</td></tr>';
    document.getElementById("pie").textContent = "Nro de registros: 0";
  };
  document.getElementById("btnLimpiar").onclick = function() {
    document.querySelectorAll(".filtros input, .filtros select").forEach(function(campo) {
      campo.value = "";
    });
    cargarDatos(true);
  };
  document.querySelectorAll("th[data-campo]").forEach(function(titulo) {
    titulo.onclick = function() {
      document.getElementById("orden").value = titulo.dataset.campo;
      cargarDatos(true);
    };
  });

  document.querySelectorAll("#f_nroFactura, #f_codArticulo, #f_descripcion").forEach(function(campo) {
    campo.oninput = programarFiltro;
    campo.onkeyup = programarFiltro;
    campo.onkeydown = function(evento) {
      if (evento.key === "Enter") {
        evento.preventDefault();
        clearTimeout(temporizadorFiltro);
        cargarDatos(true);
      }
    };
  });
  document.querySelectorAll("#f_unidadMedida, #f_fechaFactura").forEach(function(campo) {
    campo.onchange = function() {
      clearTimeout(temporizadorFiltro);
      cargarDatos(true);
    };
    campo.oninput = campo.onchange;
  });

  document.getElementById("btnAlta").onclick = function() {
    document.getElementById("formArticulosAlta").reset();
    campoCodigoArticuloAlta.setCustomValidity("");
    document.getElementById("msgErrorCodArtAlta").textContent = "";
    document.getElementById("msgErrorCodArtAlta").style.display = "none";
    campoCodigoArticuloAlta.style.borderColor = "";
    actualizarClavesExistentes();
    llenarSelectorUnidades(document.getElementById("formArticulosEntUmAlta"));
    document.getElementById("formArticulosEntFechaAlta").value = new Date().toISOString().substring(0, 10);
    document.getElementById("btEnvioFormAlta").disabled = true;
    abrirVentana("ventanaModalFormularioAlta");
  };
  document.querySelectorAll("#formArticulosAlta input, #formArticulosAlta select").forEach(function(campo) {
    campo.onkeyup = validarFormularioAlta;
    campo.onchange = validarFormularioAlta;
    campo.oninput = validarFormularioAlta;
    campo.onblur = validarFormularioAlta;
  });
  document.querySelectorAll("#formArticulosModi input, #formArticulosModi select").forEach(function(campo) {
    campo.onkeyup = validarFormularioModificacion;
    campo.onchange = validarFormularioModificacion;
    campo.oninput = validarFormularioModificacion;
  });
  document.getElementById("formArticulosAlta").onsubmit = function(evento) {
    evento.preventDefault();
    validarDuplicadoEnFactura();
    if (!this.checkValidity()) {
      validarFormularioAlta();
      return;
    }
    guardarAlta();
  };
  document.getElementById("formArticulosModi").onsubmit = function(evento) {
    evento.preventDefault();
    guardarModificacion();
  };
});

function programarFiltro() {
  clearTimeout(temporizadorFiltro);
  temporizadorFiltro = setTimeout(function() {
    cargarDatos(true);
  }, 300);
}

// AJAX común a todas las consultas: al terminar, llama a la función recibida.
async function enviarPeticionAjax(url, datos, alResponder, alError) {
  var opciones = { method: "GET" };
  if (datos !== null) {
    opciones.method = "POST";
    opciones.body = datos;
  }
  try {
    var respuesta = await fetch(url, opciones);
    var texto = await respuesta.text();
    alResponder(texto);
  } catch (error) {
    if (alError) alError(error);
  }
}

function guardarClavesExistentes(filas) {
  clavesRenglonesExistentes = [];
  filas.forEach(function(renglon) {
    if (renglon.nroFactura && renglon.codArticulo) {
      var clave = String(renglon.nroFactura).trim().toUpperCase() + "||" + String(renglon.codArticulo).trim().toUpperCase();
      clavesRenglonesExistentes.push(clave);
    }
  });
}

function actualizarClavesExistentes(alTerminar) {
  var datos = new URLSearchParams();
  datos.append("orden", "nroFactura");
  enviarPeticionAjax("salidaJsonRenglones.php", datos, function(texto) {
    var respuesta = JSON.parse(texto);
    guardarClavesExistentes(respuesta.renglones || []);
    if (document.getElementById("ventanaModalFormularioAlta").classList.contains("activo")) {
      validarFormularioAlta();
    }
    if (alTerminar) alTerminar();
  }, function() {
    // La sincronización es silenciosa, pero la recarga de la tabla debe continuar.
    if (alTerminar) alTerminar();
  });
}

function validarDuplicadoEnFactura() {
  if (!campoCodigoArticuloAlta) return;
  var numeroFactura = document.getElementById("formArticulosEntNroAlta").value.trim();
  var codigoArticulo = campoCodigoArticuloAlta.value.trim();
  var clave = numeroFactura.toUpperCase() + "||" + codigoArticulo.toUpperCase();
  var mensaje = document.getElementById("msgErrorCodArtAlta");
  if (numeroFactura !== "" && codigoArticulo !== "" && clavesRenglonesExistentes.includes(clave)) {
    campoCodigoArticuloAlta.setCustomValidity("Ese artículo ya está cargado en la misma factura.");
    mensaje.textContent = '⚠️ La factura "' + numeroFactura + '" ya contiene el artículo "' + codigoArticulo + '" (sí podés cargarlo en otra factura).';
    mensaje.style.display = "";
    campoCodigoArticuloAlta.style.borderColor = "#e53e3e";
  } else {
    campoCodigoArticuloAlta.setCustomValidity("");
    mensaje.textContent = "";
    mensaje.style.display = "none";
    campoCodigoArticuloAlta.style.borderColor = "";
  }
}

function cargarUnidades() {
  enviarPeticionAjax("salidaJsonUnidades.php", null, function(texto) {
    alert(texto);
    datosUnidades = JSON.parse(texto);
    llenarFiltroUnidades(datosUnidades);
  }, function(error) {
    alert("Error unidades: " + error);
  });
}

function agregarOpcionesUnidades(select, unidades, valorSeleccionado) {
  unidades.forEach(function(unidad) {
    var valor = unidad.cod_um || unidad.codigo || unidad.codUm || unidad.id || unidad.descripcion || "";
    var nombre = unidad.descripcion || unidad.descripcionUm || unidad.nombre || valor;
    var opcion = document.createElement("option");
    opcion.className = "elementoOptionSelect";
    opcion.value = valor;
    opcion.textContent = nombre;
    if (valor == valorSeleccionado) opcion.selected = true;
    select.appendChild(opcion);
  });
}

function llenarFiltroUnidades(respuestaUnidades) {
  var select = document.getElementById("f_unidadMedida");
  while (select.options.length > 1) select.remove(1);
  agregarOpcionesUnidades(select, respuestaUnidades.unidades || []);
}

function llenarSelectorUnidades(select, valorSeleccionado) {
  select.innerHTML = '<option value="">-- Seleccione UM --</option>';
  agregarOpcionesUnidades(select, datosUnidades.unidades || [], valorSeleccionado);
}

function validarFormularioAlta() {
  validarDuplicadoEnFactura();
  document.getElementById("btEnvioFormAlta").disabled = !document.getElementById("formArticulosAlta").checkValidity();
}

function validarFormularioModificacion() {
  document.getElementById("btEnvioFormModi").disabled = !document.getElementById("formArticulosModi").checkValidity();
}

function cargarDatos(silencioso) {
  var numeroPeticion = ++ultimaPeticionListado;
  var datos = new URLSearchParams();
  var filtros = ["f_nroFactura", "f_codArticulo", "f_unidadMedida", "f_descripcion", "f_fechaFactura"];
  var hayFiltros = false;
  datos.append("orden", document.getElementById("orden").value);
  filtros.forEach(function(id) {
    var valor = document.getElementById(id).value;
    datos.append(id, valor);
    if (valor !== "") hayFiltros = true;
  });
  if (!silencioso) alert("Lo que sale del navegador en el body:\n" + datos.toString());
  cuerpoTabla.innerHTML = '<tr><td colspan="11">Esperando respuesta…</td></tr>';
  enviarPeticionAjax("salidaJsonRenglones.php", datos, function(texto) {
    // Una búsqueda anterior puede responder después de la más reciente.
    if (numeroPeticion !== ultimaPeticionListado) return;
    if (!silencioso) alert(texto);
    var respuesta = JSON.parse(texto);
    var filas = respuesta.renglones || [];
    if (!hayFiltros) guardarClavesExistentes(filas);
    dibujarTabla(filas);
    document.getElementById("pie").textContent = "Nro de registros: " + (respuesta.cantidad || 0);
  }, function(error) {
    if (!silencioso) alert("Error: " + error);
  });
}

function dibujarTabla(filas) {
  cuerpoTabla.innerHTML = "";
  if (!filas.length) {
    cuerpoTabla.innerHTML = '<tr><td colspan="11">Sin resultados.</td></tr>';
    return;
  }
  var campos = ["nroFactura", "codArticulo", "unidadMedida", "descripcion", "fechaFactura", "cantidad", "precioUnitario", "importeRenglon"];
  filas.forEach(function(renglon) {
    var fila = document.createElement("tr");
    campos.forEach(function(campo) {
      var celda = document.createElement("td");
      celda.textContent = renglon[campo] == null ? "" : renglon[campo];
      fila.appendChild(celda);
    });
    var celdaPdf = document.createElement("td");
    celdaPdf.setAttribute("campo-dato", "articulos_btPdf");
    celdaPdf.innerHTML = '<button type="button" class="btCelda btCeldaPdf">PDF</button>';
    celdaPdf.onclick = function() { mostrarPdf(renglon.nroFactura, renglon.codArticulo); };
    fila.appendChild(celdaPdf);

    var celdaModificar = document.createElement("td");
    celdaModificar.setAttribute("campo-dato", "articulos_btModi");
    celdaModificar.innerHTML = '<button type="button" class="btCelda btCeldaModi">Modificar</button>';
    celdaModificar.onclick = function() {
      abrirVentana("ventanaModalFormularioModi");
      completarFormularioModificacion(renglon);
    };
    fila.appendChild(celdaModificar);

    var celdaBorrar = document.createElement("td");
    celdaBorrar.setAttribute("campo-dato", "articulos_btBaja");
    celdaBorrar.innerHTML = '<button type="button" class="btCelda btCeldaBaja">Borrar</button>';
    celdaBorrar.onclick = function() { eliminarRenglon(renglon.nroFactura, renglon.codArticulo); };
    fila.appendChild(celdaBorrar);
    cuerpoTabla.appendChild(fila);
  });
}

function completarFormularioModificacion(articulo) {
  document.getElementById("formArticulosEntNroModi").value = articulo.nroFactura;
  document.getElementById("formArticulosEntFechaModi").value = (articulo.fechaFactura || "").substring(0, 10);
  document.getElementById("formArticulosEntCodArtModi").value = articulo.codArticulo;
  document.getElementById("formArticulosEntDescripcionModi").value = articulo.descripcion;
  document.getElementById("formArticulosEntCantidadModi").value = articulo.cantidad;
  document.getElementById("formArticulosEntPrecioModi").value = articulo.precioUnitario;
  llenarSelectorUnidades(document.getElementById("formArticulosEntUmModi"), articulo.unidadMedida);
  validarFormularioModificacion();
}

function guardarAlta() {
  var datos = new FormData(document.getElementById("formArticulosAlta"));
  enviarPeticionAjax("alta.php", datos, function(texto) {
    alert(texto);
    mostrarRespuestaServidor(texto);
    cerrarFormularioAlta();
    actualizarClavesExistentes(function() { cargarDatos(true); });
  }, function(error) {
    alert("Error en alta: " + error);
  });
}

function guardarModificacion() {
  var datos = new FormData(document.getElementById("formArticulosModi"));
  enviarPeticionAjax("modi.php", datos, function(texto) {
    alert(texto);
    mostrarRespuestaServidor(texto);
    cerrarFormularioModificacion();
    cargarDatos(true);
  }, function(error) {
    alert("Error en modi: " + error);
  });
}

function eliminarRenglon(numeroFactura, codigoArticulo) {
  if (!confirm("¿Confirma la eliminación del renglón Factura " + numeroFactura + " / Artículo " + codigoArticulo + "?")) return;
  var datos = new FormData();
  datos.append("nroFactura", numeroFactura);
  datos.append("codArticulo", codigoArticulo);
  enviarPeticionAjax("baja.php", datos, function(texto) {
    alert(texto);
    mostrarRespuestaServidor(texto);
    actualizarClavesExistentes(function() { cargarDatos(true); });
  }, function(error) {
    alert("Error en baja: " + error);
  });
}

function mostrarPdf(numeroFactura, codigoArticulo) {
  var url = "traeBinario.php?nroFactura=" + encodeURIComponent(numeroFactura) + "&codArticulo=" + encodeURIComponent(codigoArticulo);
  enviarPeticionAjax(url, null, function(texto) {
    var respuesta = JSON.parse(texto);
    if (respuesta && respuesta.documentoPdf) {
      document.getElementById("iframePdf").src = "data:application/pdf;base64," + respuesta.documentoPdf;
      abrirVentana("ventanaModalPdf");
    } else {
      alert("No hay documento PDF asociado a este renglón.");
    }
  }, function(error) {
    alert("Error al obtener PDF: " + error);
  });
}

function abrirVentana(id) {
  document.getElementById("contenedorTablaArticulos").classList.add("contenedorPasivo");
  var modal = document.getElementById(id);
  modal.style.visibility = "visible";
  modal.classList.add("activo");
}

function cerrarVentana(id) {
  var modal = document.getElementById(id);
  modal.style.visibility = "hidden";
  modal.classList.remove("activo");
  document.getElementById("contenedorTablaArticulos").classList.remove("contenedorPasivo");
}

function mostrarRespuestaServidor(texto) {
  abrirVentana("ventanaModalRespuesta");
  document.getElementById("encabezadoModalRespuesta").textContent = "Respuesta del server: ";
  document.getElementById("contenidoModalRespuesta").innerHTML = texto;
}

function cerrarFormularioAlta() { cerrarVentana("ventanaModalFormularioAlta"); }
function cerrarFormularioModificacion() { cerrarVentana("ventanaModalFormularioModi"); }
function cerrarRespuestaServidor() { cerrarVentana("ventanaModalRespuesta"); }
function cerrarVistaPreviaPdf() {
  cerrarVentana("ventanaModalPdf");
  document.getElementById("iframePdf").src = "";
}
