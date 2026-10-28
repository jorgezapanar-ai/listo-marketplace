<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$user = require_role('client');
$status = (string) ($_GET['status'] ?? '');
$allowedStatuses = ['open','matched','in_progress','completed','cancelled'];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$where = 'r.client_id = ?';
$params = [$user['id']];
if (in_array($status, $allowedStatuses, true)) { $where .= ' AND r.status = ?'; $params[] = $status; }
$countStatement = db()->prepare("SELECT COUNT(*) FROM service_requests r WHERE $where");
$countStatement->execute($params);
$total = (int) $countStatement->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$statement = db()->prepare("SELECT r.id, r.title, r.status, r.preferred_date, r.created_at, c.name AS category_name, d.name AS district_name, COUNT(p.id) AS proposal_count FROM service_requests r JOIN categories c ON c.id = r.category_id JOIN districts d ON d.id = r.district_id LEFT JOIN proposals p ON p.request_id = r.id AND p.status IN ('pending','accepted') WHERE $where GROUP BY r.id, r.title, r.status, r.preferred_date, r.created_at, c.name, d.name ORDER BY r.created_at DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
$statement->execute($params);
$requests = $statement->fetchAll();
$pageTitle = 'Mis solicitudes'; $pageSubtitle = 'Pedidos, propuestas y servicios'; $activeDashboard = 'requests';
require __DIR__ . '/templates/dashboard-header.php';
?>
<div class="page-head"><div><h2>Tus solicitudes</h2><p><?= $total ?> resultado<?= $total === 1 ? '' : 's' ?></p></div><div class="page-actions"><a class="btn btn-primary" href="<?= e(url('request-new.php')) ?>"><?= icon('plus') ?> Nueva solicitud</a></div></div>
<form class="filter-bar" method="get"><div class="field"><label for="status">Estado</label><select id="status" name="status" data-auto-submit><option value="">Todos</option><?php foreach ($allowedStatuses as $option): ?><option value="<?= e($option) ?>"<?= selected($status, $option) ?>><?= e(status_label($option)) ?></option><?php endforeach; ?></select></div><div></div><button class="btn btn-secondary" type="submit">Filtrar</button></form>
<section class="panel">
  <?php if ($requests === []): ?><div class="empty-state"><span class="empty-icon"><?= icon('briefcase') ?></span><h3>No encontramos solicitudes</h3><p>Cambia el filtro o publica una nueva necesidad.</p><a class="btn btn-primary" href="<?= e(url('request-new.php')) ?>">Nueva solicitud</a></div>
  <?php else: ?><div class="request-list panel-body"><?php foreach ($requests as $request): ?><a class="request-item" href="<?= e(url('request-view.php?id=' . (int) $request['id'])) ?>"><div><h3><?= e($request['title']) ?></h3><div class="request-meta"><span><?= icon('briefcase') ?><?= e($request['category_name']) ?></span><span><?= icon('map') ?><?= e($request['district_name']) ?></span><span><?= icon('calendar') ?><?= e(date_pe($request['preferred_date'])) ?></span><span><?= icon('chat') ?><?= (int) $request['proposal_count'] ?> propuestas</span></div></div><span class="status status-<?= e(status_class($request['status'])) ?>"><?= e(status_label($request['status'])) ?></span></a><?php endforeach; ?></div><?php endif; ?>
</section>
<?php if ($pages > 1): ?><nav class="pagination" aria-label="Paginación"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="<?= $i === $page ? 'active' : '' ?>" href="?status=<?= e(urlencode($status)) ?>&amp;page=<?= $i ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
<?php require __DIR__ . '/templates/dashboard-footer.php'; ?>
