function mostrarAlert3() {
  alert(
    "Alert 3. Este mensaje de alerta es escrito desde un script del documento. " +
    "Esta ventana de alerta es modal lo que significa que el flujo de escritura del documento se interrumpe hasta el evento de aceptación. " +
    "El navegador muestra estas ventana con anterioridad a la interpretación o renderización del documento"
  );
}

function escribirTexto2() {
  document.write("<h2>Texto2. Mensaje escrito desde java script utilizando el metodo write().</h2>");
  document.write("<p>No pisa al documento porque aún no termino el flujo de escritura del navegador</p>");
}

function mostrarAlert6() {
  alert("Alert6. Escrito al final de todo el codigo");
}

function escribirTexto5() {
  document.write("<h2>Texto5. Otra escritura con metodo write() aplicado al documento.</h2>");
  document.write("<p>El texto escrito con write no pisa al documento porque aún no termino el flujo de escritura del navegador</p>");
  document.write("<p>El flujo de escritura sobre el navegador termina un tiempo determinado despues de la lectura de la última linea del documento y de haber cerrado todas las ventanas modales de tipo alert().</p>");
}
