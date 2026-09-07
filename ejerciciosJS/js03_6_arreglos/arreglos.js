var arregloFrutas = ["banana", "manzana"];

var tercer = prompt(
  "El arregloFrutas ya tiene dos elemntos asignados por programa\n" +
  "(banana y manzana), agregue el tercer elemento con indice numerico"
);

arregloFrutas[2] = tercer;

document.write("<h2>Variables Multidimensionales</h2>");
document.write("<h3>Array de indice numerico</h3>");
document.write("<p>Tipo para arregloFrutas: " + typeof arregloFrutas + "</p>");
document.write("<p>Primer elemento cargado desde programa: <strong>" + arregloFrutas[0] + "</strong></p>");
document.write("<p>Segundo elemento cargado desde programa: <strong>" + arregloFrutas[1] + "</strong></p>");
document.write("<p>Tercer elemento cargado desde teclado: <strong>" + arregloFrutas[2] + "</strong></p>");
document.write("<p>Cantidad de elementos: " + arregloFrutas.length + "</p>");
