var unidades = []; // Opciones recibidas para los selectores de unidades.
var clavesRenglonesExistentes = []; // Guarda claves como 1001||ART-02.
var temporizadorFiltro = null; // Identifica la espera pendiente del filtro.
var ultimaPeticionListado = 0; // Cuenta las búsquedas para reconocer respuestas viejas.
var cuerpoTabla; // Apuntará al tbody donde se dibujan los registros.
var formularioAlta;
var formularioModificacion;

// Conecta los botones y formularios cuando el HTML está listo.
document.addEventListener("DOMContentLoaded", function() {
  cuerpoTabla = document.getElementById("tbDatos");
  formularioAlta = document.getElementById("formArticulosAlta");
  formularioModificacion = document.getElementById("formArticulosModi");
  document.getElementById("orden").value = "nroFactura";
  document.getElementById("btEnvioFormAlta").disabled = true;
  document.getElementById("btEnvioFormModi").disabled = true;
  cargarSesion(); // Completa usuario, contador e identificativo desde PHP.
  cargarUnidades();
  actualizarClavesExistentes();

  document.getElementById("btCierraSesion").onclick = function() {
    location.href = "../destruirsesion.php";
  };
  document.getElementById("btnCargar").onclick = function() {
    cargarDatos(false); // false muestra las alertas del pedido y la respuesta.
  };
  document.getElementById("btnVaciar").onclick = function() {
    cuerpoTabla.innerHTML = '<tr><td colspan="11">Sin datos.</td></tr>';
    document.getElementById("pie").textContent = "Nro de registros: 0";
  };
  document.getElementById("btnLimpiar").onclick = function() {
    document.querySelectorAll(".filtros input, .filtros select").forEach(function(campo) {
      campo.value = "";
    });
    cargarDatos(true); // true actualiza la tabla sin alertas.
  };
  document.querySelectorAll("th[data-campo]").forEach(function(titulo) {
    titulo.onclick = function() {
      document.getElementById("orden").value = titulo.dataset.campo;
      cargarDatos(true);
    };
  });

  // El texto espera 300 ms; Enter busca inmediatamente sin repetir el pedido.
  document.querySelectorAll(".filtros input[type=text]").forEach(function(campo) {
    campo.oninput = programarFiltro;
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
  });

  document.getElementById("btnAlta").onclick = function() {
    formularioAlta.reset(); // Restaura también el prefijo ART-.
    validarDuplicadoEnFactura(); // Quita el error del alta anterior.
    actualizarClavesExistentes();
    llenarSelectorUnidades(formularioAlta.elements.unidadMedida);
    formularioAlta.elements.fechaFactura.value = new Date().toISOString().substring(0, 10);
    document.getElementById("btEnvioFormAlta").disabled = true;
    abrirVentana("ventanaModalFormularioAlta");
  };
  // Los eventos de todos los campos llegan al formulario; no hace falta recorrerlos.
  formularioAlta.oninput = validarFormularioAlta;
  formularioAlta.onchange = validarFormularioAlta;
  formularioAlta.onkeyup = validarFormularioAlta;
  formularioAlta.onfocusout = validarFormularioAlta;
  formularioModificacion.oninput = validarFormularioModificacion;
  formularioModificacion.onchange = validarFormularioModificacion;
  formularioModificacion.onkeyup = validarFormularioModificacion;
  formularioAlta.onsubmit = function(evento) {
    evento.preventDefault(); // Envía con fetch sin recargar la página.
    validarFormularioAlta();
    if (this.checkValidity()) guardarAlta();
  };
  formularioModificacion.onsubmit = function(evento) {
    evento.preventDefault();
    guardarModificacion();
  };
});

// Espera 300 ms desde la última escritura antes de buscar.
function programarFiltro() {
  clearTimeout(temporizadorFiltro);
  temporizadorFiltro = setTimeout(function() {
    cargarDatos(true);
  }, 300);
}

// Petición común: los formularios usan POST; las consultas sin cuerpo usan GET.
async function enviarPeticionAjax(url, datos) {
  var opciones = { method: "GET" };
  if (datos !== null) opciones = { method: "POST", body: datos };
  var respuesta = await fetch(url, opciones); // await espera sin bloquear los demás eventos.
  return respuesta.text();
}

