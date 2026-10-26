<?php
require __DIR__ . '/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $nombre    = trim($_POST['nombre'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $sexo      = $_POST['sexo'] ?? '';
    $edad      = (int) ($_POST['edad'] ?? 0);
    $carrera   = trim($_POST['carrera'] ?? '');
    $password  = (string) ($_POST['password'] ?? '');
    $confirmar = (string) ($_POST['confirmar'] ?? '');

    if ($nombre === '' || mb_strlen($nombre) > 100 || $carrera === '' || mb_strlen($carrera) > 100
        || mb_strlen($correo) > 150 || !filter_var($correo, FILTER_VALIDATE_EMAIL)
        || !in_array($sexo, ['Hombre', 'Mujer', 'Otro'], true) || $edad < 15 || $edad > 99) {
        $error = 'Revisa que todos los datos sean válidos.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        // Misma tabla `usuarios` que usa el sistema de ventas: quien se registra aqui
        // tambien aparece en el CRUD y se le pueden asignar ventas.
        $sql = 'INSERT INTO usuarios (nombre, email, sexo, edad, carrera, password_hash) VALUES (?, ?, ?, ?, ?, ?)';
        try {
            $pdo->prepare($sql)->execute([$nombre, $correo, $sexo, $edad, $carrera, $hash]);
            header('Location: login.php?registrado=1');
            exit;
        } catch (PDOException $ex) {
            if ($ex->getCode() !== '23000') {
                throw $ex;
            }
            $error = 'Ese correo ya está registrado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <main class="tarjeta">
        <h1>Crear cuenta</h1>

        <?php if ($error): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <form id="form-registro" method="post">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <label>Nombre
                <input type="text" name="nombre" maxlength="100" required value="<?= e($_POST['nombre'] ?? '') ?>">
            </label>
            <label>Correo
                <input type="email" name="correo" maxlength="150" required value="<?= e($_POST['correo'] ?? '') ?>">
            </label>
            <label>Sexo
                <select name="sexo" required>
                    <option value="">Selecciona…</option>
                    <?php foreach (['Hombre', 'Mujer', 'Otro'] as $opcion): ?>
                        <option <?= ($_POST['sexo'] ?? '') === $opcion ? 'selected' : '' ?>><?= $opcion ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Edad
                <input type="number" name="edad" min="15" max="99" required value="<?= e($_POST['edad'] ?? '') ?>">
            </label>
            <label>Carrera
                <input type="text" name="carrera" maxlength="100" required value="<?= e($_POST['carrera'] ?? '') ?>">
            </label>
            <label>Contraseña
                <input type="password" name="password" minlength="8" required>
            </label>
            <label>Confirmar contraseña
                <input type="password" name="confirmar" minlength="8" required>
            </label>
            <button type="submit">Registrarme</button>
        </form>

        <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
        <p><a href="inicio.php">← Volver al inicio</a></p>
    </main>
    <script src="registro.js"></script>
    <script src="tracker.js"></script>
</body>
</html>
