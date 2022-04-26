FROM php:8-fpm-alpine

ARG WWWGROUP

WORKDIR /var/www/html

RUN apk update && apk upgrade
RUN apk add php8 php8-zip
RUN apk add --no-cache zip libzip-dev libzip
RUN docker-php-ext-configure zip
RUN docker-php-ext-install zip

RUN docker-php-ext-install pdo pdo_mysql

RUN mkdir -p /usr/src/php/ext/redis \
    && curl -L https://github.com/phpredis/phpredis/archive/5.3.4.tar.gz | tar xvz -C /usr/src/php/ext/redis --strip 1 \
    && echo 'redis' >> /usr/src/php-available-exts \
    && docker-php-ext-install redis