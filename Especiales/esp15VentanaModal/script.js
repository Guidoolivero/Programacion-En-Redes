$(document).ready(function () {
    alert("Ancho de la ventana: " + window.innerWidth + " px");
    document.getElementById("contenedor").className = "contenedorActivo";
    document.getElementById("ventanaModal").className = "ventanaModalApagado";
    creaOpciones(JSON.parse(textoUnidadesDeMedida));
    $("#btAbrirModal").click(function () { abrirModal(); });
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
function abrirModal() {
    document.getElementById("contenedor").className = "contenedorPasivo";
    document.getElementById("ventanaModal").className = "ventanaModalPrendido";
}
function cerrarModal() {
    document.getElementById("contenedor").className = "contenedorActivo";
    document.getElementById("ventanaModal").className = "ventanaModalApagado";
}
function calcularImporte() {
    var cant = parseFloat(document.getElementById("cantidad").value) || 0;
    var precio = parseFloat(document.getElementById("precioUnitario").value) || 0;
    document.getElementById("importeRenglon").value = (cant * precio).toFixed(2);
}
