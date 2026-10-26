<?php

require __DIR__ . '/functions.php';
requireLogin();

$cursos = [
    ['Python desde cero',        'Variables, ciclos, funciones y archivos.',          '20 h'],
    ['Bases de datos con MySQL', 'Modelado, SQL, consultas con JOIN y GROUP BY.',     '24 h'],
    ['APIs REST con PHP',        'Servicios web, JSON, códigos HTTP y Postman.',      '18 h'],
    ['JavaScript moderno',       'DOM, eventos, fetch y módulos.',                    '22 h'],
    ['Ciberseguridad básica',    'Hash, inyección SQL, XSS y buenas prácticas.',      '16 h'],
    ['Git y GitHub',             'Commits, ramas, pull requests y trabajo en equipo.', '10 h'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursos</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <main class="tarjeta ancha">
        <?php include 'menu.php'; ?>
        <h1>Catálogo de cursos</h1>
        <p>Explora los cursos. Lo que hagas en esta página queda registrado en las métricas.</p>

        <div class="cursos">
            <?php foreach ($cursos as [$titulo, $descripcion, $duracion]): ?>
                <article class="curso">
                    <h2><?= e($titulo) ?></h2>
                    <p><?= e($descripcion) ?></p>
                    <p class="duracion"><?= e($duracion) ?></p>
                    <button type="button" class="interes" data-track="Me interesa: <?= e($titulo) ?>">Me interesa</button>
                </article>
            <?php endforeach; ?>
        </div>
    </main>
    <script>
        // Al pulsar "Me interesa" cambia el texto. Por eso el botón usa data-track:
        // así el rastreador registra un nombre fijo y no el texto ya cambiado.
        document.querySelectorAll('.interes').forEach((boton) => {
            boton.addEventListener('click', () => {
                boton.textContent = '✓ Te avisaremos';
                boton.classList.add('marcado');
            });
        });
    </script>
    <script src="tracker.js"></script>
</body>
</html>
