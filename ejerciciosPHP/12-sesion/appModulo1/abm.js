var objGlobalFamilias = { unidades: [] };
var renglonesExistentes = [];
var timerFiltro = null;
var reqRenglonesActual = 0;
var objTbDatos;
var objCodArtAlta;

document.addEventListener("DOMContentLoaded", function() {
  objTbDatos = document.getElementById("tbDatos");
  objCodArtAlta = document.getElementById("formArticulosEntCodArtAlta");
  document.getElementById("orden").value = "nroFactura";
  document.getElementById("btEnvioFormAlta").disabled = true;
  document.getElementById("btEnvioFormModi").disabled = true;
  cargarUnidades();
  sincronizarRenglonesExistentes();

  document.getElementById("btCierraSesion").onclick = function() {
    location.href = "../destruirsesion.php";
  };
  document.getElementById("btnCargar").onclick = function() {
    cargarDatos(false);
  };
  document.getElementById("btnVaciar").onclick = function() {
    objTbDatos.innerHTML = '<tr><td colspan="11">Sin datos.</td></tr>';
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
    campo.oninput = filtrarConDemora;
    campo.onkeyup = filtrarConDemora;
    campo.onkeydown = function(evento) {
      if (evento.key === "Enter") {
        evento.preventDefault();
        clearTimeout(timerFiltro);
        cargarDatos(true);
      }
    };
  });
  document.querySelectorAll("#f_unidadMedida, #f_fechaFactura").forEach(function(campo) {
    campo.onchange = function() {
      clearTimeout(timerFiltro);
      cargarDatos(true);
    };
    campo.oninput = campo.onchange;
  });

  document.getElementById("btnAlta").onclick = function() {
    document.getElementById("formArticulosAlta").reset();
    objCodArtAlta.setCustomValidity("");
    document.getElementById("msgErrorCodArtAlta").textContent = "";
    document.getElementById("msgErrorCodArtAlta").style.display = "none";
    objCodArtAlta.style.borderColor = "";
    sincronizarRenglonesExistentes();
    poblarSelectUnidades(document.getElementById("formArticulosEntUmAlta"));
    document.getElementById("formArticulosEntFechaAlta").value = new Date().toISOString().substring(0, 10);
    document.getElementById("btEnvioFormAlta").disabled = true;
    abrirModal("ventanaModalFormularioAlta");
  };
  document.querySelectorAll("#formArticulosAlta input, #formArticulosAlta select").forEach(function(campo) {
    campo.onkeyup = todoListoParaAlta;
    campo.onchange = todoListoParaAlta;
    campo.oninput = todoListoParaAlta;
    campo.onblur = todoListoParaAlta;
  });
  document.querySelectorAll("#formArticulosModi input, #formArticulosModi select").forEach(function(campo) {
    campo.onkeyup = todoListoParaModi;
    campo.onchange = todoListoParaModi;
    campo.oninput = todoListoParaModi;
  });
  document.getElementById("formArticulosAlta").onsubmit = function(evento) {
    evento.preventDefault();
    validarRenglonUnicoAlta();
    if (!this.checkValidity()) {
      todoListoParaAlta();
      return;
    }
    alta();
  };
  document.getElementById("formArticulosModi").onsubmit = function(evento) {
    evento.preventDefault();
    modi();
  };
});

function filtrarConDemora() {
  clearTimeout(timerFiltro);
  timerFiltro = setTimeout(function() {
    cargarDatos(true);
  }, 300);
}

