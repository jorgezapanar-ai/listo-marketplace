<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$user = require_auth();
$requestId = (int) ($_GET['id'] ?? 0);
$request = service_request($requestId);
if (!$request) { http_response_code(404); require __DIR__ . '/templates/error-404.php'; exit; }
if (!can_access_request($request, $user)) { http_response_code(403); require BASE_PATH . '/templates/error-403.php'; exit; }

$proposals = request_proposals($requestId);
$currentProposal = null;
$acceptedProposal = null;
foreach ($proposals as $proposal) {
    if ($user['role'] === 'provider' && (int) $proposal['provider_id'] === (int) $user['id']) $currentProposal = $proposal;
    if ($proposal['status'] === 'accepted') $acceptedProposal = $proposal;
}
$contactEnabled = in_array($request['status'], ['matched', 'in_progress', 'completed'], true);
$selectedProviderAvailable = ($request['selected_provider_status'] ?? null) === 'active'
    && ($request['selected_provider_verification'] ?? null) !== 'rejected';
$review = null;
if ($request['status'] === 'completed') {
    $statement = db()->prepare('SELECT * FROM reviews WHERE request_id = ?');
    $statement->execute([$requestId]);
    $review = $statement->fetch() ?: null;
}

$pageTitle = 'Solicitud #' . $requestId;
$pageSubtitle = $request['category_name'] . ' · ' . $request['district_name'];
$activeDashboard = $user['role'] === 'client' ? 'requests' : ($user['role'] === 'provider' ? 'opportunities' : 'requests');
require __DIR__ . '/templates/dashboard-header.php';
?>
<div class="page-head">
  <div><h2><?= e($request['title']) ?></h2><p>Publicada el <?= e(date_pe($request['created_at'])) ?></p></div>
  <div class="page-actions"><span class="status status-<?= e(status_class($request['status'])) ?>"><?= e(status_label($request['status'])) ?></span><?php if ($user['role'] === 'client'): ?><a class="btn btn-secondary" href="<?= e(url('requests.php')) ?>">Volver</a><?php elseif ($user['role'] === 'provider'): ?><a class="btn btn-secondary" href="<?= e(url('opportunities.php')) ?>">Volver</a><?php endif; ?></div>
</div>

