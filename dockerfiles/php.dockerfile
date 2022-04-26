FROM php:8-fpm-alpine

ARG UID
ARG GID

ENV UID=${UID}
ENV GID=${GID}

RUN mkdir -p /var/www/html

WORKDIR /var/www/html

# MacOS staff group's gid is 20, so is the dialout group in alpine linux. We're not using it, let's just remove it.
RUN delgroup dialout

RUN addgroup -g ${GID} --system laravel
RUN adduser -G laravel --system -D -s /bin/sh -u ${UID} laravel

RUN sed -i "s/user = www-data/user = laravel/g" /usr/local/etc/php-fpm.d/www.conf
RUN sed -i "s/group = www-data/group = laravel/g" /usr/local/etc/php-fpm.d/www.conf
RUN echo "php_admin_flag[log_errors] = on" >> /usr/local/etc/php-fpm.d/www.conf

# RUN apk add --no-cache mysql-client msmtp perl wget procps shadow libzip libpng libjpeg-turbo libwebp freetype icu
RUN apk update && apk upgrade
# RUN apk add php8 php81-zip
# RUN apk add --no-cache zip libzip-dev
# RUN docker-php-ext-configure zip
# RUN docker-php-ext-install zip

# RUN apk add openjdk11-jre-headless \
#     && apk add --no-cache --virtual \
#         .build-deps autoconf g++ make postgresql-dev libzip-dev \ # libzip-dev part of virtual package \
#     && docker-php-ext-configure zip \
#     && docker-php-ext-install zip \
#     && apk del .build-deps  \
#     && rm -rf /tmp/* \
#     && rm -rf /var/cache/apk/*

RUN docker-php-ext-install pdo pdo_mysql

RUN mkdir -p /usr/src/php/ext/redis \
    && curl -L https://github.com/phpredis/phpredis/archive/5.3.4.tar.gz | tar xvz -C /usr/src/php/ext/redis --strip 1 \
    && echo 'redis' >> /usr/src/php-available-exts \
    && docker-php-ext-install redis

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]
