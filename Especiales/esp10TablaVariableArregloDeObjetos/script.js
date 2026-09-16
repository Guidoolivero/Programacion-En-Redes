$(document).ready(function () {
    $("#btCargar").click(function () { cargarDatos(); });
    $("#btVaciar").click(function () { $("#tbDatos").empty(); });
});
function cargarDatos() {
    $("#tbDatos").empty();
    var objJson = JSON.parse(textoJSONRenglones);
    var objTbDatos = document.getElementById("tbDatos");
    objJson.renglones.forEach(function (argValor, argIndice) {
        var objTr = document.createElement("tr");
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
    var objTd = document.createElement("td");
    objTd.setAttribute("campo-dato", nombreCampo);
    objTd.innerHTML = valor;
    objTr.appendChild(objTd);
}
function formatearNumero(valor) { return Number(valor).toLocaleString("es-AR"); }
