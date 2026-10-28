<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'Consigue más clientes como proveedor';
$pageDescription = 'Presenta tus servicios, encuentra oportunidades compatibles y envía propuestas con el plan para proveedores de S/20.';
$activeNav = 'provider-growth';
$viewer = current_user();
$coverage = (string) setting('marketplace_city', 'Lima Metropolitana y Callao');

if ($viewer && $viewer['role'] === 'provider') {
    $providerCtaUrl = url('opportunities.php');
    $providerCtaLabel = 'Ver mis oportunidades';
} elseif ($viewer) {
    $providerCtaUrl = url('dashboard.php');
    $providerCtaLabel = 'Ir a mi panel';
} else {
    $providerCtaUrl = url('register.php?role=provider');
    $providerCtaLabel = 'Crear mi perfil de proveedor';
}

require __DIR__ . '/templates/header.php';
?>
<section class="provider-hero">
  <div class="container provider-hero-grid">
    <div class="provider-hero-copy">
      <span class="eyebrow">Para proveedores de servicios</span>
      <h1>Convierte tu experiencia en <span class="highlight">nuevas oportunidades.</span></h1>
      <p>Abre un canal adicional para encontrar personas que ya publicaron lo que necesitan en <?= e($coverage) ?>. Tú eliges a qué solicitudes responder y qué propuesta enviar.</p>
      <div class="hero-actions">
        <a class="btn btn-primary btn-large" href="<?= e($providerCtaUrl) ?>"><?= e($providerCtaLabel) ?> <?= icon('arrow') ?></a>
        <a class="btn btn-secondary btn-large" href="#plan-proveedor">Ver el plan de S/20</a>
      </div>
      <div class="provider-proof" aria-label="Ventajas para proveedores">
        <span><?= icon('check') ?> Elige tus servicios y distritos</span>
        <span><?= icon('check') ?> Decide antes de proponer</span>
        <span><?= icon('check') ?> Contacto al ser elegido</span>
      </div>
    </div>

    <aside class="provider-plan-preview" aria-label="Resumen del plan para proveedores">
      <div class="provider-plan-top">
        <span class="plan-tag">Plan proveedor</span>
        <span class="status status-success">Suscripción</span>
      </div>
      <div class="provider-price"><span>S/</span><strong>20</strong></div>
      <p>Acceso al plan para buscar oportunidades y presentar tus servicios.</p>
      <div class="provider-route" aria-label="Ruta para conseguir clientes">
        <div><span><?= icon('user') ?></span><strong>Tu perfil</strong><small>Muestra lo que sabes hacer</small></div>
        <span class="provider-route-arrow" aria-hidden="true"><?= icon('arrow') ?></span>
        <div><span><?= icon('briefcase') ?></span><strong>Oportunidades</strong><small>Filtradas por tu cobertura</small></div>
        <span class="provider-route-arrow" aria-hidden="true"><?= icon('arrow') ?></span>
        <div><span><?= icon('chat') ?></span><strong>Propuestas</strong><small>Tú defines alcance y precio</small></div>
      </div>
      <p class="plan-note">La suscripción abre acceso a las funciones del plan; no garantiza una cantidad de solicitudes, contrataciones ni ingresos.</p>
    </aside>
  </div>
</section>

<aside class="provider-trust-bar" aria-label="Principios del plan">
  <div class="container">
    <span><?= icon('settings') ?><strong>Tú controlas</strong> servicios y zonas</span>
    <span><?= icon('briefcase') ?><strong>Tú decides</strong> dónde proponer</span>
    <span><?= icon('phone') ?><strong>Tú coordinas</strong> con el cliente elegido</span>
  </div>
</aside>

<section class="section" id="beneficios-proveedor">
  <div class="container">
    <div class="section-head"><div><p class="kicker">Un canal para hacer crecer tu agenda</p><h2>Menos tiempo buscando. Más claridad para proponer.</h2></div><p>Organiza en un solo lugar tu perfil, las necesidades compatibles, tus propuestas y la reputación que construyes.</p></div>
    <div class="provider-benefit-grid">
      <article class="provider-benefit-card"><span class="benefit-number">01</span><span class="benefit-icon-large"><?= icon('search') ?></span><h3>Encuentra oportunidades compatibles</h3><p>Revisa solicitudes relacionadas con las categorías y distritos que configuraste en tu perfil.</p></article>
      <article class="provider-benefit-card"><span class="benefit-number">02</span><span class="benefit-icon-large"><?= icon('chat') ?></span><h3>Presenta una propuesta completa</h3><p>Explica alcance, precio y disponibilidad para que el cliente pueda comparar con mayor claridad.</p></article>
      <article class="provider-benefit-card"><span class="benefit-number">03</span><span class="benefit-icon-large"><?= icon('star') ?></span><h3>Construye reputación</h3><p>Las reseñas vinculadas a servicios completados ayudan a mostrar tu trayectoria dentro de la plataforma.</p></article>
    </div>
  </div>
</section>

