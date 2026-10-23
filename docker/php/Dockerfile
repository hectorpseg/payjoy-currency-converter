FROM php:8.5-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-install intl zip curl bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock ./
COPY bin /app/bin/
COPY config /app/config/
COPY public /app/public/
COPY src /app/src/
COPY templates /app/templates/

RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --no-progress

RUN touch /app/.env

CMD php -S 0.0.0.0:${PORT:-8000} -t public