// AJAX común a todas las consultas: al terminar, llama a la función recibida.
async function enviarAjax(url, datos, alResponder, alError) {
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

function guardarRenglonesExistentes(filas) {
  renglonesExistentes = [];
  filas.forEach(function(renglon) {
    if (renglon.nroFactura && renglon.codArticulo) {
      var clave = String(renglon.nroFactura).trim().toUpperCase() + "||" + String(renglon.codArticulo).trim().toUpperCase();
      renglonesExistentes.push(clave);
    }
  });
}

function sincronizarRenglonesExistentes(alTerminar) {
  var datos = new URLSearchParams();
  datos.append("orden", "nroFactura");
  enviarAjax("salidaJsonRenglones.php", datos, function(texto) {
    var respuesta = JSON.parse(texto);
    guardarRenglonesExistentes(respuesta.renglones || []);
    if (document.getElementById("ventanaModalFormularioAlta").classList.contains("activo")) {
      todoListoParaAlta();
    }
    if (alTerminar) alTerminar();
  }, function() {
    // La sincronización es silenciosa, pero la recarga de la tabla debe continuar.
    if (alTerminar) alTerminar();
  });
}

function validarRenglonUnicoAlta() {
  if (!objCodArtAlta) return;
  var nro = document.getElementById("formArticulosEntNroAlta").value.trim();
  var cod = objCodArtAlta.value.trim();
  var clave = nro.toUpperCase() + "||" + cod.toUpperCase();
  var mensaje = document.getElementById("msgErrorCodArtAlta");
  if (nro !== "" && cod !== "" && renglonesExistentes.includes(clave)) {
    objCodArtAlta.setCustomValidity("Ese artículo ya está cargado en la misma factura.");
    mensaje.textContent = '⚠️ La factura "' + nro + '" ya contiene el artículo "' + cod + '" (sí podés cargarlo en otra factura).';
    mensaje.style.display = "";
    objCodArtAlta.style.borderColor = "#e53e3e";
  } else {
    objCodArtAlta.setCustomValidity("");
    mensaje.textContent = "";
    mensaje.style.display = "none";
    objCodArtAlta.style.borderColor = "";
  }
}

function cargarUnidades() {
  enviarAjax("salidaJsonUnidades.php", null, function(texto) {
    alert(texto);
    objGlobalFamilias = JSON.parse(texto);
    llenaFiltroFamilias(objGlobalFamilias);
  }, function(error) {
    alert("Error unidades: " + error);
  });
}

function agregarUnidades(select, unidades, valorSeleccionado) {
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

function llenaFiltroFamilias(objFamilias) {
  var select = document.getElementById("f_unidadMedida");
  while (select.options.length > 1) select.remove(1);
  agregarUnidades(select, objFamilias.unidades || []);
}

function poblarSelectUnidades(select, valorSeleccionado) {
  select.innerHTML = '<option value="">-- Seleccione UM --</option>';
  agregarUnidades(select, objGlobalFamilias.unidades || [], valorSeleccionado);
}

function todoListoParaAlta() {
  validarRenglonUnicoAlta();
  document.getElementById("btEnvioFormAlta").disabled = !document.getElementById("formArticulosAlta").checkValidity();
}

function todoListoParaModi() {
  document.getElementById("btEnvioFormModi").disabled = !document.getElementById("formArticulosModi").checkValidity();
}

function cargarDatos(silencioso) {
  var numeroPeticion = ++reqRenglonesActual;
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
  objTbDatos.innerHTML = '<tr><td colspan="11">Esperando respuesta…</td></tr>';
  enviarAjax("salidaJsonRenglones.php", datos, function(texto) {
    // Una búsqueda anterior puede responder después de la más reciente.
    if (numeroPeticion !== reqRenglonesActual) return;
    if (!silencioso) alert(texto);
    var respuesta = JSON.parse(texto);
    var filas = respuesta.renglones || [];
    if (!hayFiltros) guardarRenglonesExistentes(filas);
    cargarTabla(filas);
    document.getElementById("pie").textContent = "Nro de registros: " + (respuesta.cantidad || 0);
  }, function(error) {
    if (!silencioso) alert("Error: " + error);
  });
}

function cargarTabla(filas) {
  objTbDatos.innerHTML = "";
  if (!filas.length) {
    objTbDatos.innerHTML = '<tr><td colspan="11">Sin resultados.</td></tr>';
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
    celdaPdf.onclick = function() { verPdf(renglon.nroFactura, renglon.codArticulo); };
    fila.appendChild(celdaPdf);

    var celdaModi = document.createElement("td");
    celdaModi.setAttribute("campo-dato", "articulos_btModi");
    celdaModi.innerHTML = '<button type="button" class="btCelda btCeldaModi">Modificar</button>';
    celdaModi.onclick = function() {
      abrirModal("ventanaModalFormularioModi");
      CompletaFichaArticulo(renglon);
    };
    fila.appendChild(celdaModi);

    var celdaBaja = document.createElement("td");
    celdaBaja.setAttribute("campo-dato", "articulos_btBaja");
    celdaBaja.innerHTML = '<button type="button" class="btCelda btCeldaBaja">Borrar</button>';
    celdaBaja.onclick = function() { baja(renglon.nroFactura, renglon.codArticulo); };
    fila.appendChild(celdaBaja);
    objTbDatos.appendChild(fila);
  });
}

function CompletaFichaArticulo(articulo) {
  document.getElementById("formArticulosEntNroModi").value = articulo.nroFactura;
  document.getElementById("formArticulosEntFechaModi").value = (articulo.fechaFactura || "").substring(0, 10);
  document.getElementById("formArticulosEntCodArtModi").value = articulo.codArticulo;
  document.getElementById("formArticulosEntDescripcionModi").value = articulo.descripcion;
  document.getElementById("formArticulosEntCantidadModi").value = articulo.cantidad;
  document.getElementById("formArticulosEntPrecioModi").value = articulo.precioUnitario;
  poblarSelectUnidades(document.getElementById("formArticulosEntUmModi"), articulo.unidadMedida);
  todoListoParaModi();
}

function alta() {
  var datos = new FormData(document.getElementById("formArticulosAlta"));
  enviarAjax("alta.php", datos, function(texto) {
    alert(texto);
    mostrarModalRespuesta(texto);
    cerrarModalAlta();
    sincronizarRenglonesExistentes(function() { cargarDatos(true); });
  }, function(error) {
    alert("Error en alta: " + error);
  });
}

function modi() {
  var datos = new FormData(document.getElementById("formArticulosModi"));
  enviarAjax("modi.php", datos, function(texto) {
    alert(texto);
    mostrarModalRespuesta(texto);
    cerrarModalModi();
    cargarDatos(true);
  }, function(error) {
    alert("Error en modi: " + error);
  });
}

function baja(nro, cod) {
  if (!confirm("¿Confirma la eliminación del renglón Factura " + nro + " / Artículo " + cod + "?")) return;
  var datos = new FormData();
  datos.append("nroFactura", nro);
  datos.append("codArticulo", cod);
  enviarAjax("baja.php", datos, function(texto) {
    alert(texto);
    mostrarModalRespuesta(texto);
    sincronizarRenglonesExistentes(function() { cargarDatos(true); });
  }, function(error) {
    alert("Error en baja: " + error);
  });
}

function verPdf(nro, cod) {
  var url = "traeBinario.php?nroFactura=" + encodeURIComponent(nro) + "&codArticulo=" + encodeURIComponent(cod);
  enviarAjax(url, null, function(texto) {
    var respuesta = JSON.parse(texto);
    if (respuesta && respuesta.documentoPdf) {
      document.getElementById("iframePdf").src = "data:application/pdf;base64," + respuesta.documentoPdf;
      abrirModal("ventanaModalPdf");
    } else {
      alert("No hay documento PDF asociado a este renglón.");
    }
  }, function(error) {
    alert("Error al obtener PDF: " + error);
  });
}

function abrirModal(id) {
  document.getElementById("contenedorTablaArticulos").classList.add("contenedorPasivo");
  var modal = document.getElementById(id);
  modal.style.visibility = "visible";
  modal.classList.add("activo");
}

function cerrarModal(id) {
  var modal = document.getElementById(id);
  modal.style.visibility = "hidden";
  modal.classList.remove("activo");
  document.getElementById("contenedorTablaArticulos").classList.remove("contenedorPasivo");
}

function mostrarModalRespuesta(texto) {
  abrirModal("ventanaModalRespuesta");
  document.getElementById("encabezadoModalRespuesta").textContent = "Respuesta del server: ";
  document.getElementById("contenidoModalRespuesta").innerHTML = texto;
}

function cerrarModalAlta() { cerrarModal("ventanaModalFormularioAlta"); }
function cerrarModalModi() { cerrarModal("ventanaModalFormularioModi"); }
function cerrarModalRespuesta() { cerrarModal("ventanaModalRespuesta"); }
function cerrarModalPdf() {
  cerrarModal("ventanaModalPdf");
  document.getElementById("iframePdf").src = "";
}
