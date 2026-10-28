<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

$deletedLimits = db()->exec("DELETE FROM rate_limits WHERE window_started_at < DATE_SUB(NOW(), INTERVAL 2 DAY)");
$deletedAudits = db()->exec("DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 730 DAY)");

echo sprintf("Cleanup complete: %d rate limits, %d audit logs removed.\n", $deletedLimits, $deletedAudits);