// Recuerda los pares factura + artículo para validar duplicados.
function guardarClavesExistentes(filas) {
  clavesRenglonesExistentes = []; // Borra las claves anteriores antes de reconstruirlas.
  filas.forEach(function(renglon) { // Recorre los registros recibidos; renglon es la fila actual.
    if (renglon.nroFactura && renglon.codArticulo) { // Solo arma claves cuando existen sus dos componentes.
      // Une factura y artículo sin espacios externos ni diferencias de mayúsculas.
      var clave = String(renglon.nroFactura).trim() + "||" + renglon.codArticulo.trim().toUpperCase();
      clavesRenglonesExistentes.push(clave); // Agrega la combinación al arreglo de claves conocidas.
    }
  });
}

// Trae las claves completas, sin los filtros de la tabla.
async function actualizarClavesExistentes() {
  try {
    var datos = new URLSearchParams({ orden: "nroFactura" });
    var texto = await enviarPeticionAjax("salidaJsonRenglones.php", datos);
    guardarClavesExistentes(JSON.parse(texto).renglones || []);
    if (document.getElementById("ventanaModalFormularioAlta").classList.contains("activo")) {
      validarFormularioAlta();
    }
  } catch (error) {
    // La sincronización es silenciosa; PHP también controla los duplicados al dar de alta.
  }
}

// Impide repetir un artículo dentro de la misma factura.
function validarDuplicadoEnFactura() {
  var campoCodigoArticuloAlta = formularioAlta.elements.codArticulo;
  var numeroFactura = formularioAlta.elements.nroFactura.value.trim();
  var codigoArticulo = campoCodigoArticuloAlta.value.trim();
  var clave = numeroFactura + "||" + codigoArticulo.toUpperCase();
  var mensaje = document.getElementById("msgErrorCodArtAlta");
  if (numeroFactura !== "" && codigoArticulo !== "" && clavesRenglonesExistentes.includes(clave)) {
    // Marca el campo inválido para impedir enviar el alta.
    campoCodigoArticuloAlta.setCustomValidity("Ese artículo ya está cargado en la misma factura.");
    mensaje.textContent = '⚠️ La factura "' + numeroFactura + '" ya contiene el artículo "' + codigoArticulo + '" (sí podés cargarlo en otra factura).';
    mensaje.hidden = false; // Vuelve a mostrar el mensaje de duplicado.
    campoCodigoArticuloAlta.style.borderColor = "#e53e3e"; // Resalta el campo que tiene el error.
  } else {
    campoCodigoArticuloAlta.setCustomValidity(""); // Quita el error personalizado de duplicado.
    mensaje.textContent = ""; // Borra el error cuando la combinación es válida.
    mensaje.hidden = true; // Oculta el mensaje que ya no corresponde.
    campoCodigoArticuloAlta.style.borderColor = ""; // Restaura el borde normal del campo.
  }
}

// Obtiene los datos privados sin escribir PHP dentro del template HTML.
async function cargarSesion() {
  try {
    var texto = await enviarPeticionAjax("datosSesion.php", null);
    var sesion = JSON.parse(texto);
    document.getElementById("usuarioSesion").textContent = sesion.login;
    document.getElementById("contadorSesion").textContent = sesion.contador;
    document.getElementById("identificativoSesion").textContent = sesion.identificativo;
  } catch (error) {
    alert("Error de sesión: " + error);
  }
}

// Pide las unidades al PHP y las conserva para los selectores.
async function cargarUnidades() {
  try {
    var texto = await enviarPeticionAjax("salidaJsonUnidades.php", null);
    alert(texto);
    unidades = JSON.parse(texto).unidades || [];
    llenarFiltroUnidades();
  } catch (error) {
    alert("Error unidades: " + error);
  }
}

// Crea una opción por unidad; puede dejar una seleccionada.
function agregarOpcionesUnidades(select, unidades, valorSeleccionado) {
  unidades.forEach(function(unidad) { // Recorre cada unidad recibida del servidor.
    // Usa las dos columnas reales de unidades_medida.
    var valor = unidad.cod_um;
    var nombre = unidad.descripcion;
    var opcion = document.createElement("option"); // Crea una opción nueva del selector.
    opcion.className = "elementoOptionSelect";
    opcion.value = valor; // Guarda el valor que se enviará al servidor.
    opcion.textContent = nombre; // Escribe el texto visible sin interpretar HTML.
    if (valor == valorSeleccionado) opcion.selected = true; // Deja elegida la unidad que tenía el registro.
    select.appendChild(opcion); // Agrega la opción al selector.
  });
}

