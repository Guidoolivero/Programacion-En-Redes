<?php
// ========================================================================================
// SCRIPT: index.php (Págs. 1, 2, 4 del apunte)
// OBJETIVO: Punto de entrada principal al entorno de sesión.
// Evalúa en su primera línea si la sesión ya existe.
// - Si NO existe sesión: Redirecciona al formulario de login (formularioDeLogin.html).
// - Si SÍ existe sesión: Ofrece ingresar al módulo protegido (appModulo1) o cerrar sesión.
// ========================================================================================

if (session_status() === PHP_SESSION_NONE) { // Comprueba si este pedido todavía no abrió una sesión PHP.
    session_start(); // Abre o retoma la sesión del navegador mediante su cookie.
}

// ========================================================================================
// PASO 1: Evaluar si el usuario ya está autenticado con sesión activa
// ========================================================================================
// Comprueba el dato que indica si este usuario ya se autenticó.
if (empty($_SESSION['identificativo'])) {
    // No hay sesión activa -> Redirección obligatoria al formulario de login
    header('Location: ./formularioDeLogin.html'); // Redirige al navegador a la página indicada.
    exit(); // Termina este pedido para que no continúe el código de abajo.
}

// Si llegó aquí, el usuario ya tiene sesión iniciada. Se muestran sus datos y accesos.
$usuarioActual = $_SESSION['login'] ?? 'Usuario'; // Lee el usuario para mostrarlo sin volver a autenticar.
$contadorActual = $_SESSION['ContadorDeSesion'] ?? 1; // Lee los accesos guardados en esta sesión.
$idSesion = $_SESSION['identificativo'] ?? session_id(); // Lee el identificativo para el recuadro de información.
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Sesión Activa — Menú Principal</title>
  <link rel="stylesheet" href="estiloLogin.css?v=20261110-css">
</head>
<body>
  <!-- Botón para volver al menú principal del sitio (htdocs/index.html) -->
  <div class="nav-volver-top">
    <a class="btn-volver" href="../../index.html">Volver al menú</a>
  </div>

  <div class="contenedor-card">
    <h2>Sesión actualmente activa</h2>
    <p>Ya te encuentras autenticado en el sistema.</p>
    
    <div class="info-box">
      <p><b>Usuario autenticado:</b> <?= htmlspecialchars($usuarioActual) ?></p>
      <p><b>Contador de sesiones:</b> <?= htmlspecialchars($contadorActual) ?></p>
      <p><b>ID de Sesión:</b> <?= htmlspecialchars($idSesion) ?></p>
    </div>

    <div class="botones-fila">
      <div class="acciones-sesion">
        <button type="button" id="btContinuar" class="btn-principal">Ingresar al Dashboard (appModulo1)</button>
        <button type="button" id="btSalir" class="btn-secundario">Cerrar Sesión</button>
      </div>
      <a href="descargarErrores.php" id="btDescargarErrores" class="btn-secundario">
        📥 Descargar Registro de Errores (.txt)
      </a>
    </div>
  </div>

  <script>
    // Al hacer clic continúa al módulo del ABM.
    document.getElementById("btContinuar").addEventListener("click", function() {
      location.href = "./appModulo1/index.php"; // Le indica al navegador la pantalla que debe abrir.
    });
    document.getElementById("btSalir").addEventListener("click", function() { // Al hacer clic navega al cierre de sesión.
      location.href = "./appModulo1/destruirsesion.php"; // Le indica al navegador la pantalla que debe abrir.
    });
  </script>
</body>
</html>
