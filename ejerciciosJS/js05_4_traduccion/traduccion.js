var idioma = (navigator.language || navigator.userLanguage || "es").toLowerCase();

if (idioma.indexOf("es") === 0) {
  document.write('<h1><span class="destacado">Aprendé a</span> estudiar en serio</h1>');
} else {
  document.write('<h1><span class="destacado">Learn to</span> study seriously</h1>');
}