// Llena el filtro de unidades conservando la opción de todas.
function llenarFiltroUnidades() {
  var select = document.getElementById("f_unidadMedida");
  while (select.options.length > 1) select.remove(1); // Conserva la opción de todas.
  agregarOpcionesUnidades(select, unidades);
}

// Prepara el selector del formulario de alta o modificación.
function llenarSelectorUnidades(select, valorSeleccionado) {
  select.innerHTML = '<option value="">-- Seleccione UM --</option>';
  agregarOpcionesUnidades(select, unidades, valorSeleccionado);
}

// Habilita Enviar Alta solo si los campos y la clave son válidos.
function validarFormularioAlta() {
  validarDuplicadoEnFactura();
  document.getElementById("btEnvioFormAlta").disabled = !formularioAlta.checkValidity();
}

// Habilita Enviar Modificación si el formulario es válido.
function validarFormularioModificacion() {
  document.getElementById("btEnvioFormModi").disabled = !formularioModificacion.checkValidity();
}

// Lee los filtros, pide los registros y actualiza la tabla.
async function cargarDatos(silencioso) {
  var numeroPeticion = ++ultimaPeticionListado;
  var datos = new URLSearchParams();
  var hayFiltros = false;
  datos.append("orden", document.getElementById("orden").value);
  document.querySelectorAll(".filtros input, .filtros select").forEach(function(campo) {
    datos.append(campo.id, campo.value);
    if (campo.value !== "") hayFiltros = true;
  });
  if (!silencioso) alert("Lo que sale del navegador en el body:\n" + datos.toString());
  cuerpoTabla.innerHTML = '<tr><td colspan="11">Esperando respuesta…</td></tr>';
  try {
    var texto = await enviarPeticionAjax("salidaJsonRenglones.php", datos);
    // Una búsqueda anterior puede responder después de la más reciente.
    if (numeroPeticion !== ultimaPeticionListado) return;
    if (!silencioso) alert(texto);
    var respuesta = JSON.parse(texto);
    var filas = respuesta.renglones || [];
    if (!hayFiltros) guardarClavesExistentes(filas);
    dibujarTabla(filas);
    document.getElementById("pie").textContent = "Nro de registros: " + (respuesta.cantidad || 0);
  } catch (error) {
    if (!silencioso) alert("Error: " + error);
  }
}

// Crea las ocho celdas de datos y los tres botones de cada registro.
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
      // textContent muestra los datos como texto, aunque contengan etiquetas HTML.
      fila.insertCell().textContent = renglon[campo] == null ? "" : renglon[campo];
    });
    agregarBotonTabla(fila, "PDF", "btCeldaPdf", function() {
      mostrarPdf(renglon.nroFactura, renglon.codArticulo);
    });
    agregarBotonTabla(fila, "Modificar", "btCeldaModi", function() {
      abrirVentana("ventanaModalFormularioModi");
      completarFormularioModificacion(renglon);
    });
    agregarBotonTabla(fila, "Borrar", "btCeldaBaja", function() {
      eliminarRenglon(renglon.nroFactura, renglon.codArticulo);
    });
    cuerpoTabla.appendChild(fila);
  });
}

// Los tres botones de la tabla se crean de la misma manera.
function agregarBotonTabla(fila, texto, clase, accion) {
  var celda = fila.insertCell();
  var boton = document.createElement("button");
  boton.type = "button";
  boton.className = "btCelda " + clase;
  boton.textContent = texto;
  celda.appendChild(boton);
  celda.onclick = accion;
}

// Copia el registro elegido a los campos de modificación.
function completarFormularioModificacion(articulo) {
  var campos = formularioModificacion.elements; // Se accede a cada campo por su atributo name.
  campos.nroFactura.value = articulo.nroFactura;
  campos.fechaFactura.value = (articulo.fechaFactura || "").substring(0, 10);
  campos.codArticulo.value = articulo.codArticulo;
  campos.descripcion.value = articulo.descripcion;
  campos.cantidad.value = articulo.cantidad;
  campos.precioUnitario.value = articulo.precioUnitario;
  llenarSelectorUnidades(campos.unidadMedida, articulo.unidadMedida);
  validarFormularioModificacion();
}

