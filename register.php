<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
if (is_authenticated()) redirect('dashboard.php');
$requestedRole = (string) ($_GET['role'] ?? old('role', 'client'));
if (!in_array($requestedRole, ['client', 'provider'], true)) $requestedRole = 'client';
$messages = flashes();
?>
<!doctype html>
<html lang="es-PE">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Crea tu cuenta de cliente o proveedor en <?= e(site_name()) ?>."><meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#0d4e45">
  <title>Crear cuenta · <?= e(site_name()) ?></title><link rel="icon" href="<?= e(asset('favicon.svg')) ?>" type="image/svg+xml"><link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>"><script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body class="auth-page">
  <main class="auth-shell">
    <a class="brand brand-center" href="<?= e(url()) ?>"><span class="brand-mark"><?= icon('link') ?></span><strong><?= e(site_name()) ?></strong></a>
    <section class="auth-card">
      <div class="auth-heading"><span class="eyebrow">Crea tu cuenta</span><h1>Empieza en pocos pasos</h1><p>Tu celular será el identificador para iniciar sesión.</p></div>
      <?php foreach ($messages as $message): ?><div class="alert alert-<?= e($message['type']) ?>"><?= e($message['message']) ?></div><?php endforeach; ?>
      <?php if (!empty($_SESSION['draft_request'])): ?><div class="alert alert-info">Tu solicitud está guardada y se publicará al terminar el registro.</div><?php endif; ?>
      <?php if ($error = form_error('general')): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form class="form-stack" action="<?= e(url('actions.php')) ?>" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="register">
        <fieldset><legend>¿Cómo usarás la plataforma?</legend><div class="role-picker">
          <div class="role-option"><input id="role-client" name="role" type="radio" value="client"<?= checked($requestedRole, 'client') ?>><label for="role-client"><?= icon('search') ?><span><strong>Necesito un servicio</strong>Publicaré solicitudes</span></label></div>
          <div class="role-option"><input id="role-provider" name="role" type="radio" value="provider"<?= checked($requestedRole, 'provider') ?>><label for="role-provider"><?= icon('tools') ?><span><strong>Ofrezco servicios</strong>Enviaré propuestas</span></label></div>
        </div><?php if ($error = form_error('role')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></fieldset>
        <div class="field"><label for="full_name">Nombre completo</label><input id="full_name" name="full_name" autocomplete="name" maxlength="120" value="<?= e(old('full_name')) ?>" required aria-invalid="<?= form_error('full_name') ? 'true' : 'false' ?>"><?php if ($error = form_error('full_name')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
        <div class="field"><label for="phone">Celular</label><div class="input-shell"><?= icon('phone') ?><input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" placeholder="999 999 999" value="<?= e(old('phone')) ?>" required aria-invalid="<?= form_error('phone') ? 'true' : 'false' ?>"></div><small>Usaremos este número para iniciar sesión y coordinar cuando exista una propuesta aceptada.</small><?php if ($error = form_error('phone')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
        <div class="field"><label for="email">Correo <span class="muted">(opcional)</span></label><input id="email" name="email" type="email" autocomplete="email" maxlength="190" value="<?= e(old('email')) ?>" aria-invalid="<?= form_error('email') ? 'true' : 'false' ?>"><?php if ($error = form_error('email')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
        <div class="form-grid">
          <div class="field"><label for="password">Contraseña</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="10" maxlength="128" required aria-invalid="<?= form_error('password') ? 'true' : 'false' ?>"><?php if ($error = form_error('password')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
          <div class="field"><label for="password_confirmation">Confirmar</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="10" maxlength="128" required aria-invalid="<?= form_error('password_confirmation') ? 'true' : 'false' ?>"><?php if ($error = form_error('password_confirmation')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
        </div>
        <div><label class="checkbox-row" for="terms"><input id="terms" name="terms" type="checkbox" value="1"<?= checked(old('terms'), '1') ?> required><span>Acepto los <a href="<?= e(url('terms.php')) ?>" target="_blank" rel="noopener noreferrer">términos</a> y la <a href="<?= e(url('privacy.php')) ?>" target="_blank" rel="noopener noreferrer">política de privacidad</a>.</span></label><?php if ($error = form_error('terms')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?></div>
        <button class="btn btn-primary btn-block btn-large" type="submit">Crear mi cuenta <?= icon('arrow') ?></button>
      </form>
      <div class="auth-links"><span>¿Ya tienes cuenta?</span><a href="<?= e(url('login.php')) ?>">Inicia sesión</a></div>
    </section>
  </main>
</body>
</html>
