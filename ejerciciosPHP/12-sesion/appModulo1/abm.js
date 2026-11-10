var datosUnidades = { unidades: [] }; // Conserva las unidades recibidas del servidor.
var clavesRenglonesExistentes = []; // Guarda claves como 1001||ART-02.
var temporizadorFiltro = null; // Identifica la espera pendiente del filtro.
var ultimaPeticionListado = 0; // Cuenta las búsquedas para reconocer respuestas viejas.
var cuerpoTabla; // Apuntará al tbody donde se dibujan los registros.
var campoCodigoArticuloAlta; // Apuntará al código del artículo en el alta.

document.addEventListener("DOMContentLoaded", function() { // Ejecuta este bloque cuando el HTML ya está disponible.
  cuerpoTabla = document.getElementById("tbDatos"); // Guarda la referencia al cuerpo de la tabla.
  // Guarda la referencia al campo del código.
  campoCodigoArticuloAlta = document.getElementById("formArticulosEntCodArtAlta");
  document.getElementById("orden").value = "nroFactura"; // El listado comienza ordenado por número de factura.
  document.getElementById("btEnvioFormAlta").disabled = true; // El envío queda bloqueado hasta completar datos válidos.
  document.getElementById("btEnvioFormModi").disabled = true; // El envío se habilitará al validar la ficha.
  cargarUnidades(); // Trae las opciones de los selectores desde PHP.
  actualizarClavesExistentes(); // Refresca las claves antes de validar un alta.

  document.getElementById("btCierraSesion").onclick = function() { // Este clic navega al PHP que destruye la sesión.
    location.href = "../destruirsesion.php"; // Le pide al navegador abrir esa dirección.
  };
  document.getElementById("btnCargar").onclick = function() { // Este clic solicita los registros al servidor.
    cargarDatos(false); // false permite ver las alertas del pedido y la respuesta.
  };
  document.getElementById("btnVaciar").onclick = function() { // Vacía solo la pantalla; no borra datos de MySQL.
    cuerpoTabla.innerHTML = '<tr><td colspan="11">Sin datos.</td></tr>'; // Una celda ocupa las once columnas de la tabla.
    // Actualiza el contador visible, no la base de datos.
    document.getElementById("pie").textContent = "Nro de registros: 0";
  };
  document.getElementById("btnLimpiar").onclick = function() { // Este clic limpia los cinco filtros y vuelve a cargar.
    // Selecciona todos los campos de filtro del HTML.
    document.querySelectorAll(".filtros input, .filtros select").forEach(function(campo) {
      campo.value = ""; // Deja vacío el campo actual.
    });
    cargarDatos(true); // true recarga los datos sin alertas didácticas.
  };
  document.querySelectorAll("th[data-campo]").forEach(function(titulo) { // Recorre los encabezados que permiten ordenar.
    titulo.onclick = function() { // Guarda qué hacer al tocar este encabezado.
      document.getElementById("orden").value = titulo.dataset.campo; // Copia el nombre guardado en el atributo data-campo.
      cargarDatos(true); // true recarga los datos sin alertas didácticas.
    };
  });

  document.querySelectorAll("#f_nroFactura, #f_codArticulo, #f_descripcion").forEach(function(campo) {
    campo.oninput = programarFiltro; // Busca después de escribir o pegar texto.
    campo.onkeydown = function(evento) { // Revisa si la tecla presionada fue Enter.
      if (evento.key === "Enter") { // Enter permite buscar sin esperar los 300 ms.
        evento.preventDefault(); // Evita el envío tradicional y la recarga de la página.
        clearTimeout(temporizadorFiltro); // Cancela la búsqueda que estaba esperando.
        cargarDatos(true); // true recarga los datos sin alertas didácticas.
      }
    };
  });
  document.querySelectorAll("#f_unidadMedida, #f_fechaFactura").forEach(function(campo) {
    campo.onchange = function() { // Unidad y fecha buscan cuando cambia su valor.
      clearTimeout(temporizadorFiltro); // Cancela la búsqueda que estaba esperando.
      cargarDatos(true); // true recarga los datos sin alertas didácticas.
    };
    campo.oninput = campo.onchange; // Reutiliza el mismo comportamiento al editar el campo.
  });

  document.getElementById("btnAlta").onclick = function() { // Abre un formulario vacío para un registro nuevo.
    document.getElementById("formArticulosAlta").reset(); // Restaura los valores iniciales del formulario.
    campoCodigoArticuloAlta.setCustomValidity(""); // Quita el error personalizado de duplicado.
    document.getElementById("msgErrorCodArtAlta").textContent = ""; // Limpia el mensaje del alta anterior.
    document.getElementById("msgErrorCodArtAlta").style.display = "none"; // Oculta el mensaje de error.
    campoCodigoArticuloAlta.style.borderColor = ""; // Restaura el borde normal del campo.
    actualizarClavesExistentes(); // Refresca las claves antes de validar un alta.
    // Carga las opciones del selector de unidades.
    llenarSelectorUnidades(document.getElementById("formArticulosEntUmAlta"));
    // Propone la fecha actual en formato AAAA-MM-DD.
    document.getElementById("formArticulosEntFechaAlta").value = new Date().toISOString().substring(0, 10);
    document.getElementById("btEnvioFormAlta").disabled = true; // El envío queda bloqueado hasta completar datos válidos.
    abrirVentana("ventanaModalFormularioAlta"); // Muestra el modal de alta.
  };
  document.querySelectorAll("#formArticulosAlta input, #formArticulosAlta select").forEach(function(campo) {
    campo.onkeyup = validarFormularioAlta; // Valida el alta al soltar una tecla.
    campo.onchange = validarFormularioAlta; // Valida el alta al cambiar un valor.
    campo.oninput = validarFormularioAlta; // Valida el alta al escribir o pegar.
    campo.onblur = validarFormularioAlta; // Valida el alta al salir de un campo.
  });
  document.querySelectorAll("#formArticulosModi input, #formArticulosModi select").forEach(function(campo) {
    campo.onkeyup = validarFormularioModificacion; // Valida los cambios al soltar una tecla.
    campo.onchange = validarFormularioModificacion; // Valida los cambios al elegir otro valor.
    campo.oninput = validarFormularioModificacion; // Valida los cambios mientras se escribe.
  });
  // Se ejecuta al enviar el formulario, no al abrirlo.
  document.getElementById("formArticulosAlta").onsubmit = function(evento) {
    evento.preventDefault(); // Evita el envío tradicional y la recarga de la página.
    validarDuplicadoEnFactura(); // Actualiza el error de factura + artículo repetido.
    if (!this.checkValidity()) { // Si falta un requerido o hay un error, no envía.
      validarFormularioAlta();
      return;
    }
    guardarAlta(); // Envía el formulario válido por AJAX.
  };
  document.getElementById("formArticulosModi").onsubmit = function(evento) { // Se ejecuta al pulsar Enviar Modificación.
    evento.preventDefault(); // Evita el envío tradicional y la recarga de la página.
    guardarModificacion(); // Envía los cambios del formulario por AJAX.
  };
});

