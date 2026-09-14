function validarDia() {
  var valor = document.getElementById("diaDelMes").value;
  if (valor === "") {
    return;
  }
  var n = Number(valor);
  if (isNaN(n) || n < 1 || n > 31) {
    alert("El valor debe estar entre 1 y 31");
    document.getElementById("diaDelMes").value = "";
  }
}

function validarMes() {
  var valor = document.getElementById("mes").value;
  if (valor === "") {
    return;
  }
  var n = Number(valor);
  if (isNaN(n) || n < 1 || n > 12) {
    alert("El valor debe estar entre 1 y 12");
    document.getElementById("mes").value = "";
  }
}