<div class="panel-grid">
  <section class="detail-card">
    <div class="detail-title"><div><h2>Detalle del trabajo</h2><p>Solicitud #<?= $requestId ?></p></div><span class="status status-<?= e($request['priority'] === 'urgent' ? 'danger' : 'warning') ?>"><?= e(priority_label($request['priority'])) ?></span></div>
    <div class="description-box"><?= e($request['description']) ?></div>
    <div class="detail-list">
      <div class="detail-row"><span>Categoría</span><strong><?= e($request['category_name']) ?></strong></div>
      <div class="detail-row"><span>Distrito</span><strong><?= e($request['district_name']) ?></strong></div>
      <div class="detail-row"><span>Fecha ideal</span><strong><?= e(date_pe($request['preferred_date'])) ?></strong></div>
      <div class="detail-row"><span>Presupuesto indicado</span><strong><?php if ($request['budget_min'] || $request['budget_max']): ?><?= e(money($request['budget_min'])) ?><?= $request['budget_max'] ? ' – ' . e(money($request['budget_max'])) : '' ?><?php else: ?>Por acordar<?php endif; ?></strong></div>
      <?php if (($user['role'] === 'client' || ($currentProposal && $currentProposal['status'] === 'accepted')) && $request['location_reference']): ?><div class="detail-row"><span>Referencia privada</span><strong><?= e($request['location_reference']) ?></strong></div><?php endif; ?>
      <?php if ($user['role'] === 'provider'): ?><div class="detail-row"><span>Cliente</span><strong><?= e(($currentProposal && $currentProposal['status'] === 'accepted') ? $request['client_name'] : first_name($request['client_name'])) ?></strong></div><?php endif; ?>
    </div>

    <?php if ($user['role'] === 'client'): ?>
      <div class="proposal-actions">
        <?php if ($request['status'] === 'open'): ?><form action="<?= e(url('actions.php')) ?>" method="post" data-confirm="¿Cancelar esta solicitud? Los proveedores ya no podrán enviar propuestas."><?= csrf_field() ?><input type="hidden" name="action" value="request_status"><input type="hidden" name="request_id" value="<?= $requestId ?>"><input type="hidden" name="status" value="cancelled"><button class="btn btn-ghost" type="submit">Cancelar solicitud</button></form><?php endif; ?>
        <?php if ($request['status'] === 'matched'): ?><form action="<?= e(url('actions.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="request_status"><input type="hidden" name="request_id" value="<?= $requestId ?>"><input type="hidden" name="status" value="in_progress"><button class="btn btn-primary" type="submit">Marcar servicio iniciado</button></form><form action="<?= e(url('actions.php')) ?>" method="post" data-confirm="¿Cancelar la coordinación con el proveedor elegido?"><?= csrf_field() ?><input type="hidden" name="action" value="request_status"><input type="hidden" name="request_id" value="<?= $requestId ?>"><input type="hidden" name="status" value="cancelled"><button class="btn btn-ghost" type="submit">Cancelar</button></form><?php endif; ?>
        <?php if ($request['status'] === 'in_progress'): ?><form action="<?= e(url('actions.php')) ?>" method="post" data-confirm="Confirma solo si el servicio ya terminó."><?= csrf_field() ?><input type="hidden" name="action" value="request_status"><input type="hidden" name="request_id" value="<?= $requestId ?>"><input type="hidden" name="status" value="completed"><button class="btn btn-primary" type="submit">Marcar como completado</button></form><?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <aside>
    <?php if ($user['role'] === 'client'): ?>
      <?php if ($acceptedProposal && $contactEnabled && $selectedProviderAvailable): ?>
        <div class="contact-card"><h3>Contacto habilitado</h3><p>Elegiste a <?= e($acceptedProposal['provider_name']) ?>. Coordinen alcance, dirección exacta y horario antes de iniciar.</p><a class="btn btn-primary btn-block" href="<?= e(person_whatsapp_url((string) $request['selected_provider_phone'], 'Hola ' . $acceptedProposal['provider_name'] . ', elegí tu propuesta para mi solicitud "' . $request['title'] . '" en ' . site_name() . '.')) ?>" target="_blank" rel="noopener noreferrer"><?= icon('phone') ?> Contactar por WhatsApp</a></div>
      <?php elseif ($acceptedProposal && $contactEnabled): ?>
        <div class="alert alert-warning">El perfil del proveedor elegido no está disponible en este momento. Contacta a soporte antes de continuar.</div>
      <?php else: ?>
        <div class="panel"><div class="panel-header"><h3>Propuestas recibidas</h3><span class="status status-info"><?= count(array_filter($proposals, fn($p) => $p['status'] === 'pending')) ?></span></div><div class="panel-body"><p class="muted text-small">Compara precio, disponibilidad, experiencia y reseñas. No elijas solo por el precio más bajo.</p></div></div>
      <?php endif; ?>
    <?php elseif ($user['role'] === 'provider' && $currentProposal && $currentProposal['status'] === 'accepted' && $contactEnabled): ?>
      <div class="contact-card"><h3>Tu propuesta fue elegida</h3><p>Ya puedes contactar a <?= e($request['client_name']) ?> para confirmar los detalles del servicio.</p><a class="btn btn-primary btn-block" href="<?= e(person_whatsapp_url((string) $request['client_phone'], 'Hola ' . $request['client_name'] . ', soy ' . $user['full_name'] . ' de ' . site_name() . '. Coordinemos la solicitud "' . $request['title'] . '".')) ?>" target="_blank" rel="noopener noreferrer"><?= icon('phone') ?> Contactar al cliente</a></div>
    <?php endif; ?>
  </aside>
</div>