// Espera 300 ms desde la última escritura antes de buscar.
function programarFiltro() {
  clearTimeout(temporizadorFiltro); // Cancela la búsqueda que estaba esperando.
  temporizadorFiltro = setTimeout(function() { // Programa una acción para más adelante, sin bloquear la página.
    cargarDatos(true); // true recarga los datos sin alertas didácticas.
  }, 300); // La espera es de 300 milisegundos desde la última escritura.
}

// AJAX común a todas las consultas: al terminar, llama a la función recibida.
// Petición común: envía con fetch y entrega la respuesta al callback.
async function enviarPeticionAjax(url, datos, alResponder, alError) {
  var opciones = { method: "GET" }; // GET es el método inicial para las consultas sin cuerpo.
  if (datos !== null) { // Si hay datos, se mandan en el cuerpo de un POST.
    opciones.method = "POST"; // POST envía campos al PHP mediante el body.
    opciones.body = datos; // Puede ser URLSearchParams o FormData con un archivo.
  }
  try {
    var respuesta = await fetch(url, opciones); // Espera la respuesta HTTP sin bloquear los demás eventos.
    var texto = await respuesta.text(); // Lee el cuerpo de la respuesta como texto.
    alResponder(texto); // Ejecuta el callback de respuesta que recibió esta función.
  } catch (error) {
    if (alError) alError(error); // Si se pasó un callback de error, le entrega el problema.
  }
}