<section class="section section-soft" id="como-funciona-proveedor">
  <div class="container">
    <div class="section-head"><div><p class="kicker">Cómo funciona</p><h2>De tu registro a una nueva conversación</h2></div><p>Un proceso simple, con control administrativo antes de habilitar propuestas.</p></div>
    <ol class="provider-steps">
      <li><span>1</span><div><h3>Crea tu cuenta</h3><p>Regístrate con tu celular y completa tus datos básicos.</p></div></li>
      <li><span>2</span><div><h3>Presenta tu experiencia</h3><p>Indica servicios, zonas, disponibilidad y una descripción clara de tu trabajo.</p></div></li>
      <li><span>3</span><div><h3>Espera la revisión</h3><p>Un administrador revisa el perfil antes de permitir el envío de propuestas.</p></div></li>
      <li><span>4</span><div><h3>Elige oportunidades</h3><p>Envía propuestas y, si un cliente te selecciona, coordinen por WhatsApp.</p></div></li>
    </ol>
  </div>
</section>

<section class="section provider-pricing-section" id="plan-proveedor">
  <div class="container provider-pricing-grid">
    <div class="provider-pricing-copy">
      <p class="kicker">Una inversión simple</p>
      <h2>Un plan para mostrar tu trabajo y encontrar oportunidades</h2>
      <p>La plataforma te ayuda a descubrir solicitudes y presentar propuestas. La decisión de contratar y las condiciones finales del servicio siempre se coordinan entre tú y el cliente.</p>
      <div class="provider-not-guarantee"><?= icon('shield') ?><div><strong>Transparencia primero</strong><span>Listo facilita el contacto, pero no promete clientes, ventas ni ingresos determinados.</span></div></div>
    </div>
    <article class="provider-pricing-card">
      <span class="plan-tag">Suscripción para proveedores</span>
      <div class="provider-price provider-price-dark"><span>S/</span><strong>20</strong></div>
      <p class="provider-price-caption">Precio de la suscripción</p>
      <ul class="provider-plan-list">
        <li><?= icon('check') ?><span>Perfil público después de la aprobación</span></li>
        <li><?= icon('check') ?><span>Oportunidades según servicios y distritos</span></li>
        <li><?= icon('check') ?><span>Envío y seguimiento de propuestas</span></li>
        <li><?= icon('check') ?><span>Contacto por WhatsApp cuando te eligen</span></li>
        <li><?= icon('check') ?><span>Reseñas de servicios completados</span></li>
      </ul>
      <a class="btn btn-primary btn-large btn-block" href="<?= e($providerCtaUrl) ?>"><?= e($providerCtaLabel) ?> <?= icon('arrow') ?></a>
      <p class="provider-billing-note">Este MVP todavía no cobra dentro del sitio. La periodicidad, vigencia, renovación y forma de pago deben confirmarse por el canal oficial antes de activar la suscripción.</p>
    </article>
  </div>
</section>

<section class="section section-cream">
  <div class="container provider-fit-grid">
    <div><p class="kicker">¿Es para ti?</p><h2>Diseñado para profesionales que quieren un canal adicional</h2></div>
    <ul class="provider-fit-list">
      <li><?= icon('check') ?><span><strong>Atiendes en Lima o Callao</strong> y puedes definir con precisión tus zonas.</span></li>
      <li><?= icon('check') ?><span><strong>Respondes con claridad</strong> sobre alcance, materiales, precio y disponibilidad.</span></li>
      <li><?= icon('check') ?><span><strong>Cumples lo acordado</strong> y buscas construir una reputación con reseñas.</span></li>
      <li><?= icon('check') ?><span><strong>Trabajas de forma independiente</strong> y coordinas directamente con cada cliente.</span></li>
    </ul>
  </div>
</section>

<section class="section" id="preguntas-proveedores">
  <div class="container provider-faq-layout">
    <div><p class="kicker">Preguntas frecuentes</p><h2>Lo que debes saber antes de empezar</h2><p>Queremos que entiendas cómo funciona el plan antes de registrarte.</p></div>
    <div class="provider-faq-list">
      <details><summary>¿Pagar S/20 garantiza que conseguiré clientes?</summary><p>No. La suscripción permite usar las funciones del plan y acceder a oportunidades disponibles, pero la cantidad de solicitudes y la decisión de contratación dependen de la demanda y de cada cliente.</p></details>
      <details><summary>¿Cuándo puedo enviar propuestas?</summary><p>Después de completar tu perfil y recibir la aprobación administrativa. Esa aprobación es una revisión del perfil; no es una certificación de identidad, antecedentes ni capacidad técnica.</p></details>
      <details><summary>¿Cuándo veo el contacto del cliente?</summary><p>Cuando el cliente elige tu propuesta. En ese momento se habilita WhatsApp para coordinar dirección, horario, alcance y condiciones finales.</p></details>
      <details><summary>¿Listo cobra el servicio al cliente?</summary><p>No en esta versión. El pago del trabajo se coordina directamente entre el proveedor y el cliente. El sitio tampoco procesa todavía el cobro de la suscripción.</p></details>
      <details><summary>¿Puedo responder a cualquier solicitud?</summary><p>Verás oportunidades compatibles con las categorías y distritos de tu perfil. Tú decides cuáles evaluar y no estás obligado a enviar una propuesta.</p></details>
    </div>
  </div>
</section>

<section class="section provider-final-section">
  <div class="container">
    <div class="provider-final-cta">
      <div><p class="kicker">Tu próximo cliente puede estar buscando ayuda</p><h2>Haz visible lo que sabes hacer.</h2><p>Crea tu perfil, configura tu cobertura y prepárate para responder a nuevas oportunidades.</p></div>
      <div><a class="btn btn-primary btn-large" href="<?= e($providerCtaUrl) ?>"><?= e($providerCtaLabel) ?> <?= icon('arrow') ?></a><span>Plan para proveedores: S/20</span></div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/templates/footer.php'; ?>
