<?php
// ========================================================================================
// SCRIPT: ingresoAlSistema.php (Págs. 2, 4, 6, 7, 8 del apunte)
// OBJETIVO:
// 1. Recibe los parámetros de login y password enviados desde formularioDeLogin.html.
// 2. Consulta la base de datos comparando el hash SHA-256 de la clave.
// 3. Si no autentica, redirige a formularioDeLogin.html.
// 4. Si autentica:
//    - Inicia sesión con session_start().
//    - Asigna $_SESSION['identificativo'] = session_create_id().
//    - Incrementa el contador de sesiones en la BD (UPDATE usuarios SET contador = contador + 1).
//    - Guarda $_SESSION['login'] y $_SESSION['ContadorDeSesion'].
// 5. Presenta la PANTALLA INTERMEDIA con los datos de sesión y controles para continuar
//    hacia ./appModulo1/index.php o abandonar la sesión con ./appModulo1/destruirsesion.php.
// ========================================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/datosConexionBase.php';

// ========================================================================================
// FUNCIÓN: autenticacion($log, $cl, &$nuevoContador) (Pág. 6 y 7 del apunte)
// Toma usuario y clave, calcula el hash SHA-256 y compara contra la tabla `usuarios`.
// Si es válido, incrementa en 1 el contador en la BD y guarda el nuevo valor en $nuevoContador.
// ========================================================================================
function autenticacion($log, $cl, &$nuevoContador) {
    global $host, $dbname, $username, $password;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Encriptar la clave con SHA-256 (64 caracteres hexadecimales)
        $claveEncriptada = hash('sha256', $cl);

        // Consultar la tabla de usuarios
        $sql = "SELECT id, login, password, contador FROM usuarios WHERE login = :login LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':login' => $log]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Comparar las claves encriptadas
            if ($usuario['password'] === $claveEncriptada) {
                // Autenticación correcta: incrementar el contador de sesiones en la BD
                $nuevoContador = ((int)$usuario['contador']) + 1;
                $updateSql = "UPDATE usuarios SET contador = :contador WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    ':contador' => $nuevoContador,
                    ':id' => $usuario['id']
                ]);
                return true;
            }
        }
        return false;
    } catch (PDOException $e) {
        $logPath = __DIR__ . '/errores.log';
        @file_put_contents($logPath, '[' . date('Y-m-d H:i:s') . '] Login Error: ' . $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
        return false;
    }
}

// ========================================================================================
// EVALUACIÓN DE SESIÓN (Pág. 8 del apunte):
// Si ya hay sesión iniciada (por ejemplo si el usuario hace un reload de la página),
// no volvemos a incrementar el contador en la BD.
// Si NO hay sesión iniciada, ejecutamos el proceso de autenticación y creación de sesión.
// ========================================================================================
if (!isset($_SESSION['identificativo']) || empty($_SESSION['identificativo'])) {
    $loginIngresado = $_POST['login'] ?? '';
    $passIngresada  = $_POST['password'] ?? '';

    $contadorSesion = 0;
    if (!autenticacion($loginIngresado, $passIngresada, $contadorSesion)) {
        // Credenciales inválidas -> Redirección inmediata al formulario de login
        header('Location: ./formularioDeLogin.html');
        exit();
    }

    // ====================================================================================
    // CREACIÓN Y CONFIGURACIÓN DE LA NUEVA SESIÓN (Págs. 4 y 7 del apunte)
    // ====================================================================================
    $_SESSION['identificativo'] = session_create_id();
    $_SESSION['login'] = $loginIngresado;
    $_SESSION['ContadorDeSesion'] = $contadorSesion;
}

// ========================================================================================
// FUNCIÓN: infoDeSesion() (Pág. 8 del apunte)
// Despliega los parámetros y estado de la sesión actual en la pantalla intermedia.
// ========================================================================================
function infoDeSesion() {
    $id = $_SESSION['identificativo'] ?? session_id();
    $usr = $_SESSION['login'] ?? 'Desconocido';
    $cnt = $_SESSION['ContadorDeSesion'] ?? 1;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Local';

    echo "<div class='info-box'>";
    echo "<p><b>ID de Sesión (session_create_id):</b> <code>" . htmlspecialchars($id) . "</code></p>";
    echo "<p><b>Usuario logueado:</b> <code>" . htmlspecialchars($usr) . "</code></p>";
    echo "<p><b>Cantidad de accesos / Contador de sesión:</b> <span class='badge-contador'>" . htmlspecialchars($cnt) . "</span></p>";
    echo "<p><b>Dirección IP remota:</b> <code>" . htmlspecialchars($ip) . "</code></p>";
    echo "</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Ingreso al Sistema — Pantalla Intermedia</title>
  <link rel="stylesheet" href="estiloLogin.css">
</head>
<body>
  <div class="contenedor-card" style="max-width: 580px;">
    <h2>¡Bienvenido al Sistema!</h2>
    <p class="subtitulo">Autenticación exitosa — Pantalla intermedia previa al Dashboard</p>

    <h2>Sus parámetros de sesión son los siguientes:</h2>
    <?php infoDeSesion(); ?>

    <p style="font-size: 13px; color: #555; margin-top: 15px;">
      El módulo de trabajo (<b>appModulo1</b>) se encuentra completamente protegido por sesión.
      Seleccione una acción para continuar:
    </p>

    <!-- Botones requeridos por el apunte en la página 8 y botón de descarga de errores -->
    <div class="botones-fila" style="display: flex; flex-direction: column; gap: 10px;">
      <div style="display: flex; gap: 10px; width: 100%;">
        <button type="button" id="btAppModulo1" class="btn-principal" style="flex: 2;">Ingresar al Módulo (Dashboard CRUD)</button>
        <button type="button" id="btSalida" class="btn-secundario" style="flex: 1;">Abandonar Sesión</button>
      </div>
      <a href="descargarErrores.php" id="btDescargarErrores" class="btn-secundario" style="text-align: center; text-decoration: none; background: #4a5568; color: white; border: none; padding: 9px; border-radius: 4px; font-size: 13px; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 6px;">
        📥 Descargar Registro de Errores (.txt)
      </a>
    </div>
  </div>

  <!-- Script con los listeners exactos requeridos en la página 8 del PDF -->
  <script>
    document.getElementById("btAppModulo1").addEventListener("click", function() {
      location.href = "./appModulo1/index.php";
    });

    document.getElementById("btSalida").addEventListener("click", function() {
      location.href = "./appModulo1/destruirsesion.php";
    });
  </script>
</body>
</html>
