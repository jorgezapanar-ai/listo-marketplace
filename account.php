<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$user = require_auth();
$pageTitle = 'Mi cuenta';
$pageSubtitle = 'Datos de acceso y seguridad';
$activeDashboard = 'account';
require __DIR__ . '/templates/dashboard-header.php';
?>
<div class="page-head"><div><h2>Configuración de cuenta</h2><p>Actualiza tu nombre, correo o contraseña.</p></div></div>
<div class="panel-grid">
  <section class="panel">
    <div class="panel-header"><div><h2>Datos personales</h2><p>El celular de acceso no puede cambiarse sin validación administrativa.</p></div></div>
    <div class="panel-body">
      <?php if ((int) $user['must_change_password'] === 1): ?><div class="alert alert-warning">Estás usando una contraseña temporal. Debes reemplazarla para continuar.</div><?php endif; ?>
      <form class="form-stack" action="<?= e(url('actions.php')) ?>" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="account_update">
        <div class="field"><label for="full_name">Nombre completo</label><input id="full_name" name="full_name" value="<?= e(old('full_name', $user['full_name'])) ?>" maxlength="120" required><?php if ($error = form_error('full_name')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
        <div class="field"><label for="phone">Celular de acceso</label><input id="phone" value="<?= e($user['phone']) ?>" disabled><small>Contacta al administrador si necesitas cambiarlo.</small></div>
        <div class="field"><label for="email">Correo <span class="muted">(opcional)</span></label><input id="email" name="email" type="email" value="<?= e(old('email', $user['email'] ?? '')) ?>" maxlength="190"><?php if ($error = form_error('email')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
        <fieldset><legend>Cambiar contraseña</legend>
          <div class="field"><label for="current_password">Contraseña actual</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" maxlength="128"><?php if ($error = form_error('current_password')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
          <div class="form-grid"><div class="field"><label for="new_password">Nueva contraseña</label><input id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="10" maxlength="128"><?php if ($error = form_error('new_password')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div><div class="field"><label for="password_confirmation">Confirmar</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="10" maxlength="128"><?php if ($error = form_error('password_confirmation')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div></div>
          <small class="muted text-small">Déjalos vacíos si no deseas cambiar tu contraseña.</small>
        </fieldset>
        <button class="btn btn-primary" type="submit">Guardar cambios</button>
      </form>
    </div>
  </section>
  <aside class="panel"><div class="panel-header"><h2>Seguridad</h2></div><div class="panel-body"><div class="detail-list"><div class="detail-row"><span>Rol</span><strong><?= e(['client' => 'Cliente', 'provider' => 'Proveedor', 'admin' => 'Administrador'][$user['role']]) ?></strong></div><div class="detail-row"><span>Celular</span><strong><?= e(mask_phone($user['phone'])) ?></strong></div><div class="detail-row"><span>Cuenta creada</span><strong><?= e(date_pe($user['created_at'])) ?></strong></div></div></div></aside>
</div>
<?php require __DIR__ . '/templates/dashboard-footer.php'; ?>
