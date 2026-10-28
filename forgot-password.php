<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$supportUrl = support_whatsapp_url('Hola, necesito recuperar el acceso a mi cuenta de ' . site_name() . '.');
$pageTitle = 'Recuperar acceso';
$pageDescription = 'Ayuda para recuperar el acceso a tu cuenta.';
$noIndex = true;
require __DIR__ . '/templates/header.php';
?>
<section class="section section-soft">
  <div class="container"><div class="auth-shell"><div class="auth-card center-card"><span class="success-mark"><?= icon('phone') ?></span><h1>Recupera tu acceso</h1><p>Como el ingreso usa celular, la recuperación se valida manualmente para evitar que otra persona tome control de tu cuenta.</p><?php if ($supportUrl): ?><a class="btn btn-primary btn-block" href="<?= e($supportUrl) ?>" target="_blank" rel="noopener noreferrer">Solicitar ayuda por WhatsApp</a><?php else: ?><div class="alert alert-warning">El administrador aún no configuró el número de soporte. Escríbele por el canal con el que conociste la plataforma.</div><?php endif; ?><a class="btn btn-ghost btn-block" href="<?= e(url('login.php')) ?>">Volver a iniciar sesión</a></div></div></div>
</section>
<?php require __DIR__ . '/templates/footer.php'; ?>
