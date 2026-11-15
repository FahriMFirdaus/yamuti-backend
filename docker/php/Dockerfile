ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev

# Install official PHP extension helper (mlocati)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install ekstensi yang dibutuhkan Laravel 13 & YAMUTI
RUN install-php-extensions \
    pdo_mysql \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Buat user non-root agar permission file tetap sinkron dengan Host
ARG UID=1000
ARG GID=1000
RUN addgroup -g ${GID} laravel && \
    adduser -u ${UID} -G laravel -s /bin/sh -D laravel

USER laravel

EXPOSE 9000
CMD ["php-fpm"]