<?php if ($user['role'] === 'client'): ?>
  <section class="panel">
    <div class="panel-header"><div><h2>Propuestas</h2><p>Información enviada por proveedores aprobados.</p></div></div>
    <?php if ($proposals === []): ?><div class="empty-state"><span class="empty-icon"><?= icon('chat') ?></span><h3>Aún no recibes propuestas</h3><p>Los proveedores compatibles podrán revisar tu solicitud mientras esté abierta.</p></div>
    <?php else: ?><div class="panel-body proposal-list"><?php foreach ($proposals as $proposal): $providerAvailable = $proposal['provider_account_status'] === 'active' && $proposal['verification_status'] === 'verified'; ?><article class="proposal-card<?= $proposal['status'] === 'accepted' ? ' accepted' : '' ?>"><div class="proposal-head"><div class="proposal-person"><span class="avatar avatar-small"><?= e(user_initials($proposal['provider_name'])) ?></span><div><strong><?= e($proposal['provider_name']) ?></strong><span><?= e($proposal['headline'] ?: 'Proveedor aprobado') ?> · <?= (int) $proposal['years_experience'] ?> años</span></div></div><div class="proposal-price"><?= e(money($proposal['price'])) ?></div></div><p><?= e($proposal['message']) ?></p><div class="request-meta"><span><?= icon('calendar') ?>Disponible: <?= e(date_pe($proposal['available_date'])) ?></span><span><?= icon('star') ?><?= (int) $proposal['review_count'] > 0 ? e(number_format((float) $proposal['rating_avg'], 1)) . ' (' . (int) $proposal['review_count'] . ')' : 'Sin reseñas aún' ?></span></div><div class="proposal-actions"><?php if ($providerAvailable): ?><a class="btn btn-secondary btn-small" href="<?= e(url('provider.php?id=' . (int) $proposal['provider_id'])) ?>">Ver perfil</a><?php endif; ?><?php if ($request['status'] === 'open' && $proposal['status'] === 'pending' && $providerAvailable): ?><form action="<?= e(url('actions.php')) ?>" method="post" data-confirm="¿Elegir esta propuesta? Las demás serán rechazadas y se habilitará el contacto."><?= csrf_field() ?><input type="hidden" name="action" value="proposal_accept"><input type="hidden" name="proposal_id" value="<?= (int) $proposal['id'] ?>"><button class="btn btn-primary btn-small" type="submit">Elegir propuesta</button></form><?php elseif (!$providerAvailable && $proposal['status'] === 'pending'): ?><span class="status status-danger">Proveedor no disponible</span><?php else: ?><span class="status status-<?= e(status_class($proposal['status'])) ?>"><?= e(status_label($proposal['status'])) ?></span><?php endif; ?></div></article><?php endforeach; ?></div><?php endif; ?>
  </section>

  <?php if ($request['status'] === 'completed'): ?>
    <section class="panel" id="review"><div class="panel-header"><h2>Evaluación del servicio</h2></div><div class="panel-body">
      <?php if ($review): ?><div class="alert alert-<?= e(status_class($review['status'])) ?>">Tu reseña de <?= (int) $review['rating'] ?>/5 está <?= e(mb_strtolower(status_label($review['status']))) ?>.</div><?php else: ?><form class="form-stack" action="<?= e(url('actions.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="review_create"><input type="hidden" name="request_id" value="<?= $requestId ?>"><div class="field"><label for="rating">Calificación</label><select id="rating" name="rating" required><option value="">Selecciona</option><?php for ($rating = 5; $rating >= 1; $rating--): ?><option value="<?= $rating ?>"<?= selected(old('rating'), $rating) ?>><?= $rating ?>/5</option><?php endfor; ?></select><?php if ($error = form_error('rating')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div><div class="field"><label for="comment">Comentario <span class="muted">(opcional)</span></label><textarea id="comment" name="comment" maxlength="1000"><?= e(old('comment')) ?></textarea><?php if ($error = form_error('comment')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div><button class="btn btn-primary" type="submit">Enviar reseña</button></form><?php endif; ?>
    </div></section>
  <?php endif; ?>
<?php elseif ($user['role'] === 'provider'): ?>
  <section class="panel"><div class="panel-header"><div><h2>Tu propuesta</h2><p>Indica claramente qué incluye el monto.</p></div></div><div class="panel-body">
    <?php if ($request['status'] !== 'open'): ?><div class="alert alert-info">La solicitud ya no acepta nuevas propuestas.</div>
    <?php elseif (!provider_is_verified((int) $user['id'])): ?><div class="alert alert-warning">Necesitas un perfil aprobado para enviar una propuesta. <a href="<?= e(url('provider-edit.php')) ?>">Revisar perfil</a></div>
    <?php elseif ($currentProposal && $currentProposal['status'] === 'accepted'): ?><div class="alert alert-success">Tu propuesta fue aceptada.</div>
    <?php else: ?><form class="form-stack" action="<?= e(url('actions.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="proposal_submit"><input type="hidden" name="request_id" value="<?= $requestId ?>"><div class="form-grid"><div class="field"><label for="price">Precio total propuesto</label><input id="price" name="price" type="number" min="1" max="1000000" step="0.01" value="<?= e(old('price', $currentProposal['price'] ?? '')) ?>" required><?php if ($error = form_error('price')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div><div class="field"><label for="available_date">Fecha disponible</label><input id="available_date" name="available_date" type="date" value="<?= e(old('available_date', $currentProposal['available_date'] ?? '')) ?>" data-min-today required><?php if ($error = form_error('available_date')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div><div class="field field-full"><label for="message">Alcance de tu propuesta</label><textarea id="message" name="message" minlength="20" maxlength="1500" placeholder="Explica qué incluye, materiales considerados, duración estimada y cualquier condición relevante." required><?= e(old('message', $currentProposal['message'] ?? '')) ?></textarea><?php if ($error = form_error('message')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div></div><button class="btn btn-primary" type="submit"><?= $currentProposal ? 'Actualizar propuesta' : 'Enviar propuesta' ?></button></form><?php endif; ?>
  </div></section>
<?php elseif ($user['role'] === 'admin'): ?>
  <section class="panel"><div class="panel-header"><h2>Propuestas registradas</h2></div><div class="panel-body"><p class="muted text-small">Vista administrativa: <?= count($proposals) ?> propuesta<?= count($proposals) === 1 ? '' : 's' ?>.</p></div></section>
<?php endif; ?>
<?php require __DIR__ . '/templates/dashboard-footer.php'; ?>
