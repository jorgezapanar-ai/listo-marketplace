<?php
declare(strict_types=1);

$user = $user ?? require_auth();
$pageTitle = $pageTitle ?? 'Panel';
$pageSubtitle = $pageSubtitle ?? '';
$activeDashboard = $activeDashboard ?? 'dashboard';
$roleLabel = ['client' => 'Cliente', 'provider' => 'Proveedor', 'admin' => 'Administrador'][$user['role']] ?? 'Usuario';

$navItems = match ($user['role']) {
    'client' => [
        ['dashboard', 'dashboard.php', 'home', 'Resumen'],
        ['new-request', 'request-new.php', 'plus', 'Nueva solicitud'],
        ['requests', 'requests.php', 'briefcase', 'Mis solicitudes'],
        ['providers', 'providers.php', 'search', 'Proveedores'],
        ['account', 'account.php', 'settings', 'Mi cuenta'],
    ],
    'provider' => [
        ['dashboard', 'dashboard.php', 'home', 'Resumen'],
        ['opportunities', 'opportunities.php', 'search', 'Oportunidades'],
        ['proposals', 'proposals.php', 'briefcase', 'Mis propuestas'],
        ['profile', 'provider-edit.php', 'user', 'Mi perfil profesional'],
        ['account', 'account.php', 'settings', 'Mi cuenta'],
    ],
    default => [
        ['dashboard', 'admin/index.php', 'home', 'Resumen'],
        ['users', 'admin/users.php', 'user', 'Usuarios'],
        ['requests', 'admin/requests.php', 'briefcase', 'Solicitudes'],
        ['reviews', 'admin/reviews.php', 'star', 'Reseñas'],
        ['settings', 'admin/settings.php', 'settings', 'Configuración'],
        ['account', 'account.php', 'shield', 'Mi cuenta'],
    ],
};
?>
<!doctype html>
<html lang="es-PE">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= e(trim($pageTitle . ' · ' . ($pageSubtitle !== '' ? $pageSubtitle : site_name()))) ?>">
  <meta name="robots" content="noindex,nofollow">
  <meta name="theme-color" content="#0d4e45">
  <title><?= e($pageTitle) ?> · <?= e(site_name()) ?></title>
  <link rel="icon" href="<?= e(asset('favicon.svg')) ?>" type="image/svg+xml">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body class="dashboard-body">
  <a class="skip-link" href="#dashboard-content">Ir al contenido</a>
  <div class="dashboard-shell">
    <aside class="sidebar" id="dashboard-sidebar" aria-label="Navegación del panel">
      <a class="brand" href="<?= e(url('dashboard.php')) ?>"><span class="brand-mark"><?= icon('link') ?></span><strong><?= e(site_name()) ?></strong></a>
      <span class="sidebar-label">Menú</span>
      <nav class="sidebar-nav">
        <?php foreach ($navItems as [$key, $href, $iconName, $label]): ?>
          <a class="sidebar-link<?= $activeDashboard === $key ? ' active' : '' ?>" href="<?= e(url($href)) ?>"><?= icon($iconName) ?><span><?= e($label) ?></span></a>
        <?php endforeach; ?>
      </nav>
      <div class="sidebar-bottom">
        <div class="sidebar-user">
          <span class="avatar avatar-small"><?= e(user_initials($user['full_name'])) ?></span>
          <div><strong><?= e($user['full_name']) ?></strong><span><?= e($roleLabel) ?></span></div>
        </div>
        <form action="<?= e(url('actions.php')) ?>" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="logout">
          <button class="sidebar-link btn-block" type="submit"><?= icon('logout') ?><span>Cerrar sesión</span></button>
        </form>
      </div>
    </aside>
    <div class="dashboard-main">
      <header class="dashboard-topbar">
        <div class="proposal-person">
          <button class="dashboard-menu" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="dashboard-sidebar"><?= icon('menu') ?></button>
          <div><h1><?= e($pageTitle) ?></h1><?php if ($pageSubtitle !== ''): ?><p><?= e($pageSubtitle) ?></p><?php endif; ?></div>
        </div>
        <a class="btn btn-secondary btn-small" href="<?= e(url()) ?>">Ver sitio público</a>
      </header>
      <?php $flashMessages = flashes(); if ($flashMessages !== []): ?>
        <div class="flash-stack" aria-live="polite">
          <?php foreach ($flashMessages as $message): ?>
            <div class="flash alert-<?= e($message['type']) ?>" data-dismissible><span><?= e($message['message']) ?></span><button type="button" data-dismiss aria-label="Cerrar mensaje">×</button></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <main class="dashboard-content" id="dashboard-content">
