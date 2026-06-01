FROM composer:2 AS composer

FROM dunglas/frankenphp:1-php8.5-alpine

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache \
    postgresql-client \
    unzip

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1
