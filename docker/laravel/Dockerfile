FROM composer:2 AS vendor
WORKDIR /app
COPY barangay-sagip-web/composer.json barangay-sagip-web/composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM node:22-alpine AS frontend
WORKDIR /app
COPY barangay-sagip-web/package.json ./
COPY barangay-sagip-web/resources ./resources
COPY barangay-sagip-web/vite.config.js ./vite.config.js
RUN npm install --ignore-scripts
RUN npm run build

FROM php:8.4-fpm-bookworm
WORKDIR /var/www/html

RUN apt-get update \
    && apt-get upgrade -y \
    && apt-get install -y --no-install-recommends libpq-dev libicu-dev libzip-dev libonig-dev unzip \
    && docker-php-ext-install bcmath intl mbstring opcache pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=vendor /app/vendor ./vendor
COPY barangay-sagip-web/ ./
COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

COPY docker/laravel/php.ini /usr/local/etc/php/conf.d/99-production.ini

USER www-data
EXPOSE 9000
CMD ["php-fpm", "-F"]
