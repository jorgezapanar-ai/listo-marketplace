<?php
declare(strict_types=1);

// Healthcheck interno del contenedor: consulta el endpoint HTTP real.
$response = @file_get_contents('http://127.0.0.1/health.php');
exit(is_string($response) && str_contains($response, '"status":"ok"') ? 0 : 1);
