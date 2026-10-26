<?php
// Servicio que RECIBE eventos:  POST eventos.php  (cuerpo JSON)
require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método no permitido
    exit;
}

// ¿Quién es? Lo decide el SERVIDOR con la sesión de PHP, no lo que diga el navegador.
$usuarioId = $_SESSION['usuario_id'] ?? null;
session_write_close(); // libera el bloqueo de la sesión: llegan muchos eventos seguidos

// sendBeacon manda el JSON como texto plano, así que se lee el cuerpo crudo.
$datos = json_decode(file_get_contents('php://input'), true);

$tipo       = is_array($datos) ? ($datos['tipo'] ?? '') : '';
$visitante  = is_array($datos) ? ($datos['visitante_id'] ?? '') : '';
$sesion     = is_array($datos) ? ($datos['sesion_id'] ?? '') : '';
$patronUuid = '/^[0-9a-f-]{36}$/';

// Nunca confiar en el cliente: cualquiera puede mandar un POST con curl o Postman.
if (!is_array($datos) || !in_array($tipo, ['vista', 'clic', 'tiempo'], true)
    || !is_string($visitante) || !preg_match($patronUuid, $visitante)
    || !is_string($sesion) || !preg_match($patronUuid, $sesion)) {
    http_response_code(400); // Petición incorrecta
    exit;
}

$pagina   = mb_substr((string) ($datos['pagina'] ?? ''), 0, 200);
$elemento = $tipo === 'clic' ? mb_substr((string) ($datos['elemento'] ?? ''), 0, 100) : null;
$segundos = $tipo === 'tiempo' ? max(0, min(3600, (int) ($datos['segundos'] ?? 0))) : null;

try {
    $sql = 'INSERT INTO eventos (visitante_id, sesion_id, usuario_id, tipo, pagina, elemento, segundos)
            VALUES (?, ?, ?, ?, ?, ?, ?)';
    $pdo->prepare($sql)->execute([$visitante, $sesion, $usuarioId, $tipo, $pagina, $elemento, $segundos]);
} catch (PDOException $exception) {
    error_log('Eventos: ' . $exception->getMessage());
    http_response_code(500);
    exit;
}

http_response_code(204); // Guardado; no hay nada que responder
