<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'Servicios confiables cerca de ti';
$pageDescription = 'Publica lo que necesitas y recibe propuestas de proveedores de servicios en Lima Metropolitana y Callao.';
$activeNav = '';
$categoryRows = categories();
$districtRows = districts();
$user = current_user();

$providerQuery = db()->query(
    "SELECT u.id, u.full_name, pp.headline, pp.bio, pp.years_experience,
            GROUP_CONCAT(DISTINCT c.name ORDER BY c.sort_order SEPARATOR '||') AS category_names,
            COALESCE(rv.rating_avg, 0) AS rating_avg, COALESCE(rv.review_count, 0) AS review_count
     FROM users u
     JOIN provider_profiles pp ON pp.user_id = u.id AND pp.verification_status = 'verified'
     JOIN provider_categories pc ON pc.provider_id = u.id
     JOIN categories c ON c.id = pc.category_id AND c.active = 1
     LEFT JOIN (
       SELECT provider_id, AVG(rating) AS rating_avg, COUNT(*) AS review_count
       FROM reviews WHERE status = 'published' GROUP BY provider_id
     ) rv ON rv.provider_id = u.id
     WHERE u.role = 'provider' AND u.status = 'active'
     GROUP BY u.id, u.full_name, pp.headline, pp.bio, pp.years_experience, rv.rating_avg, rv.review_count
     ORDER BY rv.review_count DESC, u.created_at ASC
     LIMIT 3"
);
$featuredProviders = $providerQuery->fetchAll();

