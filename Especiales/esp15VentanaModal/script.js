$(document).ready(function () {
    alert("Ancho de la ventana: " + window.innerWidth + " px");

    document.getElementById("contenedor").className = "contenedorActivo";
    document.getElementById("ventanaModal").className = "ventanaModalApagado";

    const objJson = JSON.parse(textoUnidadesDeMedida);
    creaOpciones(objJson);

    $("#btAbrirModal").click(function () {
        abrirModal();
    });

    $("#btCerrarModal").click(function () {
        cerrarModal();
    });

    $("#cantidad, #precioUnitario").on("input", calcularImporte);

    $("#formAlta").submit(function (e) {
        e.preventDefault();
        alert("Formulario validado.");
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

function abrirModal() {
    document.getElementById("contenedor").className = "contenedorPasivo";
    document.getElementById("ventanaModal").className = "ventanaModalPrendido";
}

function cerrarModal() {
    document.getElementById("contenedor").className = "contenedorActivo";
    document.getElementById("ventanaModal").className = "ventanaModalApagado";
}

function calcularImporte() {
    const cant = parseFloat(document.getElementById("cantidad").value) || 0;
    const precio = parseFloat(document.getElementById("precioUnitario").value) || 0;
    document.getElementById("importeRenglon").value = (cant * precio).toFixed(2);
}
