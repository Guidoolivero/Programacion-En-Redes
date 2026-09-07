var variableGlobal;

alert("Una variableGlobal fue declarada pero no asignada:\nvar variableGlobal");
alert("El valor de variableGlobal es: " + variableGlobal);

function asignacionConDeclaracion() {
  var valorLocal = prompt("Ingrese un valor para la variable LOCAL:");
  alert("El valor de la variable local ingresada es: " + valorLocal);
  alert("El tipo de la variable local ingresada es: " + typeof valorLocal);
}

function asignacionForzadaSinDeclaracion() {
  variableGlobal = prompt("Ingrese un valor para la variable GLOBAL (sin usar var adentro de la función):");
  alert("Se asignó la variable global.\nValor: " + variableGlobal);
  alert("Tipo: " + typeof variableGlobal);
}

function mostrarVariableGlobal() {
  alert("El valor de variableGlobal es: " + variableGlobal);
  alert("El tipo de variableGlobal es: " + typeof variableGlobal);
}
