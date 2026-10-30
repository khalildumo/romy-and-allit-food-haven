FROM node:22-bookworm-slim AS frontend

WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN unset npm_config_cache_dir NPM_CONFIG_CACHE_DIR && npm ci --legacy-peer-deps --no-audit
COPY . .
RUN unset npm_config_cache_dir NPM_CONFIG_CACHE_DIR && npm run build

FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libonig-dev libpng-dev libpq-dev libxml2-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd mbstring opcache pdo_mysql pdo_pgsql xml zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
COPY . .

RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public \
    && composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --optimize-autoloader \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data bootstrap/cache storage

COPY --from=frontend /app/public/build /var/www/html/public/build
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/render-entrypoint.sh /usr/local/bin/render-entrypoint

RUN chmod +x /usr/local/bin/render-entrypoint

EXPOSE 10000
ENTRYPOINT ["/usr/local/bin/render-entrypoint"]
CMD ["apache2-foreground"]
