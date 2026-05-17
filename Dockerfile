# syntax=docker/dockerfile:1.7

# --- Stage 1: composer dependencies -----------------------------------------
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock symfony.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

COPY . .
RUN composer dump-autoload --classmap-authoritative --no-dev

# --- Stage 2: runtime (Apache + PHP 8.4) ------------------------------------
FROM php:8.4-apache AS runtime

RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        libxslt1-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libsodium-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        intl \
        zip \
        xsl \
        gd \
        bcmath \
        opcache \
    && rm -rf /var/lib/apt/lists/*

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini

RUN a2enmod rewrite headers env
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf

COPY docker/apache-env.conf /etc/apache2/conf-enabled/zz-app-env.conf

WORKDIR /var/www/html

COPY --from=vendor --chown=www-data:www-data /app /var/www/html

# Build-time env: only enough for the kernel to boot during cache warmup and
# asset compilation. Real values are supplied at runtime via compose / the host.
ENV APP_ENV=prod \
    APP_DEBUG=0 \
    APP_SECRET=build-time-placeholder \
    DATABASE_URL="mysql://placeholder:placeholder@127.0.0.1:3306/placeholder?serverVersion=10.11.6-MariaDB&charset=utf8mb4"

RUN php bin/console importmap:install --no-debug \
 && php bin/console asset-map:compile --no-debug \
 && php bin/console cache:clear --env=prod --no-debug \
 && mkdir -p var/cache var/log var/share \
 && chown -R www-data:www-data var public/assets

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
