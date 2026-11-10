<?php
include __DIR__ . '/../manejoSesion.inc'; // Primero comprueba la sesión, como pide el apunte.

// Vista de entrada: entrega al navegador el template HTML completo.
readfile(__DIR__ . '/index.html');
