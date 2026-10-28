# Imagen de desarrollo para probar Listo sin cPanel.
# No usar tal cual en producción: las credenciales demo son públicas.
FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache.conf /etc/apache2/conf-available/listo.conf
RUN a2enconf listo

WORKDIR /var/www/html

COPY . /var/www/html

RUN rm -rf /var/www/html/.git /var/www/html/.github \
    && mkdir -p /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/app /var/www/html/storage \
    && chmod -R 775 /var/www/html/app /var/www/html/storage

COPY docker/entrypoint.sh /usr/local/bin/listo-entrypoint
RUN chmod +x /usr/local/bin/listo-entrypoint

ENTRYPOINT ["listo-entrypoint"]
CMD ["apache2-foreground"]

EXPOSE 80