// Envía el formulario nuevo y su PDF opcional a alta.php.
async function guardarAlta() {
  try {
    var datos = new FormData(formularioAlta); // Incluye los campos y el PDF elegido.
    var texto = await enviarPeticionAjax("alta.php", datos);
    mostrarRespuestaServidor(texto);
    cerrarFormularioAlta();
    await actualizarClavesExistentes(); // Primero actualiza las claves y después la tabla.
    cargarDatos(true);
  } catch (error) {
    alert("Error en alta: " + error);
  }
}

// Envía los cambios y el PDF opcional a modi.php.
async function guardarModificacion() {
  try {
    var datos = new FormData(formularioModificacion);
    var texto = await enviarPeticionAjax("modi.php", datos);
    mostrarRespuestaServidor(texto);
    cerrarFormularioModificacion();
    cargarDatos(true);
  } catch (error) {
    alert("Error en modi: " + error);
  }
}

// Pide confirmación y manda la clave del registro a baja.php.
async function eliminarRenglon(numeroFactura, codigoArticulo) {
  if (!confirm("¿Confirma la eliminación del renglón Factura " + numeroFactura + " / Artículo " + codigoArticulo + "?")) return;
  try {
    var datos = new FormData();
    datos.append("nroFactura", numeroFactura);
    datos.append("codArticulo", codigoArticulo);
    var texto = await enviarPeticionAjax("baja.php", datos);
    mostrarRespuestaServidor(texto);
    await actualizarClavesExistentes();
    cargarDatos(true);
  } catch (error) {
    alert("Error en baja: " + error);
  }
}

// Pide el PDF en Base64 y lo muestra dentro del iframe.
async function mostrarPdf(numeroFactura, codigoArticulo) {
  var url = "traeBinario.php?nroFactura=" + encodeURIComponent(numeroFactura) + "&codArticulo=" + encodeURIComponent(codigoArticulo);
  try {
    var texto = await enviarPeticionAjax(url, null);
    var respuesta = JSON.parse(texto);
    if (respuesta && respuesta.documentoPdf) {
      document.getElementById("iframePdf").src = "data:application/pdf;base64," + respuesta.documentoPdf;
      abrirVentana("ventanaModalPdf");
    } else {
      alert("No hay documento PDF asociado a este renglón.");
    }
  } catch (error) {
    alert("Error al obtener PDF: " + error);
  }
}

// CSS muestra la ventana cuando tiene la clase activo.
function abrirVentana(id) {
  document.getElementById("contenedorTablaArticulos").classList.add("contenedorPasivo");
  document.getElementById(id).classList.add("activo");
}

// Oculta el modal y vuelve a habilitar el tablero.
function cerrarVentana(id) {
  document.getElementById(id).classList.remove("activo");
  document.getElementById("contenedorTablaArticulos").classList.remove("contenedorPasivo");
}

// Muestra la traza que devolvió el PHP de alta, cambio o baja.
function mostrarRespuestaServidor(texto) {
  alert(texto);
  abrirVentana("ventanaModalRespuesta");
  var contenido = document.getElementById("contenidoModalRespuesta");
  contenido.textContent = ""; // Vacía la respuesta anterior (equivale a empty() de jQuery).
  contenido.append(texto); // append agrega el texto sin interpretar etiquetas HTML.
}

// Cierra el modal de alta.
function cerrarFormularioAlta() { cerrarVentana("ventanaModalFormularioAlta"); }
// Cierra el modal de modificación.
function cerrarFormularioModificacion() { cerrarVentana("ventanaModalFormularioModi"); }
// Cierra el mensaje con la respuesta del PHP.
function cerrarRespuestaServidor() { cerrarVentana("ventanaModalRespuesta"); }
// Cierra el visor y deja de mostrar el documento anterior.
function cerrarVistaPreviaPdf() {
  cerrarVentana("ventanaModalPdf");
  document.getElementById("iframePdf").src = ""; // Quita del visor el documento que se acaba de cerrar.
}
