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
  <!-- Botones de navegación al menú general y al menú de ejercicios PHP -->
  <div class="nav-volver-top">
    <a class="btn-volver" href="../../index.html">Menú General</a>
    <a class="btn-volver" href="../index.php">Menú PHP</a>
  </div>

  <div class="contenedor-card">
    <h2>Sesión actualmente activa</h2>
    <p>Ya te encuentras autenticado en el sistema.</p>
    
    <div class="info-box">
      <p><b>Usuario autenticado:</b> <?= htmlspecialchars($usuarioActual) ?></p>
      <p><b>Contador de sesiones:</b> <?= htmlspecialchars($contadorActual) ?></p>
      <p><b>ID de Sesión:</b> <?= htmlspecialchars($idSesion) ?></p>
    </div>

    <div class="botones-fila" style="display: flex; flex-direction: column; gap: 10px;">
      <div style="display: flex; gap: 10px; width: 100%;">
        <button type="button" id="btContinuar" class="btn-principal" style="flex: 2;">Ingresar al Dashboard (appModulo1)</button>
        <button type="button" id="btSalir" class="btn-secundario" style="flex: 1;">Cerrar Sesión</button>
      </div>
      <a href="descargarErrores.php" id="btDescargarErrores" class="btn-secundario" style="text-align: center; text-decoration: none; background: #4a5568; color: white; border: none; padding: 9px; border-radius: 4px; font-size: 13px; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 6px;">
        📥 Descargar Registro de Errores (.txt)
      </a>
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
