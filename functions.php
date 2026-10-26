<?php
declare(strict_types=1);

// Solo en tu computadora (php -S): muestra los errores de PHP en pantalla para poder depurar
if (PHP_SAPI === 'cli-server') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Cookie de sesion (PHPSESSID): no visible para JavaScript y solo por HTTPS cuando el sitio lo usa
// (Render termina el HTTPS en su proxy y manda X-Forwarded-Proto).
$esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $esHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// config.php lee las variables de entorno (Render). Si existe config.local.php
// (solo en tu computadora, no se sube a GitHub), sus valores tienen prioridad.
$config = require __DIR__ . '/config.php';
$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $override = require $local;
    if (is_array($override)) {
        $config = array_merge($config, $override);
    }
}

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'] ?? '3306',
        $config['database'],
        $config['charset']
    );
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    error_log('Conexion a la base de datos: ' . $exception->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisa las variables de entorno (DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME).');
}

/**
 * Crea las columnas de login en `usuarios` y la tabla `eventos` si todavia no existen
 * (equivale a migracion_login_metricas.sql). Es seguro repetirlo: solo agrega lo que falta.
 * Se revisa una vez por sesion de navegador.
 */
function asegurarEsquema(PDO $pdo): void
{
    if (!empty($_SESSION['esquema_ok'])) {
        return;
    }
    try {
        $existentes = array_map('strtolower', $pdo->query(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'"
        )->fetchAll(PDO::FETCH_COLUMN));

        $nuevas = [
            'password_hash' => 'VARCHAR(255) NULL',
            'sexo'          => "ENUM('Hombre', 'Mujer', 'Otro') NULL",
            'edad'          => 'TINYINT UNSIGNED NULL',
            'carrera'       => 'VARCHAR(100) NULL',
        ];
        $agregar = [];
        foreach ($nuevas as $columna => $definicion) {
            if (!in_array($columna, $existentes, true)) {
                $agregar[] = "ADD COLUMN {$columna} {$definicion}";
            }
        }
        if ($agregar) {
            $pdo->exec('ALTER TABLE usuarios ' . implode(', ', $agregar));
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS eventos (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            visitante_id CHAR(36)     NOT NULL,
            sesion_id    CHAR(36)     NOT NULL,
            usuario_id   INT          NULL,
            tipo         ENUM('vista', 'clic', 'tiempo') NOT NULL,
            pagina       VARCHAR(200) NOT NULL,
            elemento     VARCHAR(100) NULL,
            segundos     SMALLINT UNSIGNED NULL,
            creado_en    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_evento_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id_usuario) ON DELETE SET NULL,
            INDEX (visitante_id),
            INDEX (sesion_id)
        )");
        $_SESSION['esquema_ok'] = true;
    } catch (PDOException $exception) {
        error_log('Esquema: ' . $exception->getMessage());
    }
}

asegurarEsquema($pdo);

function e( $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $page = 'inicio'): never
{
    header('Location: index.php#' . rawurlencode($page));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verifyCsrf(): void
{
    $esperado = (string) ($_SESSION['csrf'] ?? '');
    // Sin token en la sesion ('' == '') no debe pasar la validacion.
    if ($esperado === '' || !hash_equals($esperado, (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Solicitud no valida. Recarga la pagina e intenta de nuevo.');
    }
}

function postInt(string $key, int $minimum = 1): int
{
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
    if ($value === false || $value === null || $value < $minimum) {
        throw new InvalidArgumentException("El campo {$key} no es valido.");
    }
    return $value;
}

function postDecimal(string $key, float $minimum = 0): float
{
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_FLOAT);
    if ($value === false || $value === null || $value < $minimum) {
        throw new InvalidArgumentException("El campo {$key} no es valido.");
    }
    return $value;
}

function postText(string $key, int $maxLength): string
{
    $value = trim((string) ($_POST[$key] ?? ''));
    if ($value === '' || mb_strlen($value) > $maxLength) {
        throw new InvalidArgumentException("El campo {$key} es obligatorio y no debe superar {$maxLength} caracteres.");
    }
    return $value;
}

// ---------- Login (usa la misma tabla `usuarios` del sistema de ventas) ----------

/** Paginas protegidas: si no hay sesion, manda al login. */
function requireLogin(): void
{
    if (empty($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}
