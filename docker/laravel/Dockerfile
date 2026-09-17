FROM composer:2 AS vendor
WORKDIR /app
COPY barangay-sagip-web/composer.json barangay-sagip-web/composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM php:8.4-fpm-bookworm
WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libicu-dev libzip-dev unzip \
    && docker-php-ext-install bcmath intl mbstring opcache pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=vendor /app/vendor ./vendor
COPY barangay-sagip-web/ ./

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

COPY docker/laravel/php.ini /usr/local/etc/php/conf.d/99-production.ini

USER www-data
EXPOSE 9000
CMD ["php-fpm", "-F"]
