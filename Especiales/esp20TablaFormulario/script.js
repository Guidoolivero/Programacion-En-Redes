$(document).ready(function () {
    document.getElementById("contenedor").className = "contenedorActivo";
    document.getElementById("ventanaModal").className = "ventanaModalApagado";
    creaOpciones(JSON.parse(textoUnidadesDeMedida));
    $("#btCargar").click(function () { cargarDatos(); });
    $("#btVaciar").click(function () { $("#tbDatos").empty(); });
    $("#btCargarForm").click(function () { abrirModal(); });
    $("#btCerrarModal").click(function () { cerrarModal(); });
    $("#cantidad, #precioUnitario").on("input", calcularImporte);
    $("#formAlta").submit(function (e) { e.preventDefault(); alert("Formulario validado."); });
});
function creaOpciones(objJson) {
    var select = document.getElementById("selectUnidadMedida");
    $(select).empty();
    objJson.unidadesDeMedida.forEach(function (argValor) {
        var objOpcion = document.createElement("option");
        objOpcion.setAttribute("value", argValor.codUm);
        objOpcion.innerHTML = argValor.descripcionUm;
        select.appendChild(objOpcion);
    });
}
function cargarDatos() {
    $("#tbDatos").empty();
    var objJson = JSON.parse(textoJSONRenglones);
    var objTbDatos = document.getElementById("tbDatos");
    objJson.renglones.forEach(function (argValor) {
        var objTr = document.createElement("tr");
        agregarCelda(objTr, "nroFactura", argValor.nroFactura);
        agregarCelda(objTr, "fechaFactura", argValor.fechaFactura);
        agregarCelda(objTr, "codArticulo", argValor.codArticulo);
        agregarCelda(objTr, "descripcion", argValor.descripcion);
        agregarCelda(objTr, "codUm", argValor.codUm);
        agregarCelda(objTr, "cantidad", argValor.cantidad);
        agregarCelda(objTr, "precioUnitario", Number(argValor.precioUnitario).toLocaleString("es-AR"));
        agregarCelda(objTr, "importeRenglon", Number(argValor.importeRenglon).toLocaleString("es-AR"));
        objTbDatos.appendChild(objTr);
    });
}
function agregarCelda(objTr, nombreCampo, valor) {
    var objTd = document.createElement("td");
    objTd.setAttribute("campo-dato", nombreCampo);
    objTd.innerHTML = valor;
    objTr.appendChild(objTd);
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
    var cant = parseFloat(document.getElementById("cantidad").value) || 0;
    var precio = parseFloat(document.getElementById("precioUnitario").value) || 0;
    document.getElementById("importeRenglon").value = (cant * precio).toFixed(2);
}
