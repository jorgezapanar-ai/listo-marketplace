<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$user = require_auth();
if ($user['role'] === 'admin') redirect('admin/index.php');

$pageTitle = 'Hola, ' . first_name($user['full_name']);
$pageSubtitle = $user['role'] === 'client' ? 'Gestiona tus solicitudes y propuestas' : 'Gestiona tu perfil y oportunidades';
$activeDashboard = 'dashboard';

if ($user['role'] === 'client') {
    $statement = db()->prepare("SELECT COUNT(*) AS total, SUM(status = 'open') AS open_count, SUM(status IN ('matched','in_progress')) AS active_count, SUM(status = 'completed') AS completed_count FROM service_requests WHERE client_id = ?");
    $statement->execute([$user['id']]);
    $stats = $statement->fetch();
    $statement = db()->prepare("SELECT r.id, r.title, r.status, r.created_at, c.name AS category_name, d.name AS district_name, COUNT(p.id) AS proposal_count FROM service_requests r JOIN categories c ON c.id = r.category_id JOIN districts d ON d.id = r.district_id LEFT JOIN proposals p ON p.request_id = r.id AND p.status IN ('pending','accepted') WHERE r.client_id = ? GROUP BY r.id, r.title, r.status, r.created_at, c.name, d.name ORDER BY r.created_at DESC LIMIT 6");
    $statement->execute([$user['id']]);
    $recentRequests = $statement->fetchAll();
} else {
    $profile = provider_profile((int) $user['id']);
    $statement = db()->prepare("SELECT COUNT(*) AS total, SUM(status = 'pending') AS pending_count, SUM(status = 'accepted') AS accepted_count FROM proposals WHERE provider_id = ?");
    $statement->execute([$user['id']]);
    $stats = $statement->fetch();
    $statement = db()->prepare("SELECT COUNT(DISTINCT r.id) FROM service_requests r JOIN provider_categories pc ON pc.category_id = r.category_id AND pc.provider_id = ? JOIN provider_districts pd ON pd.district_id = r.district_id AND pd.provider_id = ? WHERE r.status = 'open'");
    $statement->execute([$user['id'], $user['id']]);
    $opportunityCount = (int) $statement->fetchColumn();
    $statement = db()->prepare("SELECT p.id, p.status, p.price, p.created_at, r.id AS request_id, r.title, c.name AS category_name, d.name AS district_name FROM proposals p JOIN service_requests r ON r.id = p.request_id JOIN categories c ON c.id = r.category_id JOIN districts d ON d.id = r.district_id WHERE p.provider_id = ? ORDER BY p.created_at DESC LIMIT 6");
    $statement->execute([$user['id']]);
    $recentProposals = $statement->fetchAll();
}

require __DIR__ . '/templates/dashboard-header.php';
?>
<?php if ($user['role'] === 'client'): ?>
  <div class="page-head"><div><h2>Resumen de actividad</h2><p>Consulta lo que está pasando con tus solicitudes.</p></div><div class="page-actions"><a class="btn btn-primary" href="<?= e(url('request-new.php')) ?>"><?= icon('plus') ?> Nueva solicitud</a></div></div>
  <div class="stats-grid">
    <div class="stat-card"><div class="stat-top"><span>Solicitudes</span><span class="stat-icon"><?= icon('briefcase') ?></span></div><strong><?= (int) ($stats['total'] ?? 0) ?></strong></div>
    <div class="stat-card"><div class="stat-top"><span>Abiertas</span><span class="stat-icon"><?= icon('search') ?></span></div><strong><?= (int) ($stats['open_count'] ?? 0) ?></strong></div>
    <div class="stat-card"><div class="stat-top"><span>En coordinación</span><span class="stat-icon"><?= icon('chat') ?></span></div><strong><?= (int) ($stats['active_count'] ?? 0) ?></strong></div>
    <div class="stat-card"><div class="stat-top"><span>Completadas</span><span class="stat-icon"><?= icon('check') ?></span></div><strong><?= (int) ($stats['completed_count'] ?? 0) ?></strong></div>
  </div>
  <section class="panel"><div class="panel-header"><div><h2>Solicitudes recientes</h2><p>Últimos pedidos publicados en tu cuenta.</p></div><a class="btn btn-secondary btn-small" href="<?= e(url('requests.php')) ?>">Ver todas</a></div>
    <?php if ($recentRequests === []): ?><div class="empty-state"><span class="empty-icon"><?= icon('briefcase') ?></span><h3>Aún no publicaste solicitudes</h3><p>Describe lo que necesitas y empieza a recibir propuestas.</p><a class="btn btn-primary" href="<?= e(url('request-new.php')) ?>">Publicar mi primera solicitud</a></div>
    <?php else: ?><div class="request-list panel-body"><?php foreach ($recentRequests as $request): ?><a class="request-item" href="<?= e(url('request-view.php?id=' . (int) $request['id'])) ?>"><div><h3><?= e($request['title']) ?></h3><div class="request-meta"><span><?= icon('briefcase') ?><?= e($request['category_name']) ?></span><span><?= icon('map') ?><?= e($request['district_name']) ?></span><span><?= icon('chat') ?><?= (int) $request['proposal_count'] ?> propuestas</span></div></div><span class="status status-<?= e(status_class($request['status'])) ?>"><?= e(status_label($request['status'])) ?></span></a><?php endforeach; ?></div><?php endif; ?>
  </section>
