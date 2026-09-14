var contador = 0;
var escenario = document.getElementById("escenario");

function crear() {
  var caja = document.createElement("div");
  caja.className = "item";

  var titulo = document.createElement("h1");
  titulo.innerHTML = "Elemento creado: " + contador;

  caja.appendChild(titulo);
  escenario.appendChild(caja);
  contador++;
}

function limpiar() {
  escenario.innerHTML = "";
  contador = 0;
}

function info() {
  var nodos = escenario.childNodes;
  alert("longitud del chilNodes: " + nodos.length);
  for (var i = 0; i < nodos.length; i++) {
    alert("indice: " + i + " innerHTML: " + nodos[i].innerHTML);
  }
}
