<?php
// ========================================================================================
// SCRIPT: index.php (Págs. 1, 2, 4 del apunte)
// OBJETIVO: Punto de entrada principal al entorno de sesión.
// Evalúa en su primera línea si la sesión ya existe.
// - Si NO existe sesión: Redirecciona al formulario de login (formularioDeLogin.html).
// - Si SÍ existe sesión: Ofrece ingresar al módulo protegido (appModulo1) o cerrar sesión.
// ========================================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================================================================
// PASO 1: Evaluar si el usuario ya está autenticado con sesión activa
// ========================================================================================
if (!isset($_SESSION['identificativo']) || empty($_SESSION['identificativo'])) {
    // No hay sesión activa -> Redirección obligatoria al formulario de login
    header('Location: ./formularioDeLogin.html');
    exit();
}

// Si llegó aquí, el usuario ya tiene sesión iniciada. Se muestran sus datos y accesos.
$usuarioActual = $_SESSION['login'] ?? 'Usuario';
$contadorActual = $_SESSION['ContadorDeSesion'] ?? 1;
$idSesion = $_SESSION['identificativo'] ?? session_id();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Sesión Activa — Menú Principal</title>
  <link rel="stylesheet" href="estiloLogin.css">
</head>
<body>
  <div class="contenedor-card">
    <h2>Sesión actualmente activa</h2>
    <p>Ya te encuentras autenticado en el sistema.</p>
    
    <div class="info-box">
      <p><b>Usuario autenticado:</b> <?= htmlspecialchars($usuarioActual) ?></p>
      <p><b>Contador de sesiones:</b> <?= htmlspecialchars($contadorActual) ?></p>
      <p><b>ID de Sesión:</b> <?= htmlspecialchars($idSesion) ?></p>
    </div>

    <div class="botones-fila">
      <button type="button" id="btContinuar" class="btn-principal">Ingresar al Dashboard (appModulo1)</button>
      <button type="button" id="btSalir" class="btn-secundario">Cerrar Sesión</button>
    </div>
  </div>

  <script>
    document.getElementById("btContinuar").addEventListener("click", function() {
      location.href = "./appModulo1/index.php";
    });
    document.getElementById("btSalir").addEventListener("click", function() {
      location.href = "./appModulo1/destruirsesion.php";
    });
  </script>
</body>
</html>
