<?php
require __DIR__ . '/functions.php';
requireLogin();

$stmt = $pdo->prepare('SELECT nombre, email, sexo, edad, carrera FROM usuarios WHERE id_usuario = ?');
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

if (!$usuario) { // la cuenta fue borrada desde el CRUD mientras había sesión
    header('Location: logout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi cuenta</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <main class="tarjeta">
        <?php include 'menu.php'; ?>
        <h1>¡Hola, <?= e($usuario['nombre']) ?>!</h1>
        <p>Iniciaste sesión correctamente.</p>
        <ul>
            <li><strong>Correo:</strong> <?= e($usuario['email']) ?></li>
            <li><strong>Sexo:</strong> <?= e($usuario['sexo']) ?></li>
            <li><strong>Edad:</strong> <?= e($usuario['edad']) ?></li>
            <li><strong>Carrera:</strong> <?= e($usuario['carrera']) ?></li>
        </ul>
        <a class="boton" href="cursos.php" data-track="Ver catálogo (botón azul)">Ver catálogo de cursos</a>
        <p><a href="logout.php">Cerrar sesión</a></p>
    </main>
    <script src="tracker.js"></script>
</body>
</html>