// Recuerda los pares factura + artículo para validar duplicados.
function guardarClavesExistentes(filas) {
  clavesRenglonesExistentes = []; // Borra las claves anteriores antes de reconstruirlas.
  filas.forEach(function(renglon) { // Recorre los registros recibidos; renglon es la fila actual.
    if (renglon.nroFactura && renglon.codArticulo) { // Solo arma claves cuando existen sus dos componentes.
      // Une factura y artículo sin espacios externos ni diferencias de mayúsculas.
      var clave = String(renglon.nroFactura).trim().toUpperCase() + "||" + String(renglon.codArticulo).trim().toUpperCase();
      clavesRenglonesExistentes.push(clave); // Agrega la combinación al arreglo de claves conocidas.
    }
  });
}

// Consulta todas las claves; al terminar puede recargar la tabla.
function actualizarClavesExistentes(alTerminar) {
  var datos = new URLSearchParams(); // Prepara campos para enviar; todavía no hace la petición.
  datos.append("orden", "nroFactura"); // Pide el listado completo ordenado por factura.
  enviarPeticionAjax("salidaJsonRenglones.php", datos, function(texto) { // Este callback corre cuando llega la respuesta.
    var respuesta = JSON.parse(texto); // Convierte el texto JSON recibido en un objeto JavaScript.
    guardarClavesExistentes(respuesta.renglones || []); // Reconstruye las claves con el listado completo.
    // Revalida los campos si la ventana de alta sigue abierta.
    if (document.getElementById("ventanaModalFormularioAlta").classList.contains("activo")) {
      validarFormularioAlta();
    }
    if (alTerminar) alTerminar(); // Ejecuta la acción pendiente; por ejemplo, recargar la tabla.
  }, function() {
    // La sincronización es silenciosa, pero la recarga de la tabla debe continuar.
    if (alTerminar) alTerminar(); // Ejecuta la acción pendiente; por ejemplo, recargar la tabla.
  });
}

// Impide repetir un artículo dentro de la misma factura.
function validarDuplicadoEnFactura() {
  if (!campoCodigoArticuloAlta) return; // Sale si todavía no se guardó la referencia al input.
  // Lee la factura y quita espacios de los extremos.
  var numeroFactura = document.getElementById("formArticulosEntNroAlta").value.trim();
  var codigoArticulo = campoCodigoArticuloAlta.value.trim(); // Lee el artículo y quita espacios de los extremos.
  // Arma la misma clave usada en el listado de existentes.
  var clave = numeroFactura.toUpperCase() + "||" + codigoArticulo.toUpperCase();
  var mensaje = document.getElementById("msgErrorCodArtAlta"); // Obtiene el lugar donde se muestra el error.
  // Busca si esa combinación ya estaba cargada.
  if (numeroFactura !== "" && codigoArticulo !== "" && clavesRenglonesExistentes.includes(clave)) {
    // Marca el campo inválido para impedir enviar el alta.
    campoCodigoArticuloAlta.setCustomValidity("Ese artículo ya está cargado en la misma factura.");
    // Explica al usuario qué factura contiene ese artículo.
    mensaje.textContent = '⚠️ La factura "' + numeroFactura + '" ya contiene el artículo "' + codigoArticulo + '" (sí podés cargarlo en otra factura).';
    mensaje.style.display = ""; // Vuelve a mostrar el mensaje de duplicado.
    campoCodigoArticuloAlta.style.borderColor = "#e53e3e"; // Resalta el campo que tiene el error.
  } else {
    campoCodigoArticuloAlta.setCustomValidity(""); // Quita el error personalizado de duplicado.
    mensaje.textContent = ""; // Borra el error cuando la combinación es válida.
    mensaje.style.display = "none"; // Oculta el mensaje que ya no corresponde.
    campoCodigoArticuloAlta.style.borderColor = ""; // Restaura el borde normal del campo.
  }
}

