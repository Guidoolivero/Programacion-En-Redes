var z;
let q;
var a = 1;
var b = "1";
var c = 2;
var suma1;
var suma2;

function pintar(titulo, valor, tipo) {
  document.getElementById("salida").innerHTML =
    "<p><strong>" + titulo + "</strong> " + valor + "</p>" +
    "<p><strong>Tipo de " + titulo.replace("Valor de ", "").replace(":", "") + "</strong> " + tipo + "</p>";
}

function mostrarZ() {
  pintar("Valor de z:", String(z), typeof z);
}

function mostrarQ() {
  q = "Nuevo valor para q";
  document.getElementById("salida").innerHTML =
    "<p><strong>Valor de q:</strong> " + q + "</p>" +
    "<p><strong>Tipo de q:</strong> " + typeof q + "</p>";
}

function mostrarA() {
  document.getElementById("salida").innerHTML =
    "<p><strong>Valor de a:</strong> " + a + "</p>" +
    "<p><strong>Tipo de a:</strong> " + typeof a + "</p>";
}

function mostrarB() {
  document.getElementById("salida").innerHTML =
    "<p><strong>Valor de b:</strong> " + b + "</p>" +
    "<p><strong>Tipo de b:</strong> " + typeof b + "</p>";
}

function mostrarC() {
  document.getElementById("salida").innerHTML =
    "<p><strong>Valor de c:</strong> " + c + "</p>" +
    "<p><strong>Tipo de c:</strong> " + typeof c + "</p>";
}

function mostrarSuma1() {
  suma1 = a + b;
  document.getElementById("salida").innerHTML =
    "<p><strong>Valor de suma1 (a + b):</strong> " + suma1 + "</p>" +
    "<p><strong>Tipo de suma1:</strong> " + typeof suma1 + "</p>" +
    "<p>a es number y b es string → JavaScript concatena: 1 + \"1\" = \"11\"</p>";
}

function mostrarSuma2() {
  suma2 = a + c;
  document.getElementById("salida").innerHTML =
    "<p><strong>Valor de suma2 (a + c):</strong> " + suma2 + "</p>" +
    "<p><strong>Tipo de suma2:</strong> " + typeof suma2 + "</p>" +
    "<p>a y c son number → suma matemática: 1 + 2 = 3</p>";
}
