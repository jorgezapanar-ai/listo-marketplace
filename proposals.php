<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$user = require_role('provider');
$status = (string) ($_GET['status'] ?? '');
$allowed = ['pending','accepted','rejected','withdrawn'];
$where = 'p.provider_id = ?'; $params = [$user['id']];
if (in_array($status, $allowed, true)) { $where .= ' AND p.status = ?'; $params[] = $status; }
$statement = db()->prepare("SELECT p.*, r.id AS request_id, r.title, r.status AS request_status, c.name AS category_name, d.name AS district_name FROM proposals p JOIN service_requests r ON r.id = p.request_id JOIN categories c ON c.id = r.category_id JOIN districts d ON d.id = r.district_id WHERE $where ORDER BY p.created_at DESC");
$statement->execute($params); $proposals = $statement->fetchAll();
$pageTitle = 'Mis propuestas'; $pageSubtitle = 'Seguimiento de propuestas enviadas'; $activeDashboard = 'proposals';
require __DIR__ . '/templates/dashboard-header.php';
?>
<div class="page-head"><div><h2>Propuestas enviadas</h2><p><?= count($proposals) ?> resultado<?= count($proposals) === 1 ? '' : 's' ?></p></div><div class="page-actions"><a class="btn btn-primary" href="<?= e(url('opportunities.php')) ?>"><?= icon('search') ?> Buscar oportunidades</a></div></div>
<form class="filter-bar" method="get"><div class="field"><label for="status">Estado</label><select id="status" name="status" data-auto-submit><option value="">Todos</option><?php foreach ($allowed as $option): ?><option value="<?= e($option) ?>"<?= selected($status,$option) ?>><?= e(status_label($option)) ?></option><?php endforeach; ?></select></div><div></div><button class="btn btn-secondary">Filtrar</button></form>
<section class="panel"><?php if ($proposals === []): ?><div class="empty-state"><span class="empty-icon"><?= icon('briefcase') ?></span><h3>No hay propuestas en este estado</h3><p>Explora oportunidades y presenta una propuesta clara.</p><a class="btn btn-primary" href="<?= e(url('opportunities.php')) ?>">Ver oportunidades</a></div><?php else: ?><div class="table-scroll"><table class="data-table"><thead><tr><th>Solicitud</th><th>Categoría</th><th>Propuesta</th><th>Estado</th><th>Enviada</th><th></th></tr></thead><tbody><?php foreach ($proposals as $proposal): ?><tr><td class="table-title"><strong><?= e($proposal['title']) ?></strong><span><?= e($proposal['district_name']) ?> · Solicitud <?= e(mb_strtolower(status_label($proposal['request_status']))) ?></span></td><td><?= e($proposal['category_name']) ?></td><td><?= e(money($proposal['price'])) ?></td><td><span class="status status-<?= e(status_class($proposal['status'])) ?>"><?= e(status_label($proposal['status'])) ?></span></td><td><?= e(date_pe($proposal['created_at'])) ?></td><td><div class="table-actions"><a class="btn btn-secondary btn-small" href="<?= e(url('request-view.php?id=' . (int) $proposal['request_id'])) ?>">Ver</a><?php if ($proposal['status'] === 'pending'): ?><form action="<?= e(url('actions.php')) ?>" method="post" data-confirm="¿Retirar esta propuesta?"><?= csrf_field() ?><input type="hidden" name="action" value="proposal_withdraw"><input type="hidden" name="proposal_id" value="<?= (int) $proposal['id'] ?>"><button class="btn btn-ghost btn-small" type="submit">Retirar</button></form><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php require __DIR__ . '/templates/dashboard-footer.php'; ?>
