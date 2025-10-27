FROM php:8.3-fpm-alpine

RUN apk add --no-cache git unzip libpq-dev icu-dev oniguruma-dev bash

RUN docker-php-ext-install intl mbstring pcntl \
 && docker-php-ext-configure pgsql --with-pgsql=/usr \
 && docker-php-ext-install pdo pdo_pgsql opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/bin/sh", "/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

WORKDIR /var/www/html
