function ejecutarAlert() {
  alert("Este alert se ejecutó porque hiciste clic. El comando está asociado a un evento (onclick).");
}

function ejecutarWrite() {
  // Como la página YA terminó de cargarse, document.write() BORRA todo
  // y escribe un documento nuevo.
  document.write("<h2>document.write() después de que la página cargó</h2>");
  document.write("<p>La página original desapareció porque el flujo de escritura del documento ya había terminado.</p>");
  document.write("<p><a href='index.html'>Volver al ejercicio</a></p>");
}
