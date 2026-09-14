var personas = [
  { nombre: "Pablo", apellido: "Gala", fechaNac: "01/07/1956" },
  { nombre: "Jose", apellido: "Witt", fechaNac: "17/01/1985" }
];

function validar(nombre, apellido, fecha) {
  if (!nombre || !apellido || !fecha) {
    return false;
  }
  return true;
}

function crearPersona() {
  var nombre = document.getElementById("nombre").value.trim();
  var apellido = document.getElementById("apellido").value.trim();
  var fecha = document.getElementById("fecha").value.trim();

  if (!validar(nombre, apellido, fecha)) {
    alert("Falla en la validación. Asignar variables correctamente!");
    return;
  }

  var persona = {
    nombre: nombre,
    apellido: apellido,
    fechaNac: fecha
  };

  personas.push(persona);
  listarPersonas();
}

function listarPersonas() {
  var html = "<table>";
  for (var i = 0; i < personas.length; i++) {
    html += "<tr>";
    html += "<td>" + personas[i].nombre + "</td>";
    html += "<td>" + personas[i].apellido + "</td>";
    html += "<td>" + personas[i].fechaNac + "</td>";
    html += "</tr>";
  }
  html += "</table>";
  html += "Longitud del arreglo de objetos: " + personas.length;

  document.getElementById("tabla").innerHTML = html;
  document.getElementById("presentacion").style.display = "block";
}

function ocultarPresentacion() {
  document.getElementById("presentacion").style.display = "none";
}
