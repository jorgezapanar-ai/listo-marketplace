<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$configFile = BASE_PATH . '/app/config.php';
if (!is_file($configFile)) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "La plataforma no está instalada. Ejecuta install.php desde el navegador.\n");
        exit(1);
    }
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $prefix = str_contains($script, '/admin/') ? '../' : '';
    header('Location: ' . $prefix . 'install.php');
    exit;
}

$GLOBALS['app_config'] = require $configFile;
require_once BASE_PATH . '/app/helpers.php';

date_default_timezone_set((string) config('app.timezone', 'America/Lima'));

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$isCli = PHP_SAPI === 'cli';
$sessionDisabled = defined('APP_NO_SESSION') && APP_NO_SESSION === true;

if (!$isCli && !$sessionDisabled) {
    $cookiePath = (string) (parse_url((string) config('app.url', ''), PHP_URL_PATH) ?: '/');
    $cookiePath = '/' . trim($cookiePath, '/');
    $cookiePath = $cookiePath === '/' ? '/' : $cookiePath . '/';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name((string) config('session.name', 'listo_session'));
    session_set_cookie_params([
        'lifetime' => (int) config('session.lifetime', 7200),
        'path' => $cookiePath,
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
} else {
    $_SESSION = $_SESSION ?? [];
}

require_once BASE_PATH . '/app/database.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/marketplace.php';

if (!$sessionDisabled && !$isCli) {
    pull_form_state();
}

if (!$isCli) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, private');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data:; style-src 'self'; script-src 'self'; connect-src 'self'");
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

if ((bool) config('app.debug', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

set_exception_handler(static function (Throwable $exception): void {
    $message = sprintf("[%s] %s in %s:%d\n%s\n", date('c'), $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTraceAsString());
    @file_put_contents(BASE_PATH . '/storage/logs/app.log', $message, FILE_APPEND | LOCK_EX);
    if (!headers_sent()) {
        http_response_code(500);
    }
    if ((bool) config('app.debug', false)) {
        echo '<pre>' . e($message) . '</pre>';
        return;
    }
    require BASE_PATH . '/templates/error-500.php';
});
