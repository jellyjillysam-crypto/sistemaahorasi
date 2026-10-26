<?php
// Servicio que ENTREGA métricas:  GET metricas.php  (respuesta JSON)

require __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401); // No autenticado
    echo json_encode(['error' => 'Inicia sesión para ver las métricas.']);
    exit;
}

session_write_close();

function consultar(PDO $pdo, string $sql): array
{
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$resumen = consultar($pdo, "
    SELECT COUNT(DISTINCT visitante_id) AS visitantes,
           COUNT(DISTINCT sesion_id)    AS sesiones,
           COUNT(DISTINCT usuario_id)   AS usuarios,
           COALESCE(SUM(tipo = 'vista'), 0) AS vistas,
           COALESCE(SUM(tipo = 'clic'), 0)  AS clics
    FROM eventos")[0];

// Tiempo promedio por sesión: primero se suma por sesión y luego se promedia.
$resumen['tiempo_promedio'] = consultar($pdo, "
    SELECT COALESCE(ROUND(AVG(total)), 0) AS promedio
    FROM (SELECT SUM(segundos) AS total FROM eventos
          WHERE tipo = 'tiempo' GROUP BY sesion_id) AS t")[0]['promedio'];

$paginas = consultar($pdo, "
    SELECT pagina,
           SUM(tipo = 'vista') AS vistas,
           SUM(tipo = 'clic')  AS clics,
           COALESCE(SUM(segundos), 0) AS segundos
    FROM eventos
    GROUP BY pagina
    ORDER BY vistas DESC
    LIMIT 10");

$clics = consultar($pdo, "
    SELECT elemento, pagina, COUNT(*) AS total
    FROM eventos
    WHERE tipo = 'clic'
    GROUP BY elemento, pagina
    ORDER BY total DESC
    LIMIT 10");

// Opciones del menú (inicio.php y barra del sistema de ventas): los clics llevan nombre fijo "Menú...".
$menu = consultar($pdo, "
    SELECT elemento,
           COUNT(*) AS clics,
           COUNT(DISTINCT usuario_id) AS usuarios,
           MAX(creado_en) AS ultima
    FROM eventos
    WHERE tipo = 'clic' AND elemento LIKE 'Menú%'
    GROUP BY elemento
    ORDER BY clics DESC");

// Aquí se une la analítica con los datos del registro del ejercicio LOGIN.
$usuarios = consultar($pdo, "
    SELECT COALESCE(u.nombre, '(anónimos)') AS nombre,
           COALESCE(u.carrera, '—') AS carrera,
           COUNT(DISTINCT e.sesion_id) AS sesiones,
           SUM(e.tipo = 'vista') AS vistas,
           SUM(e.tipo = 'clic')  AS clics,
           COALESCE(SUM(e.segundos), 0) AS segundos,
           MAX(e.creado_en) AS ultima
    FROM eventos e
    LEFT JOIN usuarios u ON u.id_usuario = e.usuario_id
    GROUP BY e.usuario_id, u.nombre, u.carrera
    ORDER BY ultima DESC");

$carreras = consultar($pdo, "
    SELECT u.carrera,
           COUNT(DISTINCT u.id_usuario) AS usuarios,
           SUM(e.tipo = 'clic') AS clics,
           COALESCE(SUM(e.segundos), 0) AS segundos
    FROM eventos e
    JOIN usuarios u ON u.id_usuario = e.usuario_id
    WHERE u.carrera IS NOT NULL
    GROUP BY u.carrera
    ORDER BY segundos DESC");

$recientes = consultar($pdo, "
    SELECT DATE_FORMAT(e.creado_en, '%H:%i:%s') AS hora,
           COALESCE(u.nombre, 'Anónimo') AS nombre,
           e.tipo, e.pagina, e.elemento, e.segundos
    FROM eventos e
    LEFT JOIN usuarios u ON u.id_usuario = e.usuario_id
    ORDER BY e.id DESC
    LIMIT 15");

echo json_encode(compact('resumen', 'menu', 'paginas', 'clics', 'usuarios', 'carreras', 'recientes'), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
