FROM php:8.4-cli

RUN apt-get update \
    && apt-get install --yes --no-install-recommends $PHPIZE_DEPS git libjpeg62-turbo-dev libonig-dev libpng-dev libsqlite3-dev libwebp-dev libzip-dev unzip \
    && pecl install redis pcov \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install gd mbstring pdo_sqlite zip \
    && docker-php-ext-enable redis \
    && docker-php-ext-enable pcov \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
