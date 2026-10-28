<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$user = require_role('provider');
$profile = provider_profile((int) $user['id']);
$categoryFilter = (int) ($_GET['category'] ?? 0);
$districtFilter = (int) ($_GET['district'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$where = ["r.status = 'open'", 'pc.provider_id = ?', 'pd.provider_id = ?'];
$params = [$user['id'], $user['id']];
if ($categoryFilter > 0) { $where[] = 'r.category_id = ?'; $params[] = $categoryFilter; }
if ($districtFilter > 0) { $where[] = 'r.district_id = ?'; $params[] = $districtFilter; }
$whereSql = implode(' AND ', $where);
$countStatement = db()->prepare("SELECT COUNT(DISTINCT r.id) FROM service_requests r JOIN provider_categories pc ON pc.category_id = r.category_id JOIN provider_districts pd ON pd.district_id = r.district_id WHERE $whereSql");
$countStatement->execute($params);
$total = (int) $countStatement->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage)); $page = min($page, $pages);
$statement = db()->prepare("SELECT DISTINCT r.id, r.title, r.description, r.priority, r.preferred_date, r.budget_min, r.budget_max, r.created_at, c.name AS category_name, d.name AS district_name, p.status AS proposal_status FROM service_requests r JOIN categories c ON c.id = r.category_id JOIN districts d ON d.id = r.district_id JOIN provider_categories pc ON pc.category_id = r.category_id JOIN provider_districts pd ON pd.district_id = r.district_id LEFT JOIN proposals p ON p.request_id = r.id AND p.provider_id = ? WHERE $whereSql ORDER BY (r.target_provider_id = ?) DESC, FIELD(r.priority, 'urgent','soon','flexible'), r.created_at DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
$statement->execute(array_merge([$user['id']], $params, [$user['id']]));
$requests = $statement->fetchAll();
$statement = db()->prepare('SELECT c.id, c.name FROM provider_categories pc JOIN categories c ON c.id = pc.category_id WHERE pc.provider_id = ? ORDER BY c.sort_order'); $statement->execute([$user['id']]); $providerCategories = $statement->fetchAll();
$statement = db()->prepare('SELECT d.id, d.name FROM provider_districts pd JOIN districts d ON d.id = pd.district_id WHERE pd.provider_id = ? ORDER BY d.name'); $statement->execute([$user['id']]); $providerDistricts = $statement->fetchAll();
$pageTitle = 'Oportunidades'; $pageSubtitle = 'Solicitudes compatibles con tu perfil'; $activeDashboard = 'opportunities';
require __DIR__ . '/templates/dashboard-header.php';
?>
<div class="page-head"><div><h2>Solicitudes disponibles</h2><p><?= $total ?> oportunidad<?= $total === 1 ? '' : 'es' ?> compatible<?= $total === 1 ? '' : 's' ?></p></div><div class="page-actions"><a class="btn btn-secondary" href="<?= e(url('provider-edit.php')) ?>">Editar cobertura</a></div></div>
<?php if (!$profile || $profile['verification_status'] !== 'verified'): ?><div class="alert alert-warning">Puedes explorar oportunidades, pero necesitas un perfil aprobado para enviar propuestas. <a href="<?= e(url('provider-edit.php')) ?>">Revisar perfil</a></div><?php endif; ?>
<form class="filter-bar" method="get"><div class="field"><label for="category">Categoría</label><select id="category" name="category" data-auto-submit><option value="0">Todas mis categorías</option><?php foreach ($providerCategories as $category): ?><option value="<?= (int) $category['id'] ?>"<?= selected($categoryFilter, $category['id']) ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div><div class="field"><label for="district">Distrito</label><select id="district" name="district" data-auto-submit><option value="0">Todos mis distritos</option><?php foreach ($providerDistricts as $district): ?><option value="<?= (int) $district['id'] ?>"<?= selected($districtFilter, $district['id']) ?>><?= e($district['name']) ?></option><?php endforeach; ?></select></div><button class="btn btn-secondary" type="submit">Filtrar</button></form>
<section class="panel"><?php if ($requests === []): ?><div class="empty-state"><span class="empty-icon"><?= icon('search') ?></span><h3>No hay oportunidades compatibles</h3><p>Amplía tus categorías o distritos, o vuelve a revisar más tarde.</p><a class="btn btn-primary" href="<?= e(url('provider-edit.php')) ?>">Editar mi cobertura</a></div><?php else: ?><div class="request-list panel-body"><?php foreach ($requests as $request): ?><a class="request-item" href="<?= e(url('request-view.php?id=' . (int) $request['id'])) ?>"><div><h3><?= e($request['title']) ?></h3><div class="request-meta"><span><?= icon('briefcase') ?><?= e($request['category_name']) ?></span><span><?= icon('map') ?><?= e($request['district_name']) ?></span><span><?= icon('calendar') ?><?= e(date_pe($request['preferred_date'])) ?></span><span><?= e(money($request['budget_max'] ?: $request['budget_min'])) ?></span></div></div><?php if ($request['proposal_status']): ?><span class="status status-<?= e(status_class($request['proposal_status'])) ?>"><?= e(status_label($request['proposal_status'])) ?></span><?php else: ?><span class="status status-<?= e($request['priority'] === 'urgent' ? 'danger' : 'warning') ?>"><?= e(priority_label($request['priority'])) ?></span><?php endif; ?></a><?php endforeach; ?></div><?php endif; ?></section>
<?php if ($pages > 1): ?><nav class="pagination" aria-label="Paginación"><?php for ($i=1;$i<=$pages;$i++): ?><a class="<?= $i===$page?'active':'' ?>" href="?category=<?= $categoryFilter ?>&amp;district=<?= $districtFilter ?>&amp;page=<?= $i ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
<?php require __DIR__ . '/templates/dashboard-footer.php'; ?>
