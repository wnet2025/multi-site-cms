FROM serversideup/php:8.3-fpm-alpine

RUN mkdir -p /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/storage

WORKDIR /var/www/html

USER www-data

