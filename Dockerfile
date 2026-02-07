FROM php:8.2-cli-alpine

RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    zip \
    unzip \
    git

RUN docker-php-ext-configure zip \
    && docker-php-ext-install intl zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