// Pide las unidades al PHP y las conserva para los selectores.
function cargarUnidades() {
  enviarPeticionAjax("salidaJsonUnidades.php", null, function(texto) {
    alert(texto);
    datosUnidades = JSON.parse(texto); // Conserva las unidades para reutilizarlas en los formularios.
    llenarFiltroUnidades(datosUnidades); // Carga las opciones del filtro de unidades.
  }, function(error) { // Este callback recibe fallos de red o de procesamiento.
    alert("Error unidades: " + error);
  });
}

// Crea una opción por unidad; puede dejar una seleccionada.
function agregarOpcionesUnidades(select, unidades, valorSeleccionado) {
  unidades.forEach(function(unidad) { // Recorre cada unidad recibida del servidor.
    // Elige el código que viajará al PHP al seleccionar la opción.
    var valor = unidad.cod_um || unidad.codigo || unidad.codUm || unidad.id || unidad.descripcion || "";
    // Elige la descripción que verá el usuario.
    var nombre = unidad.descripcion || unidad.descripcionUm || unidad.nombre || valor;
    var opcion = document.createElement("option"); // Crea una opción nueva del selector.
    opcion.className = "elementoOptionSelect";
    opcion.value = valor; // Guarda el valor que se enviará al servidor.
    opcion.textContent = nombre; // Escribe el texto visible sin interpretar HTML.
    if (valor == valorSeleccionado) opcion.selected = true; // Deja elegida la unidad que tenía el registro.
    select.appendChild(opcion); // Agrega la opción al selector.
  });
}

// Llena el filtro de unidades conservando la opción de todas.
function llenarFiltroUnidades(respuestaUnidades) {
  var select = document.getElementById("f_unidadMedida");
  while (select.options.length > 1) select.remove(1); // Borra opciones anteriores pero conserva la opción de todas.
  agregarOpcionesUnidades(select, respuestaUnidades.unidades || []);
}

// Prepara el selector del formulario de alta o modificación.
function llenarSelectorUnidades(select, valorSeleccionado) {
  // La opción vacía obliga a elegir una unidad en el formulario.
  select.innerHTML = '<option value="">-- Seleccione UM --</option>';
  agregarOpcionesUnidades(select, datosUnidades.unidades || [], valorSeleccionado);
}

// Habilita Enviar Alta solo si los campos y la clave son válidos.
function validarFormularioAlta() {
  validarDuplicadoEnFactura(); // Actualiza el error de factura + artículo repetido.
  // checkValidity revisa requeridos y errores personalizados.
  document.getElementById("btEnvioFormAlta").disabled = !document.getElementById("formArticulosAlta").checkValidity();
}

// Habilita Enviar Modificación si el formulario es válido.
function validarFormularioModificacion() {
  // Habilita el envío solo si los campos de modificación son válidos.
  document.getElementById("btEnvioFormModi").disabled = !document.getElementById("formArticulosModi").checkValidity();
}

// Lee los filtros, pide los registros y actualiza la tabla.
function cargarDatos(silencioso) {
  var numeroPeticion = ++ultimaPeticionListado; // Numera esta búsqueda para descartar respuestas anteriores.
  var datos = new URLSearchParams(); // Prepara campos para enviar; todavía no hace la petición.
  // Son los ID de los cinco campos de filtro del HTML.
  var filtros = ["f_nroFactura", "f_codArticulo", "f_unidadMedida", "f_descripcion", "f_fechaFactura"];
  var hayFiltros = false; // Todavía no se encontró ningún filtro completado.
  datos.append("orden", document.getElementById("orden").value); // Agrega el campo por el que se ordenará el listado.
  filtros.forEach(function(id) { // Revisa un campo de filtro por cada vuelta.
    var valor = document.getElementById(id).value; // Lee lo escrito o seleccionado en el campo actual.
    datos.append(id, valor); // Agrega nombre y valor del filtro al pedido.
    if (valor !== "") hayFiltros = true; // Recuerda que al menos un filtro tiene contenido.
  });
  // Con false muestra el body que se enviará al PHP.
  if (!silencioso) alert("Lo que sale del navegador en el body:\n" + datos.toString());
  // Mientras espera, reemplaza las filas por este aviso.
  cuerpoTabla.innerHTML = '<tr><td colspan="11">Esperando respuesta…</td></tr>';
  enviarPeticionAjax("salidaJsonRenglones.php", datos, function(texto) { // Este callback corre cuando llega la respuesta.
    // Una búsqueda anterior puede responder después de la más reciente.
    if (numeroPeticion !== ultimaPeticionListado) return; // Descarta esta respuesta si ya se inició otra búsqueda.
    if (!silencioso) alert(texto); // Muestra la respuesta cruda solamente en modo no silencioso.
    var respuesta = JSON.parse(texto); // Convierte el texto JSON recibido en un objeto JavaScript.
    var filas = respuesta.renglones || []; // Usa los registros recibidos; si faltan, usa un arreglo vacío.
    if (!hayFiltros) guardarClavesExistentes(filas); // Solo un listado sin filtros contiene todas las claves.
    dibujarTabla(filas); // Crea las filas visibles con los registros encontrados.
    // Actualiza el contador con la cantidad devuelta por PHP.
    document.getElementById("pie").textContent = "Nro de registros: " + (respuesta.cantidad || 0);
  }, function(error) { // Este callback recibe fallos de red o de procesamiento.
    if (!silencioso) alert("Error: " + error); // En modo no silencioso muestra el fallo de la consulta.
  });
}

