#!/bin/sh
set -eu

# Inicializa base de datos, administrador y datos demo de forma idempotente.
php /var/www/html/docker/init.php

# El entrypoint corre como root; Apache lo hace como www-data.
chown -R www-data:www-data /var/www/html/app /var/www/html/storage
if [ -f /var/www/html/app/config.php ]; then
    chown www-data:www-data /var/www/html/app/config.php
    chmod 640 /var/www/html/app/config.php
fi
if [ -f /var/www/html/storage/installed.lock ]; then
    chown www-data:www-data /var/www/html/storage/installed.lock
fi

exec "$@"
