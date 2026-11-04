FROM php:8.4.27-fpm AS php-base

RUN apt-get update \
    && apt-get install --yes --no-install-recommends libjpeg62-turbo-dev libonig-dev libpng-dev libpq-dev libsqlite3-dev libwebp-dev libzip-dev \
    && pecl install redis \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install gd mbstring pdo_pgsql pdo_sqlite zip pcntl opcache \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

FROM php-base AS development
RUN apt-get update \
    && apt-get install --yes --no-install-recommends git unzip \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

FROM php-base AS source
COPY composer.json composer.lock artisan ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY public ./public
COPY resources ./resources
COPY routes ./routes
COPY src ./src
RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && rm -f bootstrap/cache/*.php

FROM source AS dependencies
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader

FROM php-base AS production
COPY --from=dependencies /var/www/html /var/www/html
COPY docker/php/production.ini /usr/local/etc/php/conf.d/production.ini
COPY docker/php/healthcheck.php /usr/local/bin/app-healthcheck.php
COPY docker/php/production-entrypoint.sh /usr/local/bin/production-entrypoint.sh
RUN chown -R www-data:www-data storage bootstrap/cache
USER www-data
ENTRYPOINT ["sh", "/usr/local/bin/production-entrypoint.sh"]
CMD ["php-fpm", "-F"]

FROM nginx:1.31-alpine AS nginx
COPY --from=source /var/www/html/public /var/www/html/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/nginx/cloudflare-realip.conf /etc/nginx/cloudflare-realip.conf
