<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$user = require_role('client');
$categoryRows = categories();
$districtRows = districts();
$targetProviderId = (int) ($_GET['provider'] ?? old('target_provider_id', 0));
$targetProvider = $targetProviderId > 0 ? provider_public_profile($targetProviderId) : null;
if ($targetProviderId > 0 && !$targetProvider) $targetProviderId = 0;
if ($targetProvider) {
    $providerCategoryIds = array_map(static fn(array $row): int => (int) $row['id'], $targetProvider['categories']);
    $providerDistrictIds = array_map(static fn(array $row): int => (int) $row['id'], $targetProvider['districts']);
    $categoryRows = array_values(array_filter($categoryRows, static fn(array $row): bool => in_array((int) $row['id'], $providerCategoryIds, true)));
    $districtRows = array_values(array_filter($districtRows, static fn(array $row): bool => in_array((int) $row['id'], $providerDistrictIds, true)));
}
$defaultCategory = $targetProvider && count($categoryRows) === 1 ? (int) $categoryRows[0]['id'] : 0;
$defaultDistrict = $targetProvider && count($districtRows) === 1 ? (int) $districtRows[0]['id'] : 0;
$requestedCategory = (int) ($_GET['category'] ?? old('category_id', $defaultCategory));
$pageTitle = 'Nueva solicitud';
$pageSubtitle = 'Describe el trabajo para recibir propuestas';
$activeDashboard = 'new-request';
require __DIR__ . '/templates/dashboard-header.php';
?>
<div class="page-head"><div><h2>Publica una necesidad</h2><p>Los datos exactos de contacto no serán visibles hasta que elijas una propuesta.</p></div></div>
<div class="panel-grid">
  <section class="panel"><div class="panel-header"><div><h2>Detalles del servicio</h2><p>Cuanta más claridad brindes, mejores serán las propuestas.</p></div></div><div class="panel-body">
    <?php if ($error = form_error('general')): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form class="form-stack" action="<?= e(url('actions.php')) ?>" method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="request_create"><input type="hidden" name="target_provider_id" value="<?= $targetProviderId ?>">
      <div class="form-grid">
        <div class="field"><label for="category_id">Categoría</label><select id="category_id" name="category_id" required><option value="">Selecciona</option><?php foreach ($categoryRows as $category): ?><option value="<?= (int) $category['id'] ?>"<?= selected(old('category_id', $requestedCategory), $category['id']) ?>><?= e($category['name']) ?></option><?php endforeach; ?></select><?php if ($error = form_error('category_id')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
        <div class="field"><label for="district_id">Distrito</label><select id="district_id" name="district_id" required><option value="">Selecciona</option><?php foreach ($districtRows as $district): ?><option value="<?= (int) $district['id'] ?>"<?= selected(old('district_id', $defaultDistrict), $district['id']) ?>><?= e($district['name']) ?></option><?php endforeach; ?></select><?php if ($error = form_error('district_id')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
        <div class="field field-full"><label for="title">Título breve</label><input id="title" name="title" maxlength="140" placeholder="Ej.: Revisar dos tomacorrientes de la sala" value="<?= e(old('title')) ?>" required><?php if ($error = form_error('title')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
        <div class="field field-full"><label for="description">Descripción</label><textarea id="description" name="description" minlength="20" maxlength="3000" data-count="#description-count" placeholder="Explica qué ocurre, qué resultado esperas y cualquier detalle que ayude a preparar la propuesta." required><?= e(old('description')) ?></textarea><small id="description-count"></small><?php if ($error = form_error('description')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
        <div class="field"><label for="preferred_date">Fecha ideal</label><input id="preferred_date" name="preferred_date" type="date" value="<?= e(old('preferred_date')) ?>" data-min-today></div>
        <div class="field"><label for="priority">Prioridad</label><select id="priority" name="priority"><option value="flexible"<?= selected(old('priority', 'flexible'), 'flexible') ?>>Flexible</option><option value="soon"<?= selected(old('priority'), 'soon') ?>>Lo antes posible</option><option value="urgent"<?= selected(old('priority'), 'urgent') ?>>Urgente</option></select></div>
        <div class="field"><label for="budget_min">Presupuesto mínimo <span class="muted">(opcional)</span></label><input id="budget_min" name="budget_min" type="number" min="0" step="0.01" value="<?= e(old('budget_min')) ?>" placeholder="S/ 0.00"><?php if ($error = form_error('budget_min')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
        <div class="field"><label for="budget_max">Presupuesto máximo <span class="muted">(opcional)</span></label><input id="budget_max" name="budget_max" type="number" min="0" step="0.01" value="<?= e(old('budget_max')) ?>" placeholder="S/ 0.00"><?php if ($error = form_error('budget_max')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
        <div class="field field-full"><label for="location_reference">Referencia de ubicación <span class="muted">(opcional y privada)</span></label><input id="location_reference" name="location_reference" maxlength="255" value="<?= e(old('location_reference')) ?>" placeholder="Ej.: Cerca del parque central"><small>Solo se muestra al proveedor cuya propuesta aceptes. No publiques tu dirección exacta en la descripción.</small></div>
      </div>
      <button class="btn btn-primary" type="submit">Publicar solicitud <?= icon('arrow') ?></button>
    </form>
  </div></section>
  <aside class="panel"><div class="panel-header"><h2>Antes de publicar</h2></div><div class="panel-body">
    <?php if ($targetProvider): ?><div class="contact-card"><h3>Solicitud dirigida a <?= e($targetProvider['full_name']) ?></h3><p>El pedido también podrá ser visible para otros proveedores compatibles mientras esté abierto.</p></div><?php endif; ?>
    <ul class="benefit-list"><li><span class="benefit-icon"><?= icon('shield') ?></span><div><h3>Protege tus datos</h3><p>No incluyas celular, dirección exacta ni documentos en la descripción.</p></div></li><li><span class="benefit-icon"><?= icon('briefcase') ?></span><div><h3>Compara el alcance</h3><p>El precio no es el único criterio: revisa experiencia, fecha y condiciones.</p></div></li><li><span class="benefit-icon"><?= icon('chat') ?></span><div><h3>Coordina al elegir</h3><p>El contacto por WhatsApp se habilita después de aceptar una propuesta.</p></div></li></ul>
  </div></aside>
</div>
<?php require __DIR__ . '/templates/dashboard-footer.php'; ?>
