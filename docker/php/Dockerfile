FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libzip-dev unzip curl \
    && docker-php-ext-install pdo pdo_mysql zip

WORKDIR /var/www/html
