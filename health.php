<?php
declare(strict_types=1);

define('APP_NO_SESSION', true);
require __DIR__ . '/app/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');
try {
    db()->query('SELECT 1')->fetchColumn();
    echo json_encode(['status' => 'ok', 'database' => 'connected', 'timestamp' => date(DATE_ATOM)], JSON_UNESCAPED_SLASHES);
} catch (Throwable) {
    http_response_code(503);
    echo json_encode(['status' => 'unavailable', 'database' => 'error', 'timestamp' => date(DATE_ATOM)], JSON_UNESCAPED_SLASHES);
}
