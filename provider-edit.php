<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$user = require_role('provider');
$profile = provider_profile((int) $user['id']);
$categoryRows = categories();
$districtRows = districts();
$statement = db()->prepare('SELECT category_id FROM provider_categories WHERE provider_id = ?');
$statement->execute([$user['id']]);
$selectedCategories = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
$statement = db()->prepare('SELECT district_id FROM provider_districts WHERE provider_id = ?');
$statement->execute([$user['id']]);
$selectedDistricts = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
$pageTitle = 'Perfil profesional'; $pageSubtitle = 'Servicios, zonas y experiencia'; $activeDashboard = 'profile';
require __DIR__ . '/templates/dashboard-header.php';
?>
<div class="page-head"><div><h2>Tu perfil profesional</h2><p>Esta información ayuda a los clientes a decidir con mayor contexto.</p></div><?php if (($profile['verification_status'] ?? '') === 'verified'): ?><div class="page-actions"><a class="btn btn-secondary" href="<?= e(url('provider.php?id=' . (int) $user['id'])) ?>">Ver perfil público</a></div><?php endif; ?></div>
<?php if ($profile): ?><div class="alert alert-<?= e(status_class($profile['verification_status'])) ?>">Estado: <strong><?= e(status_label($profile['verification_status'])) ?></strong>. <?php if ($profile['verification_status'] === 'pending'): ?>El equipo debe revisar tu información antes de habilitar propuestas.<?php elseif ($profile['verification_status'] === 'rejected'): ?><?= e($profile['verification_notes'] ?: 'Actualiza la información solicitada para volver a revisión.') ?><?php else: ?>Puedes enviar propuestas a solicitudes compatibles.<?php endif; ?></div><?php endif; ?>
<section class="panel"><div class="panel-header"><div><h2>Información visible</h2><p>No publiques documentos, direcciones ni datos sensibles.</p></div></div><div class="panel-body">
  <form class="form-stack" action="<?= e(url('actions.php')) ?>" method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="provider_profile_update">
    <div class="field"><label for="headline">Titular profesional</label><input id="headline" name="headline" maxlength="160" value="<?= e(old('headline', $profile['headline'] ?? '')) ?>" placeholder="Ej.: Electricista residencial para instalaciones y mantenimiento" required><?php if ($error = form_error('headline')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
    <div class="form-grid"><div class="field"><label for="years_experience">Años de experiencia</label><input id="years_experience" name="years_experience" type="number" min="0" max="60" value="<?= e(old('years_experience', $profile['years_experience'] ?? 0)) ?>" required></div><div class="field"><label for="availability_text">Disponibilidad habitual</label><input id="availability_text" name="availability_text" maxlength="255" value="<?= e(old('availability_text', $profile['availability_text'] ?? '')) ?>" placeholder="Ej.: Lunes a sábado de 8 a. m. a 6 p. m." required><?php if ($error = form_error('availability_text')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div></div>
    <div class="field"><label for="bio">Experiencia y forma de trabajo</label><textarea id="bio" name="bio" minlength="80" maxlength="2000" data-count="#bio-count" placeholder="Describe tu experiencia, los trabajos que realizas, cómo preparas una propuesta y qué puede esperar el cliente." required><?= e(old('bio', $profile['bio'] ?? '')) ?></textarea><small id="bio-count"></small><?php if ($error = form_error('bio')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
    <fieldset><legend>Servicios que brindas <span class="muted text-small">(máximo 5)</span></legend><div class="checkbox-grid"><?php $categoryValues = old('categories', $selectedCategories); foreach ($categoryRows as $category): ?><div class="check-card"><input id="category-<?= (int) $category['id'] ?>" name="categories[]" type="checkbox" value="<?= (int) $category['id'] ?>"<?= checked($categoryValues, $category['id']) ?>><label for="category-<?= (int) $category['id'] ?>"><?= e($category['name']) ?></label></div><?php endforeach; ?></div><?php if ($error = form_error('categories')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></fieldset>
    <fieldset><legend>Distritos donde trabajas <span class="muted text-small">(máximo 20)</span></legend><div class="checkbox-grid"><?php $districtValues = old('districts', $selectedDistricts); foreach ($districtRows as $district): ?><div class="check-card"><input id="district-<?= (int) $district['id'] ?>" name="districts[]" type="checkbox" value="<?= (int) $district['id'] ?>"<?= checked($districtValues, $district['id']) ?>><label for="district-<?= (int) $district['id'] ?>"><?= e($district['name']) ?></label></div><?php endforeach; ?></div><?php if ($error = form_error('districts')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></fieldset>
    <button class="btn btn-primary" type="submit">Guardar y enviar a revisión</button>
  </form>
</div></section>
<?php require __DIR__ . '/templates/dashboard-footer.php'; ?>