// Crea las ocho celdas de datos y los tres botones de cada registro.
function dibujarTabla(filas) { // Crea las filas visibles con los registros encontrados.
  cuerpoTabla.innerHTML = ""; // Retira las filas anteriores antes de dibujar las nuevas.
  if (!filas.length) { // Si el arreglo está vacío, muestra Sin resultados y termina.
    cuerpoTabla.innerHTML = '<tr><td colspan="11">Sin resultados.</td></tr>';
    return;
  }
  // Define el orden de las ocho columnas de datos.
  var campos = ["nroFactura", "codArticulo", "unidadMedida", "descripcion", "fechaFactura", "cantidad", "precioUnitario", "importeRenglon"];
  filas.forEach(function(renglon) { // Recorre los registros recibidos; renglon es la fila actual.
    var fila = document.createElement("tr"); // Crea la fila HTML del registro actual.
    campos.forEach(function(campo) { // Crea una celda para cada dato de esa fila.
      var celda = document.createElement("td"); // Crea una celda de la tabla.
      // Muestra el valor como texto; null o undefined quedan vacíos.
      celda.textContent = renglon[campo] == null ? "" : renglon[campo];
      fila.appendChild(celda); // Agrega la celda de datos a la fila actual.
    });
    var celdaPdf = document.createElement("td");
    celdaPdf.setAttribute("campo-dato", "articulos_btPdf");
    celdaPdf.innerHTML = '<button type="button" class="btCelda btCeldaPdf">PDF</button>';
    // Este clic pide el PDF del registro de esta fila.
    celdaPdf.onclick = function() { mostrarPdf(renglon.nroFactura, renglon.codArticulo); };
    fila.appendChild(celdaPdf);

    var celdaModificar = document.createElement("td");
    celdaModificar.setAttribute("campo-dato", "articulos_btModi");
    celdaModificar.innerHTML = '<button type="button" class="btCelda btCeldaModi">Modificar</button>';
    celdaModificar.onclick = function() { // Este clic abre el formulario; todavía no guarda cambios.
      abrirVentana("ventanaModalFormularioModi");
      completarFormularioModificacion(renglon); // Copia los datos de esta fila al formulario.
    };
    fila.appendChild(celdaModificar);

    var celdaBorrar = document.createElement("td");
    celdaBorrar.setAttribute("campo-dato", "articulos_btBaja");
    celdaBorrar.innerHTML = '<button type="button" class="btCelda btCeldaBaja">Borrar</button>';
    // Este clic confirma y solicita borrar el registro actual.
    celdaBorrar.onclick = function() { eliminarRenglon(renglon.nroFactura, renglon.codArticulo); };
    fila.appendChild(celdaBorrar);
    cuerpoTabla.appendChild(fila); // Agrega la fila completa a la tabla visible.
  });
}

// Copia el registro elegido a los campos de modificación.
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

