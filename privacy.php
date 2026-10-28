<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$pageTitle = 'Política de privacidad';
$pageDescription = 'Tratamiento de datos personales en ' . site_name() . '.';
require __DIR__ . '/templates/header.php';
?>
<section class="section section-soft"><div class="container"><div class="panel"><div class="panel-header"><div><h1>Política de privacidad</h1><p>Borrador técnico pendiente de revisión legal</p></div></div><div class="panel-body">
  <div class="alert alert-warning"><strong>Revisión previa al lanzamiento:</strong> completa la identidad y domicilio del titular del banco de datos, su inscripción cuando corresponda y el canal para ejercer derechos ARCO.</div>
  <h2>1. Datos que se recopilan</h2><p>La plataforma puede tratar nombre, celular, correo opcional, rol, categorías, distritos, perfil profesional, solicitudes, propuestas, estados de servicio, reseñas, registros de acceso y datos técnicos necesarios para seguridad.</p>
  <h2>2. Finalidades</h2><p>Los datos se utilizan para crear y proteger cuentas, conectar solicitudes con proveedores, habilitar contacto tras una aceptación, moderar contenido, atender soporte, prevenir fraude, cumplir obligaciones y mejorar la operación.</p>
  <h2>3. Datos visibles</h2><p>Los perfiles aprobados pueden mostrar nombre, experiencia declarada, categorías, cobertura, disponibilidad y reseñas. El celular y las referencias privadas de ubicación permanecen ocultos hasta que una propuesta es aceptada, salvo acceso administrativo justificado.</p>
  <h2>4. Base y consentimiento</h2><p>El tratamiento se realiza para ejecutar las funcionalidades solicitadas, cumplir obligaciones legales y proteger intereses legítimos de seguridad. Cuando la ley lo requiera, se solicitará consentimiento específico.</p>
  <h2>5. Conservación y seguridad</h2><p>Los datos se conservan durante el tiempo necesario para operar la cuenta, resolver controversias y cumplir obligaciones. Se aplican contraseñas cifradas, sesiones protegidas, control de roles, registros de auditoría y medidas de minimización; ningún sistema es absolutamente invulnerable.</p>
  <h2>6. Encargados y transferencias</h2><p>El hosting, correo, mensajería u otros proveedores tecnológicos podrían tratar datos bajo instrucciones del responsable. Antes del lanzamiento deben identificarse los encargados y cualquier flujo transfronterizo aplicable.</p>
  <h2>7. Derechos ARCO</h2><p>El titular puede solicitar acceso, rectificación, cancelación u oposición conforme a la Ley N.º 29733 y su normativa. El operador debe agregar el correo, domicilio y procedimiento formal para estas solicitudes.</p>
  <h2>8. Menores de edad</h2><p>La plataforma está dirigida a personas mayores de edad con capacidad para contratar. No se deben crear cuentas de menores ni publicar sus datos sin base legal y salvaguardas adecuadas.</p>
  <h2>9. Contacto</h2><p><?php if ($email = setting('support_email', '')): ?>Consultas de privacidad: <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>.<?php else: ?>El administrador debe configurar un correo de privacidad antes del lanzamiento.<?php endif; ?></p>
</div></div></div></section>
<?php require __DIR__ . '/templates/footer.php'; ?>
