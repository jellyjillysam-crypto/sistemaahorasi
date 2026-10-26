<?php
require __DIR__ . '/functions.php';

if (!empty($_SESSION['usuario_id'])) {
    header('Location: inicio.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $correo   = trim($_POST['correo'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    // Solo cuentas activas y que ya tengan contrasena (los usuarios creados desde el CRUD
    // de ventas sin contrasena son clientes, no cuentas con acceso).
    $stmt = $pdo->prepare('SELECT id_usuario, nombre, password_hash FROM usuarios
                           WHERE email = ? AND activo = 1 AND password_hash IS NOT NULL');
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($password, $usuario['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int) $usuario['id_usuario'];
        $_SESSION['nombre']     = $usuario['nombre'];

        // Lo que este navegador hizo como anonimo ahora se sabe de quien fue.
        $visitante = $_COOKIE['visitante_id'] ?? '';
        if (preg_match('/^[0-9a-f-]{36}$/', $visitante)) {
            $pdo->prepare('UPDATE eventos SET usuario_id = ? WHERE visitante_id = ? AND usuario_id IS NULL')
                ->execute([$usuario['id_usuario'], $visitante]);
        }

        header('Location: inicio.php');
        exit;
    }
    $error = 'Correo o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <main class="tarjeta">
        <h1>Iniciar sesión</h1>

        <?php if (isset($_GET['registrado'])): ?>
            <p class="exito">Cuenta creada. Ya puedes iniciar sesión.</p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <label>Correo
                <input type="email" name="correo" required value="<?= e($_POST['correo'] ?? '') ?>">
            </label>
            <label>Contraseña
                <input type="password" name="password" required>
            </label>
            <button type="submit">Entrar</button>
        </form>

        <p>¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
        <p><a href="inicio.php">← Volver al inicio</a></p>
    </main>
    <script src="tracker.js"></script>
</body>
</html>
