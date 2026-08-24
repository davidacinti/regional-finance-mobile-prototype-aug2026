FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libonig-dev \
        libsqlite3-dev \
        libzip-dev \
        nodejs \
        npm \
        unzip \
    && docker-php-ext-install mbstring pdo pdo_sqlite zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock package.json package-lock.json ./

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts \
    && npm ci

COPY . .

RUN composer dump-autoload --optimize \
    && npm run build \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && php artisan package:discover --ansi

CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
