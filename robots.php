<?php
declare(strict_types=1);

define('APP_NO_SESSION', true);
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$basePath = trim((string) (parse_url((string) config('app.url', ''), PHP_URL_PATH) ?: ''), '/');
$prefix = $basePath === '' ? '/' : '/' . $basePath . '/';
$privatePaths = [
    'admin/', 'app/', 'database/', 'storage/', 'templates/',
    'dashboard.php', 'account.php', 'actions.php', 'request-view.php',
    'requests.php', 'opportunities.php', 'proposals.php', 'provider-edit.php',
];
?>User-agent: *
Allow: <?= $prefix ?>
<?php foreach ($privatePaths as $path): ?>Disallow: <?= $prefix . $path ?>
<?php endforeach; ?>
Sitemap: <?= url('sitemap.xml') ?>
