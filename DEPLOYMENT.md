# Despliegue en cPanel / Apache

## 1. Verificar el hosting

En **MultiPHP Manager**, selecciona PHP 8.1 o superior. En **Select PHP Version**, confirma estas extensiones:

- `pdo_mysql`
- `mbstring`
- `json`
- `openssl`
- `session`

Activa el certificado SSL desde **SSL/TLS Status** y fuerza HTTPS antes de registrar usuarios reales.

## 2. Crear MySQL

Desde **MySQL Databases**:

1. Crea una base de datos, por ejemplo `cuenta_listo`.
2. Crea un usuario MySQL con contraseña aleatoria y única.
3. Asocia el usuario a la base con **ALL PRIVILEGES**.
4. Conserva host, puerto, nombre completo de base, usuario y contraseña para el instalador.

En cPanel, el nombre suele incluir el prefijo de la cuenta. Debe copiarse completo.

## 3. Subir archivos

1. Abre **File Manager**.
2. Entra en `public_html`.
3. Sube el ZIP de distribución.
4. Extrae su contenido directamente en `public_html`; `index.php` debe quedar dentro de esa carpeta.
5. Confirma permisos recomendados:
   - Carpetas: `755`.
   - Archivos: `644`.
   - `storage/` y `storage/logs/`: `755` o `775` únicamente si PHP no puede escribir.

No uses permisos `777`.

## 4. Ejecutar el instalador

Visita:

```text
https://tu-dominio.pe/install.php
```

Completa:

- Nombre temporal o definitivo de la plataforma.
- URL pública exacta con HTTPS.
- Credenciales MySQL.
- Nombre, celular y contraseña segura del administrador.

El instalador:

- Crea todas las tablas.
- Carga categorías y distritos de Lima y Callao.
- Genera `app/config.php` con una clave aleatoria.
- Crea la cuenta administradora.
- Bloquea nuevas instalaciones mediante `storage/installed.lock`.

Después de comprobar el acceso, elimina `install.php` del servidor como medida adicional. Conserva una copia únicamente fuera de `public_html`.

## 5. Configurar el marketplace

Entra al panel administrador y abre **Configuración**:

1. Agrega el número de WhatsApp comercial.
2. Agrega el correo de soporte y privacidad.
3. Confirma la cobertura mostrada.
4. Revisa y aprueba manualmente cada perfil de proveedor.
5. Define si las reseñas requieren moderación.

## 6. Completar información legal

Antes de publicar tráfico o anuncios, actualiza `terms.php` y `privacy.php` con:

- Razón social o nombre del responsable.
- RUC o identificación aplicable.
- Domicilio.
- Titular y ubicación del banco de datos personales.
- Canal para derechos ARCO.
- Libro de Reclamaciones.
- Proveedores tecnológicos y transferencias de datos aplicables.

Solicita revisión legal peruana. Los textos incluidos son una base técnica, no asesoría legal.

## 7. Programar mantenimiento

En **Cron Jobs**, agrega una ejecución diaria. Sustituye las rutas por las de tu cuenta:

```bash
/usr/local/bin/php /home/TU_USUARIO/public_html/cron/cleanup.php
```

El script elimina rate limits vencidos y auditorías de más de dos años.

## 8. Copias de seguridad

- Activa backups diarios de archivos y MySQL.
- Conserva al menos una copia externa semanal.
- Prueba restauración antes del lanzamiento.
- Nunca descargues `app/config.php` en equipos compartidos.

## 9. Prueba de aceptación

Realiza el flujo completo con tres cuentas de prueba:

1. Cliente publica una solicitud.
2. Proveedor completa perfil.
3. Administrador verifica al proveedor.
4. Proveedor envía propuesta.
5. Cliente acepta.
6. Ambos abren el contacto de WhatsApp.
7. Cliente marca en curso y completado.
8. Cliente deja una reseña.
9. Administrador modera la reseña.
10. La landing `para-proveedores.php` muestra el plan de S/20 y lleva al registro de proveedor.

Comprueba también:

- `https://tu-dominio.pe/health.php` devuelve `status: ok`.
- `https://tu-dominio.pe/robots.txt` responde con `Sitemap:` absoluto (requiere `mod_rewrite`).
- `https://tu-dominio.pe/sitemap.xml` incluye `para-proveedores.php`.
- HTTPS no presenta contenido mixto.
- `app/`, `database/`, `storage/` y `templates/` responden 403.
- El instalador queda bloqueado.
- Los errores PHP no se muestran públicamente.

## Actualizaciones

Antes de reemplazar archivos:

1. Haz backup de MySQL y `app/config.php`.
2. Activa una página de mantenimiento desde cPanel.
3. No reemplaces `app/config.php` ni `storage/installed.lock`.
4. Prueba el flujo principal después de actualizar.
