(function pintarFondo() {
  var texto = "asdfasd asdf asdf asdf asdf asdf asdf asdf asdf asdf asdf asdf asdf asdf ";
  var largo = "";
  for (var i = 0; i < 400; i++) {
    largo += texto;
  }
  document.getElementById("fondo").textContent = largo;
})();

function blanquear() {
  document.getElementById("caja").reset();
}

function enviar(pagina) {
  var form = document.getElementById("caja");
  form.action = pagina;
  form.submit();
}
