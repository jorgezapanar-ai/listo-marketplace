# Listo Marketplace

Marketplace MVP para cPanel que conecta clientes con proveedores de servicios en Lima Metropolitana y Callao.

## Funcionalidades incluidas

- Instalador web para PHP y MySQL.
- Registro e inicio de sesión mediante celular y contraseña.
- Roles separados: cliente, proveedor y administrador.
- Publicación de solicitudes con categoría, distrito, fecha, prioridad y presupuesto.
- Matching por categorías y distritos configurados por cada proveedor.
- Propuestas con alcance, precio y disponibilidad.
- Selección de proveedor y contacto por WhatsApp después de aceptar.
- Estados de servicio: abierto, proveedor elegido, en curso, completado y cancelado.
- Reseñas vinculadas únicamente a servicios completados.
- Perfiles públicos de proveedores aprobados mediante revisión administrativa.
- Landing comercial para proveedores con plan de suscripción anunciado a S/20.
- Panel administrativo para usuarios, validaciones, solicitudes, reseñas y configuración.
- Protección CSRF, consultas preparadas, contraseñas con `password_hash`, sesiones seguras, rate limiting y auditoría.
- Diseño responsive y accesible sin dependencias externas.

## Requisitos

- PHP 8.1 o superior.
- MySQL 8.0+ o MariaDB 10.5+.
- Extensiones PHP: `pdo_mysql`, `mbstring`, `json`, `openssl`, `session`.
- Apache con `.htaccess` y `mod_rewrite` habilitado.
- Certificado SSL activo antes de captar usuarios reales.

## Inicio rápido

Consulta [DEPLOYMENT.md](DEPLOYMENT.md) para el despliegue completo en cPanel.

1. Sube el contenido de esta carpeta a `public_html`.
2. Crea una base de datos MySQL y un usuario con todos los privilegios.
3. Visita `https://tu-dominio.pe/install.php`.
4. Completa los datos de base de datos y la cuenta administradora.
5. Ingresa al panel, configura WhatsApp y revisa la lista previa al lanzamiento.

## Páginas comerciales y de indexación

- `para-proveedores.php`: landing para captar proveedores, con plan anunciado de S/20.
- `sitemap.php`: se sirve como `/sitemap.xml` mediante `.htaccess`.
- `robots.php`: se sirve como `/robots.txt` y genera la URL absoluta del sitemap.
- `health.php`: verificación de disponibilidad para monitoreo.

## Probar con el equipo

Este repositorio es la beta técnica del marketplace. Para probarlo:

1. Levanta un servidor con PHP 8.1+ y MySQL/MariaDB.
2. Copia `app/config.example.php` a `app/config.php` y completa los datos locales, o ejecuta `install.php` desde el navegador.
3. Abre el sitio y crea tres cuentas de prueba: cliente, proveedor y administrador.
4. Sigue este recorrido: solicitud → aprobación de proveedor → propuesta → aceptación → WhatsApp → cierre → reseña → moderación.
5. Revisa la landing comercial en `para-proveedores.php`.

No subas `app/config.php`, `storage/installed.lock`, logs ni respaldos al repositorio; `.gitignore` ya los excluye.

### Alcance de la prueba

- La suscripción de **S/20** se muestra en la landing, pero **no se cobra en línea** todavía.
- “Aprobado” significa solo revisión administrativa del perfil; no certifica identidad, antecedentes ni capacidad técnica.
- Los textos legales son borradores y requieren revisión antes de operar comercialmente.

## Límites de esta versión

- No incluye verificación automática OTP por SMS o WhatsApp; el administrador valida proveedores manualmente.
- No procesa pagos dentro de la plataforma; la activación y el cobro del plan de S/20 requieren coordinación externa durante el piloto.
- El contacto directo se habilita mediante WhatsApp después de aceptar una propuesta.
- Los documentos legales incluyen campos que deben completarse con los datos reales del operador.

## Estructura

- `app/`: configuración y lógica de dominio.
- `admin/`: panel administrativo.
- `assets/`: CSS, JavaScript e identidad visual.
- `database/`: esquema y datos iniciales.
- `templates/`: layouts compartidos.
- `storage/`: bloqueo de instalación y logs privados.
- `cron/`: mantenimiento programado.

No publiques `app/config.php`, respaldos SQL ni logs fuera del servidor seguro.
