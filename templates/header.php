<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Servicios confiables cerca de ti';
$pageDescription = $pageDescription ?? 'Conecta con proveedores de servicios en Lima Metropolitana y Callao.';
$activeNav = $activeNav ?? '';
$noIndex = $noIndex ?? false;
$user = current_user();
$fullTitle = $pageTitle . ' · ' . site_name();
?>
<!doctype html>
<html lang="es-PE">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= e($pageDescription) ?>">
  <meta name="theme-color" content="#0d4e45">
  <?php if ($noIndex): ?><meta name="robots" content="noindex,nofollow"><?php endif; ?>
  <meta property="og:type" content="website">
  <meta property="og:locale" content="es_PE">
  <meta property="og:title" content="<?= e($fullTitle) ?>">
  <meta property="og:description" content="<?= e($pageDescription) ?>">
  <meta property="og:url" content="<?= e(current_url()) ?>">
  <link rel="canonical" href="<?= e(current_url()) ?>">
  <title><?= e($fullTitle) ?></title>
  <link rel="icon" href="<?= e(asset('favicon.svg')) ?>" type="image/svg+xml">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body>
  <a class="skip-link" href="#main-content">Ir al contenido principal</a>
  <header class="site-header">
    <div class="container nav-wrap">
      <a class="brand" href="<?= e(url()) ?>" aria-label="<?= e(site_name()) ?>, ir al inicio">
        <span class="brand-mark"><?= icon('link') ?></span><strong><?= e(site_name()) ?></strong>
      </a>
      <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="main-nav"><?= icon('menu') ?></button>
      <nav class="main-nav" id="main-nav" aria-label="Navegación principal">
        <div class="nav-links">
          <a class="nav-link<?= $activeNav === 'services' ? ' active' : '' ?>" href="<?= e(url('index.php#servicios')) ?>">Servicios</a>
          <a class="nav-link<?= $activeNav === 'how' ? ' active' : '' ?>" href="<?= e(url('index.php#como-funciona')) ?>">Cómo funciona</a>
          <a class="nav-link<?= $activeNav === 'providers' ? ' active' : '' ?>" href="<?= e(url('providers.php')) ?>">Buscar proveedores</a>
          <a class="nav-link<?= $activeNav === 'provider-growth' ? ' active' : '' ?>" href="<?= e(url('para-proveedores.php')) ?>">Para proveedores</a>
        </div>
        <div class="nav-actions">
          <?php if ($user): ?>
            <a class="btn btn-ghost" href="<?= e(url('dashboard.php')) ?>">Hola, <?= e(first_name($user['full_name'])) ?></a>
            <a class="btn btn-primary btn-small" href="<?= e(url('dashboard.php')) ?>">Ir a mi panel</a>
          <?php else: ?>
            <a class="btn btn-ghost" href="<?= e(url('login.php')) ?>">Ingresar</a>
            <a class="btn btn-secondary btn-small" href="<?= e(url('para-proveedores.php')) ?>">Ofrecer servicios</a>
            <a class="btn btn-primary btn-small" href="<?= e(url('request-new.php')) ?>">Publicar necesidad</a>
          <?php endif; ?>
        </div>
      </nav>
    </div>
  </header>
  <?php $flashMessages = flashes(); if ($flashMessages !== []): ?>
    <div class="flash-stack" aria-live="polite">
      <?php foreach ($flashMessages as $message): ?>
        <div class="flash alert-<?= e($message['type']) ?>" data-dismissible>
          <span><?= e($message['message']) ?></span>
          <button type="button" data-dismiss aria-label="Cerrar mensaje">×</button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <main id="main-content">
