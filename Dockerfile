FROM php:7.4-apache

LABEL author="Dennis Paul / blaumedia"
LABEL maintainer="dennis@blaumedia.com"

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

RUN mkdir /var/www/html/cache \
    && touch /var/www/html/cache/index.html \
    && chown -R www-data:www-data /var/www/html

COPY --chown=www-data:www-data vendor/*.php ./
COPY --chown=www-data:www-data get.php ./