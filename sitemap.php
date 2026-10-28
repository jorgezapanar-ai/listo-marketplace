<?php
declare(strict_types=1);

define('APP_NO_SESSION', true);
require __DIR__ . '/app/bootstrap.php';
header('Content-Type: application/xml; charset=UTF-8');
$staticUrls = ['', 'providers.php', 'para-proveedores.php', 'terms.php', 'privacy.php'];
$providers = db()->query("SELECT u.id, GREATEST(u.updated_at, pp.updated_at) AS updated_at FROM users u JOIN provider_profiles pp ON pp.user_id=u.id WHERE u.role='provider' AND u.status='active' AND pp.verification_status='verified' ORDER BY u.id")->fetchAll();
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($staticUrls as $path): ?><url><loc><?= e(url($path)) ?></loc><changefreq><?= $path === '' ? 'daily' : 'weekly' ?></changefreq></url>
<?php endforeach; ?>
<?php foreach ($providers as $provider): ?><url><loc><?= e(url('provider.php?id=' . (int) $provider['id'])) ?></loc><lastmod><?= e((new DateTimeImmutable($provider['updated_at']))->format('Y-m-d')) ?></lastmod><changefreq>weekly</changefreq></url>
<?php endforeach; ?>
</urlset>
