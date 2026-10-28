<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$pageTitle = 'Términos y condiciones';
$pageDescription = 'Condiciones de uso del marketplace ' . site_name() . '.';
$noIndex = false;
require __DIR__ . '/templates/header.php';
?>
<section class="section section-soft"><div class="container"><div class="panel"><div class="panel-header"><div><h1>Términos y condiciones</h1><p>Borrador técnico pendiente de revisión legal</p></div></div><div class="panel-body">
  <div class="alert alert-warning"><strong>Revisión previa al lanzamiento:</strong> el titular de la plataforma debe completar su razón social, RUC, domicilio y canal formal de reclamaciones en este documento.</div>
  <h2>1. Naturaleza de la plataforma</h2><p><?= e(site_name()) ?> es una plataforma tecnológica que facilita el contacto entre personas que solicitan servicios y proveedores independientes que desean presentar propuestas. Salvo indicación expresa, la plataforma no ejecuta los servicios publicados ni actúa como empleador, representante o socio de los usuarios.</p>
  <h2>2. Registro y seguridad</h2><p>Cada usuario debe proporcionar información verdadera, mantener la confidencialidad de su contraseña y comunicar cualquier uso no autorizado. Las cuentas pueden ser suspendidas ante indicios de fraude, suplantación, acoso, incumplimiento o riesgo para otros usuarios.</p>
  <h2>3. Solicitudes y propuestas</h2><p>El cliente es responsable de describir la necesidad sin publicar datos sensibles. El proveedor es responsable de indicar con claridad alcance, precio, disponibilidad, materiales y condiciones. La aceptación de una propuesta habilita el intercambio de contacto para coordinar el servicio.</p>
  <h2>4. Relación entre usuarios</h2><p>El acuerdo de prestación se celebra directamente entre el cliente y el proveedor elegido. Ambas partes deben confirmar identidad, alcance, forma de pago, seguridad, permisos técnicos y cualquier comprobante aplicable antes de iniciar.</p>
  <h2>5. Suscripción para proveedores</h2><p>El plan para proveedores se anuncia a S/20 por suscripción y habilita las funciones descritas en la página comercial. No garantiza un número de solicitudes, contrataciones ni ingresos. Antes de cobrar, el operador debe informar por escrito la periodicidad, vigencia, renovación, medios de pago, comprobantes, cancelación y cualquier condición adicional aplicable.</p>
  <h2>6. Pagos</h2><p>Mientras la plataforma no muestre un módulo de pago integrado, tanto la activación de la suscripción como los pagos por cada servicio se coordinan por los canales oficiales correspondientes. <?= e(site_name()) ?> no solicita claves, códigos de seguridad ni transferencias a cuentas no publicadas por sus canales oficiales.</p>
  <h2>7. Conductas prohibidas</h2><p>No está permitido publicar actividades ilegales o peligrosas, datos personales de terceros, contenido discriminatorio, engaños, spam, ofertas falsas, credenciales, documentos sensibles o solicitudes que infrinjan derechos de otras personas.</p>
  <h2>8. Aprobación de perfiles y reseñas</h2><p>El estado “aprobado” indica únicamente que un administrador revisó la información declarada en el perfil. No acredita identidad, antecedentes, licencias ni capacidad técnica salvo que la plataforma describa expresamente un control adicional. Las reseñas deben corresponder a servicios completados y pueden moderarse si vulneran estas reglas.</p>
  <h2>9. Responsabilidad</h2><p>Cada usuario debe evaluar las condiciones y tomar precauciones razonables. La responsabilidad de la plataforma se limita a la operación tecnológica en la medida permitida por la legislación peruana, sin excluir derechos irrenunciables del consumidor.</p>
  <h2>10. Contacto y controversias</h2><p>Las consultas se reciben por los canales de soporte publicados en el sitio. El operador deberá añadir aquí su identificación legal y el enlace al Libro de Reclamaciones antes de iniciar operaciones comerciales.</p>
</div></div></div></section>
<?php require __DIR__ . '/templates/footer.php'; ?>
