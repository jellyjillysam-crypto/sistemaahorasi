<?php
// Esta página NO consulta la BD: solo trae el HTML vacío.
// Los datos los pide panel.js al servicio metricas.php.

require __DIR__ . '/functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Métricas</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <main class="tarjeta ancha">
        <?php include 'menu.php'; ?>
        <h1>Métricas del sitio</h1>
        <p class="nota">Se actualiza solo cada 5 segundos. <span id="estado"></span></p>

        <section class="kpis" id="kpis"></section>

        <h2>Opciones del menú</h2>
        <div class="tabla"><table id="tabla-menu"></table></div>

        <h2>Actividad reciente</h2>
        <div class="tabla"><table id="tabla-recientes"></table></div>

        <h2>Páginas</h2>
        <div class="tabla"><table id="tabla-paginas"></table></div>

        <h2>¿Dónde hacen clic?</h2>
        <div class="tabla"><table id="tabla-clics"></table></div>

        <h2>Usuarios</h2>
        <div class="tabla"><table id="tabla-usuarios"></table></div>

        <h2>Por carrera</h2>
        <div class="tabla"><table id="tabla-carreras"></table></div>
    </main>
    <script src="panel.js"></script>
    <script src="tracker.js"></script>
</body>
</html>
