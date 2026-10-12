<?php
// Guarda las respuestas de la encuesta usando la misma conexion del sistema (PDO + variables de entorno)
require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: encuesta.html');
    exit;
}

$campos = ['profesores', 'instalacion', 'wifi', 'biblioteca', 'sugerencia'];
$d = [];
foreach ($campos as $c) {
    $d[$c] = trim((string) ($_POST[$c] ?? ''));
    if ($d[$c] === '') {
        http_response_code(400);
        exit("Faltan respuestas. <a href='encuesta.html'>Volver</a>");
    }
}
$d['sugerencia'] = mb_substr($d['sugerencia'], 0, 300);

// Consulta preparada: evita inyeccion SQL
$sql = 'INSERT INTO respuestas (profesores, instalacion, wifi, biblioteca, sugerencia)
        VALUES (:profesores, :instalacion, :wifi, :biblioteca, :sugerencia)';

try {
    $pdo->prepare($sql)->execute($d);
} catch (PDOException $exception) {
    error_log('Encuesta: ' . $exception->getMessage());
    http_response_code(500);
    exit("No se pudo guardar tu respuesta. <a href='encuesta.html'>Volver</a>");
}

header('Location: regalo.html');
exit;