<?php else: ?>
  <div class="page-head"><div><h2>Resumen profesional</h2><p>Revisa tu validación, propuestas y trabajos.</p></div><div class="page-actions"><a class="btn btn-primary" href="<?= e(url('opportunities.php')) ?>"><?= icon('search') ?> Ver oportunidades</a></div></div>
  <?php if (!$profile || empty($profile['headline']) || empty($profile['bio'])): ?><div class="alert alert-warning">Completa tus servicios, zonas y experiencia para solicitar la validación del perfil. <a href="<?= e(url('provider-edit.php')) ?>">Completar perfil</a></div><?php elseif ($profile['verification_status'] !== 'verified'): ?><div class="alert alert-<?= $profile['verification_status'] === 'rejected' ? 'danger' : 'info' ?>">Estado de validación: <strong><?= e(status_label($profile['verification_status'])) ?></strong>. <?= $profile['verification_status'] === 'rejected' ? e($profile['verification_notes'] ?: 'Actualiza tu perfil y vuelve a enviarlo.') : 'Un administrador revisará tu información antes de habilitar propuestas.' ?></div><?php endif; ?>
  <div class="stats-grid">
    <div class="stat-card"><div class="stat-top"><span>Oportunidades</span><span class="stat-icon"><?= icon('search') ?></span></div><strong><?= $opportunityCount ?></strong></div>
    <div class="stat-card"><div class="stat-top"><span>Propuestas</span><span class="stat-icon"><?= icon('briefcase') ?></span></div><strong><?= (int) ($stats['total'] ?? 0) ?></strong></div>
    <div class="stat-card"><div class="stat-top"><span>Pendientes</span><span class="stat-icon"><?= icon('calendar') ?></span></div><strong><?= (int) ($stats['pending_count'] ?? 0) ?></strong></div>
    <div class="stat-card"><div class="stat-top"><span>Aceptadas</span><span class="stat-icon"><?= icon('check') ?></span></div><strong><?= (int) ($stats['accepted_count'] ?? 0) ?></strong></div>
  </div>
  <section class="panel"><div class="panel-header"><div><h2>Propuestas recientes</h2><p>Seguimiento de tus últimas postulaciones.</p></div><a class="btn btn-secondary btn-small" href="<?= e(url('proposals.php')) ?>">Ver todas</a></div>
    <?php if ($recentProposals === []): ?><div class="empty-state"><span class="empty-icon"><?= icon('briefcase') ?></span><h3>Aún no enviaste propuestas</h3><p>Busca solicitudes compatibles con tus categorías y zonas.</p><a class="btn btn-primary" href="<?= e(url('opportunities.php')) ?>">Explorar oportunidades</a></div>
    <?php else: ?><div class="request-list panel-body"><?php foreach ($recentProposals as $proposal): ?><a class="request-item" href="<?= e(url('request-view.php?id=' . (int) $proposal['request_id'])) ?>"><div><h3><?= e($proposal['title']) ?></h3><div class="request-meta"><span><?= icon('briefcase') ?><?= e($proposal['category_name']) ?></span><span><?= icon('map') ?><?= e($proposal['district_name']) ?></span><span><?= money($proposal['price']) ?></span></div></div><span class="status status-<?= e(status_class($proposal['status'])) ?>"><?= e(status_label($proposal['status'])) ?></span></a><?php endforeach; ?></div><?php endif; ?>
  </section>
<?php endif; ?>
<?php require __DIR__ . '/templates/dashboard-footer.php'; ?>
