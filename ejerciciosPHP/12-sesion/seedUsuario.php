<?php
// ========================================================================================
// SCRIPT: seedUsuario.php
// OBJETIVO: Crear o verificar el usuario inicial en la tabla `usuarios` con hash SHA-256.
// Se puede ejecutar desde el navegador una sola vez si se desea cargar usuarios de prueba.
// ========================================================================================

include __DIR__ . '/datosConexionBase.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Usuario 1: admin / admin123
    $user1 = 'admin';
    $passPlana1 = 'admin123';
    $passHash1 = hash('sha256', $passPlana1);

    // Usuario 2: guido / guido123
    $user2 = 'guido';
    $passPlana2 = 'guido123';
    $passHash2 = hash('sha256', $passPlana2);

    $sql = "INSERT INTO usuarios (login, password, contador) 
            VALUES (:login, :password, 0)
            ON DUPLICATE KEY UPDATE password = VALUES(password)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':login' => $user1, ':password' => $passHash1]);
    $stmt->execute([':login' => $user2, ':password' => $passHash2]);

    echo "<h3>Usuarios cargados con éxito en la tabla `usuarios`</h3>";
    echo "<ul>";
    echo "<li><b>Usuario:</b> admin | <b>Clave:</b> admin123 | <b>SHA-256:</b> $passHash1</li>";
    echo "<li><b>Usuario:</b> guido | <b>Clave:</b> guido123 | <b>SHA-256:</b> $passHash2</li>";
    echo "</ul>";
    echo "<p><a href='formularioDeLogin.html'>Ir al Formulario de Login</a></p>";
} catch (PDOException $e) {
    echo "Error al cargar usuario: " . $e->getMessage();
}