// Envía el formulario nuevo y su PDF opcional a alta.php.
function guardarAlta() {
  // Reúne los campos del formulario y el PDF opcional.
  var datos = new FormData(document.getElementById("formArticulosAlta"));
  enviarPeticionAjax("alta.php", datos, function(texto) {
    alert(texto);
    mostrarRespuestaServidor(texto);
    cerrarFormularioAlta();
    actualizarClavesExistentes(function() { cargarDatos(true); }); // true recarga los datos sin alertas didácticas.
  }, function(error) { // Este callback recibe fallos de red o de procesamiento.
    alert("Error en alta: " + error);
  });
}

// Envía los cambios y el PDF opcional a modi.php.
function guardarModificacion() {
  // Reúne los campos del formulario y el PDF opcional.
  var datos = new FormData(document.getElementById("formArticulosModi"));
  enviarPeticionAjax("modi.php", datos, function(texto) {
    alert(texto);
    mostrarRespuestaServidor(texto);
    cerrarFormularioModificacion();
    cargarDatos(true); // true recarga los datos sin alertas didácticas.
  }, function(error) { // Este callback recibe fallos de red o de procesamiento.
    alert("Error en modi: " + error);
  });
}

// Pide confirmación y manda la clave del registro a baja.php.
function eliminarRenglon(numeroFactura, codigoArticulo) {
  // Si el usuario cancela la confirmación, no se borra nada.
  if (!confirm("¿Confirma la eliminación del renglón Factura " + numeroFactura + " / Artículo " + codigoArticulo + "?")) return;
  var datos = new FormData(); // Prepara un cuerpo nuevo para enviar la clave del registro.
  datos.append("nroFactura", numeroFactura); // Envía el primer componente de la clave.
  datos.append("codArticulo", codigoArticulo); // Envía el segundo componente de la clave.
  enviarPeticionAjax("baja.php", datos, function(texto) {
    alert(texto);
    mostrarRespuestaServidor(texto);
    actualizarClavesExistentes(function() { cargarDatos(true); }); // true recarga los datos sin alertas didácticas.
  }, function(error) { // Este callback recibe fallos de red o de procesamiento.
    alert("Error en baja: " + error);
  });
}

// Pide el PDF en Base64 y lo muestra dentro del iframe.
function mostrarPdf(numeroFactura, codigoArticulo) {
  // Codifica factura y artículo para pasarlos por la URL.
  var url = "traeBinario.php?nroFactura=" + encodeURIComponent(numeroFactura) + "&codArticulo=" + encodeURIComponent(codigoArticulo);
  enviarPeticionAjax(url, null, function(texto) {
    var respuesta = JSON.parse(texto); // Convierte el texto JSON recibido en un objeto JavaScript.
    if (respuesta && respuesta.documentoPdf) { // Comprueba que la respuesta realmente tenga un PDF.
      // Carga la vista previa con el Base64 recibido del servidor.
      document.getElementById("iframePdf").src = "data:application/pdf;base64," + respuesta.documentoPdf;
      abrirVentana("ventanaModalPdf");
    } else {
      alert("No hay documento PDF asociado a este renglón.");
    }
  }, function(error) { // Este callback recibe fallos de red o de procesamiento.
    alert("Error al obtener PDF: " + error);
  });
}

// Muestra el modal y bloquea los clics en el tablero de atrás.
function abrirVentana(id) {
  document.getElementById("contenedorTablaArticulos").classList.add("contenedorPasivo");
  var modal = document.getElementById(id);
  modal.style.visibility = "visible"; // Hace visible la ventana seleccionada.
  modal.classList.add("activo");
}

// Oculta el modal y vuelve a habilitar el tablero.
function cerrarVentana(id) {
  var modal = document.getElementById(id);
  modal.style.visibility = "hidden"; // Oculta la ventana seleccionada.
  modal.classList.remove("activo");
  document.getElementById("contenedorTablaArticulos").classList.remove("contenedorPasivo");
}

// Muestra la traza que devolvió el PHP de alta, cambio o baja.
function mostrarRespuestaServidor(texto) {
  abrirVentana("ventanaModalRespuesta");
  document.getElementById("encabezadoModalRespuesta").textContent = "Respuesta del server: ";
  // Muestra el contenido de la respuesta del servidor.
  document.getElementById("contenidoModalRespuesta").innerHTML = texto;
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
