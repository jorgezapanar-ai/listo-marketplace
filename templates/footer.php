  </main>
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-about">
          <a class="brand" href="<?= e(url()) ?>"><span class="brand-mark"><?= icon('link') ?></span><strong><?= e(site_name()) ?></strong></a>
          <p>Marketplace peruano que conecta personas que necesitan resolver algo con proveedores que buscan nuevas oportunidades.</p>
        </div>
        <div class="footer-col">
          <h2>Para contratar</h2>
          <a href="<?= e(url('index.php#como-funciona')) ?>">Cómo funciona</a>
          <a href="<?= e(url('index.php#servicios')) ?>">Explorar servicios</a>
          <a href="<?= e(url('request-new.php')) ?>">Publicar necesidad</a>
        </div>
        <div class="footer-col">
          <h2>Para proveedores</h2>
          <a href="<?= e(url('para-proveedores.php')) ?>">Cómo conseguir clientes</a>
          <a href="<?= e(url('register.php?role=provider')) ?>">Crear un perfil</a>
          <a href="<?= e(url('opportunities.php')) ?>">Ver oportunidades</a>
          <a href="<?= e(url('providers.php')) ?>">Directorio público</a>
        </div>
        <div class="footer-col">
          <h2>Ayuda y legal</h2>
          <?php if ($supportUrl = support_whatsapp_url('Hola, necesito ayuda con ' . site_name())): ?><a href="<?= e($supportUrl) ?>" target="_blank" rel="noopener noreferrer">Soporte por WhatsApp</a><?php endif; ?>
          <a href="<?= e(url('terms.php')) ?>">Términos y condiciones</a>
          <a href="<?= e(url('privacy.php')) ?>">Política de privacidad</a>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© <?= date('Y') ?> <?= e(site_name()) ?>. Todos los derechos reservados.</span>
        <span>Operación inicial: <?= e((string) setting('marketplace_city', 'Lima Metropolitana y Callao')) ?></span>
      </div>
    </div>
  </footer>
</body>
</html>
