# Laravel aplikazioaren irudia: PHP 8.5 + Apache.
# app, queue eta scheduler zerbitzuek irudi hau bera erabiltzen dute.
FROM php:8.5-apache

# Sistemako paketeak eta PHP luzapenak (MySQL, zip, intl).
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev \
    && docker-php-ext-install pdo_mysql zip intl bcmath \
    && rm -rf /var/lib/apt/lists/*

# Composer, irudi ofizialetik kopiatuta.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache: docroot-a public/ karpeta da eta mod_rewrite gaituta (Laravel-en ibilbideak).
# 8080 portuan entzuten du, root ez den erabiltzaile batekin exekutatu ahal izateko.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!Listen 80$!Listen 8080!' /etc/apache2/ports.conf \
    && sed -ri 's!<VirtualHost \*:80>!<VirtualHost *:8080>!' /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite

# Composer-ek bere cachea idazteko karpeta (edozein erabiltzailerekin).
ENV COMPOSER_HOME=/tmp/composer
# 1000 erabiltzaileak ez du etxe-karpetarik; tinker-ek (psysh) bertan idazten du.
ENV HOME=/tmp

WORKDIR /var/www/html
EXPOSE 8080
