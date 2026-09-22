$(document).ready(function () {
    const objJson = JSON.parse(textoUnidadesDeMedida);
    creaOpciones(objJson);

    $("#cantidad, #precioUnitario").on("input", calcularImporte);

    $("#formAlta").submit(function (e) {
        e.preventDefault();
        alert("Formulario validado. En un entorno real se enviaría el renglón de factura.");
    });
});

function creaOpciones(objJson) {
    const select = document.getElementById("selectUnidadMedida");
    objJson.unidadesDeMedida.forEach(function (argValor, argIndice) {
        const objOpcion = document.createElement("option");
        objOpcion.setAttribute("class", "elementoOptionSelect");
        objOpcion.setAttribute("value", argValor.codUm);
        objOpcion.textContent = argValor.descripcionUm;
        select.appendChild(objOpcion);
    });
}

function calcularImporte() {
    const cant = parseFloat(document.getElementById("cantidad").value) || 0;
    const precio = parseFloat(document.getElementById("precioUnitario").value) || 0;
    document.getElementById("importeRenglon").value = (cant * precio).toFixed(2);
}
