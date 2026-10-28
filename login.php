<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
if (is_authenticated()) redirect('dashboard.php');
$messages = flashes();
?>
<!doctype html>
<html lang="es-PE">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Accede a <?= e(site_name()) ?> con tu celular y contraseña."><meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#0d4e45">
  <title>Iniciar sesión · <?= e(site_name()) ?></title><link rel="icon" href="<?= e(asset('favicon.svg')) ?>" type="image/svg+xml"><link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>"><script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body class="auth-page">
  <main class="auth-shell">
    <a class="brand brand-center" href="<?= e(url()) ?>"><span class="brand-mark"><?= icon('link') ?></span><strong><?= e(site_name()) ?></strong></a>
    <section class="auth-card">
      <div class="auth-heading"><span class="eyebrow">Bienvenido de vuelta</span><h1>Inicia sesión</h1><p>Accede con el celular registrado en tu cuenta.</p></div>
      <?php foreach ($messages as $message): ?><div class="alert alert-<?= e($message['type']) ?>"><?= e($message['message']) ?></div><?php endforeach; ?>
      <?php if ($error = form_error('general')): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form class="form-stack" action="<?= e(url('actions.php')) ?>" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="login">
        <div class="field"><label for="phone">Celular</label><div class="input-shell"><?= icon('phone') ?><input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" placeholder="999 999 999" value="<?= e(old('phone')) ?>" required></div></div>
        <div class="field"><label for="password">Contraseña</label><input id="password" name="password" type="password" autocomplete="current-password" maxlength="128" required></div>
        <button class="btn btn-primary btn-block btn-large" type="submit">Ingresar <?= icon('arrow') ?></button>
      </form>
      <div class="auth-links"><a href="<?= e(url('forgot-password.php')) ?>">¿Olvidaste tu contraseña?</a></div>
      <div class="auth-links"><span>¿Aún no tienes cuenta?</span><a href="<?= e(url('register.php')) ?>">Regístrate</a></div>
    </section>
  </main>
</body>
</html>