require __DIR__ . '/templates/header.php';
?>
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <span class="eyebrow">Marketplace peruano de servicios</span>
      <h1>Lo necesitas. Alguien sabe <span class="highlight">hacerlo.</span></h1>
      <p>Conectamos personas que necesitan resolver una tarea con proveedores que buscan nuevas oportunidades. Publica, compara y elige con mayor claridad.</p>
      <div class="hero-actions">
        <a class="btn btn-primary btn-large" href="#solicitar">Encontrar un proveedor <?= icon('arrow') ?></a>
        <a class="btn btn-secondary btn-large" href="<?= e(url('para-proveedores.php')) ?>"><?= icon('user') ?> Conseguir clientes</a>
      </div>
      <div class="proof-row" aria-label="Beneficios de la plataforma">
        <span class="proof-item"><span class="proof-dot"><?= icon('shield') ?></span>Perfiles revisados por el equipo</span>
        <span class="proof-item"><span class="proof-dot"><?= icon('briefcase') ?></span>Propuestas comparables</span>
        <span class="proof-item"><span class="proof-dot"><?= icon('chat') ?></span>Contacto directo al elegir</span>
      </div>
    </div>

    <div id="solicitar">
      <?php if (!$user || $user['role'] === 'client'): ?>
        <form class="request-card form-stack" action="<?= e(url('actions.php')) ?>" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="request_start">
          <div class="card-heading">
            <div><h2>¿Qué necesitas resolver?</h2><p>Publica tu necesidad y recibe propuestas.</p></div>
            <span class="step-pill">Paso 1 de 3</span>
          </div>
          <?php if (($generalError = form_error('general')) !== null): ?><div class="alert alert-danger"><?= e($generalError) ?></div><?php endif; ?>
          <div class="form-grid">
            <div class="field field-full">
              <label for="category_id">Tipo de servicio</label>
              <div class="input-shell"><?= icon('plus') ?><select id="category_id" name="category_id" required aria-invalid="<?= form_error('category_id') ? 'true' : 'false' ?>"><option value="">Selecciona una categoría</option><?php foreach ($categoryRows as $category): ?><option value="<?= (int) $category['id'] ?>"<?= selected(old('category_id'), $category['id']) ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div>
              <?php if ($error = form_error('category_id')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?>
            </div>
            <div class="field field-full">
              <label for="district_id">Distrito</label>
              <div class="input-shell"><?= icon('map') ?><select id="district_id" name="district_id" required aria-invalid="<?= form_error('district_id') ? 'true' : 'false' ?>"><option value="">Selecciona tu distrito</option><?php foreach ($districtRows as $district): ?><option value="<?= (int) $district['id'] ?>"<?= selected(old('district_id'), $district['id']) ?>><?= e($district['name']) ?></option><?php endforeach; ?></select></div>
              <?php if ($error = form_error('district_id')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?>
            </div>
            <div class="field">
              <label for="preferred_date">Fecha ideal</label>
              <div class="input-shell"><?= icon('calendar') ?><input id="preferred_date" name="preferred_date" type="date" value="<?= e(old('preferred_date')) ?>" data-min-today data-default-tomorrow="true"></div>
            </div>
            <div class="field">
              <label for="priority">Prioridad</label>
              <select id="priority" name="priority"><option value="flexible"<?= selected(old('priority', 'flexible'), 'flexible') ?>>Fecha flexible</option><option value="soon"<?= selected(old('priority'), 'soon') ?>>Lo antes posible</option><option value="urgent"<?= selected(old('priority'), 'urgent') ?>>Es urgente</option></select>
            </div>
            <div class="field field-full">
              <label for="description">Describe brevemente el trabajo</label>
              <textarea id="description" name="description" minlength="20" maxlength="1000" placeholder="Ej.: Necesito revisar dos tomacorrientes que dejaron de funcionar en la sala." required aria-invalid="<?= form_error('description') ? 'true' : 'false' ?>"><?= e(old('description')) ?></textarea>
              <?php if ($error = form_error('description')): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?>
            </div>
          </div>
          <div class="request-submit">
            <div><span>Publicar una solicitud</span><strong>Sin costo</strong></div>
            <button class="btn btn-primary" type="submit">Continuar <?= icon('arrow') ?></button>
          </div>
          <p class="form-fine">Tus datos de contacto solo se comparten con el proveedor que elijas.</p>
        </form>
      <?php else: ?>
        <div class="request-card center-card">
          <span class="success-mark"><?= icon('briefcase') ?></span>
          <h2>Encuentra oportunidades compatibles</h2>
          <p>Revisa solicitudes según tus servicios y distritos configurados.</p>
          <a class="btn btn-primary btn-block" href="<?= e(url('opportunities.php')) ?>">Ver oportunidades</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<aside class="trust-strip" aria-label="Principios de confianza">
  <div class="container trust-grid">
    <div class="trust-intro">Una necesidad, distintas opciones<small>Diseñado para reducir incertidumbre al contratar</small></div>
    <div class="trust-item"><?= icon('shield') ?>Revisión administrativa</div>
    <div class="trust-item"><?= icon('chat') ?>Solicitud centralizada</div>
    <div class="trust-item"><?= icon('briefcase') ?>Propuestas comparables</div>
  </div>
</aside>

<section class="section" id="servicios">
  <div class="container">
    <div class="section-head"><div><p class="kicker">Explora por categoría</p><h2>Ayuda para cada pendiente</h2></div><p>Elige una categoría para publicar una necesidad o conocer proveedores disponibles en Lima Metropolitana y Callao.</p></div>
    <div class="category-grid">
      <?php foreach (array_slice($categoryRows, 0, 8) as $category): ?>
        <a class="category-card" href="<?= e(url('request-new.php?category=' . (int) $category['id'])) ?>">
          <span class="category-icon"><?= icon($category['icon']) ?></span>
          <h3><?= e($category['name']) ?></h3><p><?= e($category['description']) ?></p><span class="category-arrow"><?= icon('arrow') ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-soft" id="como-funciona">
  <div class="container">
    <div class="section-head"><div><p class="kicker">Dos lados, una conexión</p><h2>De una necesidad a una solución</h2></div><p>La plataforma organiza el proceso sin reemplazar tu decisión final.</p></div>
    <div class="steps-grid">
      <article class="step-card"><span class="step-number">1</span><h3>Describe lo que necesitas</h3><p>Elige categoría, distrito, fecha y explica el resultado que esperas.</p></article>
      <article class="step-card"><span class="step-number">2</span><h3>Recibe propuestas</h3><p>Proveedores compatibles pueden indicar disponibilidad, alcance y precio.</p></article>
      <article class="step-card"><span class="step-number">3</span><h3>Compara perfiles</h3><p>Revisa experiencia, validación, reseñas y detalles antes de elegir.</p></article>
      <article class="step-card"><span class="step-number">4</span><h3>Coordina y califica</h3><p>Contacta por WhatsApp al proveedor elegido y evalúa al completar.</p></article>
    </div>
  </div>
</section>

<section class="section" id="confianza">
  <div class="container split">
    <div class="visual-panel" aria-hidden="true">
      <div class="visual-network"><span class="network-line line-a"></span><span class="network-line line-b"></span><span class="network-line line-c"></span><span class="network-node node-a"><?= icon('user') ?></span><span class="network-node node-b"><?= icon('tools') ?></span><span class="network-node node-c"><?= icon('chat') ?></span><span class="network-node node-d"><?= icon('shield') ?></span></div>
      <div class="visual-badge"><span class="badge-icon"><?= icon('shield') ?></span><div><strong>Más contexto antes de elegir</strong><span>Perfil, propuesta y reputación en un mismo lugar.</span></div></div>
    </div>
    <div class="split-copy">
      <p class="kicker">Confianza desde el diseño</p><h2>Contrata sin hacerlo a ciegas</h2>
      <p>Cada proveedor muestra su estado de validación, categorías, cobertura y experiencia declarada. Los datos personales se mantienen protegidos hasta que el cliente acepta una propuesta.</p>
      <ul class="benefit-list">
        <li><span class="benefit-icon"><?= icon('shield') ?></span><div><h3>Proveedores moderados</h3><p>Los perfiles deben ser aprobados antes de enviar propuestas.</p></div></li>
        <li><span class="benefit-icon"><?= icon('briefcase') ?></span><div><h3>Alcance y precio por escrito</h3><p>Cada propuesta conserva los términos compartidos por el proveedor.</p></div></li>
        <li><span class="benefit-icon"><?= icon('star') ?></span><div><h3>Reseñas de trabajos completados</h3><p>Solo un cliente con servicio finalizado puede dejar una evaluación.</p></div></li>
      </ul>
    </div>
  </div>
</section>

<?php if ($featuredProviders !== []): ?>
<section class="section section-cream">
  <div class="container">
    <div class="section-head"><div><p class="kicker">Perfiles públicos</p><h2>Conoce proveedores</h2></div><p>Solo aparecen perfiles aprobados por la administración de la plataforma.</p></div>
    <div class="provider-grid">
      <?php foreach ($featuredProviders as $provider): $providerCategories = explode('||', (string) $provider['category_names']); ?>
        <article class="provider-card">
          <div class="provider-card-top"><span class="avatar"><?= e(user_initials($provider['full_name'])) ?></span><div class="provider-card-name"><strong><?= e($provider['full_name']) ?></strong><span><?= e($provider['headline'] ?: 'Proveedor de servicios') ?></span></div></div>
          <div class="provider-card-body"><div class="chip-row"><?php foreach (array_slice($providerCategories, 0, 3) as $name): ?><span class="chip"><?= e($name) ?></span><?php endforeach; ?></div><p><?= e($provider['bio'] ?: 'Perfil profesional aprobado por el equipo.') ?></p><div class="card-footer"><span class="rating"><?= icon('star') ?><?= (int) $provider['review_count'] > 0 ? e(number_format((float) $provider['rating_avg'], 1)) . ' · ' . (int) $provider['review_count'] . ' reseñas' : 'Sin reseñas aún' ?></span><a class="btn btn-secondary btn-small" href="<?= e(url('provider.php?id=' . (int) $provider['id'])) ?>">Ver perfil</a></div></div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container">
    <div class="cta-panel">
       <div><p class="kicker">Para quienes saben hacer</p><h2>Convierte tu experiencia en oportunidades</h2><p>Configura tus servicios, zonas y disponibilidad. Revisa cada necesidad antes de decidir si deseas enviar una propuesta.</p><a class="btn btn-primary btn-large" href="<?= e(url('para-proveedores.php')) ?>">Conocer el plan de S/20 <?= icon('arrow') ?></a></div>
       <div class="cta-benefits"><div class="cta-benefit"><?= icon('settings') ?><strong>Elige categorías y distritos</strong></div><div class="cta-benefit"><?= icon('search') ?><strong>Encuentra oportunidades compatibles</strong></div><div class="cta-benefit"><?= icon('star') ?><strong>Construye reputación con reseñas</strong></div></div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/templates/footer.php'; ?>
