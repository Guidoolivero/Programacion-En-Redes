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

if (session_status() === PHP_SESSION_NONE) { // Comprueba si este pedido todavía no abrió una sesión PHP.
    session_start(); // Abre o retoma la sesión del navegador mediante su cookie.
}

include __DIR__ . '/datosConexionBase.php'; // Carga la configuración que usará PDO para conectarse.

// ========================================================================================
// FUNCIÓN: autenticacion($log, $cl, &$nuevoContador) (Pág. 6 y 7 del apunte)
// Toma usuario y clave, calcula el hash SHA-256 y compara contra la tabla `usuarios`.
// Si es válido, incrementa en 1 el contador en la BD y guarda el nuevo valor en $nuevoContador.
// ========================================================================================
function autenticacion($log, $cl, &$nuevoContador) {
    global $host, $dbname, $username, $password; // Usa dentro de la función la configuración cargada fuera de ella.

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION 
            // PDO => PHP Data Objects (Objetos de Datos de PHP).
            // :: => Operador de Resolución de Ámbito
        ]);

        // Encriptar la clave con SHA-256 (64 caracteres hexadecimales)
        $claveEncriptada = hash('sha256', $cl); // Calcula el hash para compararlo con el almacenado en usuarios.

        // Consultar la tabla de usuarios
        // SELECT: busca el usuario por su login.
        $sql = "SELECT id, login, password, contador FROM usuarios WHERE login = :login LIMIT 1";
        $stmt = $pdo->prepare($sql); // Prepara la consulta sin mezclar el login con el texto SQL.
        $stmt->execute([':login' => $log]); // Ejecuta el SELECT vinculando el usuario recibido.
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);// fetch trae la fila como arreglo asociativo, o false si ese login no existe.
        // :: => Operador de Resolución de Ámbito
        if ($usuario) { // Continúa solo si la consulta encontró ese usuario.
            // Comparar las claves encriptadas
            if ($usuario['password'] === $claveEncriptada) { // Compara el hash guardado con el de la clave ingresada.
                // Autenticación correcta: incrementar el contador de sesiones en la BD
                // Cuenta este acceso y lo devuelve por referencia al llamador.
                $nuevoContador = ((int)$usuario['contador']) + 1;
                // UPDATE: prepara el nuevo contador para ese usuario.
                $updateSql = "UPDATE usuarios SET contador = :contador WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql); // Prepara la actualización del contador.
                $updateStmt->execute([ // Ejecuta el UPDATE con el contador y el ID del usuario.
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
// Comprueba el dato que indica si este usuario ya se autenticó.
if (empty($_SESSION['identificativo'])) {
    $loginIngresado = $_POST['login'] ?? ''; // Lee el usuario enviado desde el formulario de login.
    $passIngresada  = $_POST['password'] ?? ''; // Lee la contraseña enviada desde el formulario.

    $contadorSesion = 0; // La autenticación completará el nuevo contador si es correcta.
    // Si usuario o contraseña no coinciden, vuelve al formulario.
    if (!autenticacion($loginIngresado, $passIngresada, $contadorSesion)) {
        // Credenciales inválidas -> Redirección inmediata al formulario de login
        header('Location: ./formularioDeLogin.html'); // Redirige al navegador a la página indicada.
        exit(); // Termina este pedido para que no continúe el código de abajo.
    }

    // ====================================================================================
    // CREACIÓN Y CONFIGURACIÓN DE LA NUEVA SESIÓN (Págs. 4 y 7 del apunte)
    // ====================================================================================
    $_SESSION['identificativo'] = session_create_id(); // Guarda la marca de autenticación usada por este ejercicio.
    $_SESSION['login'] = $loginIngresado; // Conserva el usuario para mostrarlo en las otras pantallas.
    $_SESSION['ContadorDeSesion'] = $contadorSesion; // Conserva la cantidad de accesos devuelta por autenticacion.
}

// ========================================================================================
// FUNCIÓN: infoDeSesion() (Pág. 8 del apunte)
// Despliega los parámetros y estado de la sesión actual en la pantalla intermedia.
// ========================================================================================
function infoDeSesion() {
    $id = $_SESSION['identificativo'] ?? session_id(); // Lee el identificativo que se mostrará en pantalla.
    $usr = $_SESSION['login'] ?? 'Desconocido'; // Lee el nombre del usuario de esta sesión.
    $cnt = $_SESSION['ContadorDeSesion'] ?? 1; // Lee el contador guardado durante el login.
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Local'; // Lee la dirección del cliente si el servidor la informa.

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
  <link rel="stylesheet" href="estiloLogin.css?v=20261110-css">
</head>
<body>
  <!-- Botón para volver al menú principal del sitio (htdocs/index.html) -->
  <div class="nav-volver-top">
    <a class="btn-volver" href="../../index.html">Volver al menú</a>
  </div>

  <div class="contenedor-card pantalla-intermedia">
    <h2>¡Bienvenido al Sistema!</h2>
    <p class="subtitulo">Autenticación exitosa — Pantalla intermedia previa al Dashboard</p>

    <h2>Sus parámetros de sesión son los siguientes:</h2>
    <?php infoDeSesion(); ?>

    <p class="descripcion-modulo">
      El módulo de trabajo (<b>appModulo1</b>) se encuentra completamente protegido por sesión.
      Seleccione una acción para continuar:
    </p>

    <!-- Botones requeridos por el apunte en la página 8 y botón de descarga de errores -->
    <div class="botones-fila">
      <div class="acciones-sesion">
        <button type="button" id="btAppModulo1" class="btn-principal">Ingresar al Módulo (Dashboard CRUD)</button>
        <button type="button" id="btSalida" class="btn-secundario">Abandonar Sesión</button>
      </div>
      <a href="descargarErrores.php" id="btDescargarErrores" class="btn-secundario">
        📥 Descargar Registro de Errores (.txt)
      </a>
    </div>
  </div>

  <!-- Script con los listeners exactos requeridos en la página 8 del PDF -->
  <script>
    // Este botón abre el tablero del ABM protegido por sesión.
    document.getElementById("btAppModulo1").addEventListener("click", function() {
      location.href = "./appModulo1/index.php"; // Cambia la página que muestra el navegador.
    });

    // Este botón navega al script que destruye la sesión.
    document.getElementById("btSalida").addEventListener("click", function() {
      location.href = "./appModulo1/destruirsesion.php"; // Cambia la página que muestra el navegador.
    });
  </script>
</body>
</html>
