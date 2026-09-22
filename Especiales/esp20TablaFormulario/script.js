$(document).ready(function () {
    document.getElementById("contenedor").className = "contenedorActivo";
    document.getElementById("ventanaModal").className = "ventanaModalApagado";

    const objUm = JSON.parse(textoUnidadesDeMedida);
    creaOpciones(objUm);

    $("#btCargar").click(function () {
        cargarDatos();
    });

    $("#btVaciar").click(function () {
        $("#tbDatos").empty();
    });

    $("#btCargarForm").click(function () {
        abrirModal();
    });

    $("#btCerrarModal").click(function () {
        cerrarModal();
    });

    $("#cantidad, #precioUnitario").on("input", calcularImporte);

    $("#formAlta").submit(function (e) {
        e.preventDefault();
        alert("Formulario validado. El renglón no se persiste: aún no hay backend.");
    });
});

function creaOpciones(objJson) {
    const select = document.getElementById("selectUnidadMedida");
    $(select).empty();
    objJson.unidadesDeMedida.forEach(function (argValor, argIndice) {
        const objOpcion = document.createElement("option");
        objOpcion.setAttribute("class", "elementoOptionSelect");
        objOpcion.setAttribute("value", argValor.codUm);
        objOpcion.textContent = argValor.descripcionUm;
        select.appendChild(objOpcion);
    });
}

function cargarDatos() {
    $("#tbDatos").empty();
    const objJson = JSON.parse(textoJSONRenglones);
    const objTbDatos = document.getElementById("tbDatos");

    objJson.renglones.forEach(function (argValor, argIndice) {
        const objTr = document.createElement("tr");
        agregarCelda(objTr, "nroFactura", argValor.nroFactura);
        agregarCelda(objTr, "fechaFactura", argValor.fechaFactura);
        agregarCelda(objTr, "codArticulo", argValor.codArticulo);
        agregarCelda(objTr, "descripcion", argValor.descripcion);
        agregarCelda(objTr, "codUm", argValor.codUm);
        agregarCelda(objTr, "cantidad", argValor.cantidad);
        agregarCelda(objTr, "precioUnitario", formatearNumero(argValor.precioUnitario));
        agregarCelda(objTr, "importeRenglon", formatearNumero(argValor.importeRenglon));
        objTbDatos.appendChild(objTr);
    });
}

function agregarCelda(objTr, nombreCampo, valor) {
    const objTd = document.createElement("td");
    objTd.setAttribute("campo-dato", nombreCampo);
    objTd.textContent = valor;
    objTr.appendChild(objTd);
}

function formatearNumero(valor) {
    return Number(valor).toLocaleString("es-AR");
}

function abrirModal() {
    $("#contenedor").attr("class", "contenedorPasivo");
    $("#ventanaModal").attr("class", "ventanaModalPrendido");
}

function cerrarModal() {
    $("#contenedor").attr("class", "contenedorActivo");
    $("#ventanaModal").attr("class", "ventanaModalApagado");
}

function calcularImporte() {
    const cant = parseFloat(document.getElementById("cantidad").value) || 0;
    const precio = parseFloat(document.getElementById("precioUnitario").value) || 0;
    document.getElementById("importeRenglon").value = (cant * precio).toFixed(2);
}
