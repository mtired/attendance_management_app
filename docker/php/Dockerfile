FROM php:8.4-fpm

COPY php.ini /usr/local/etc/php/

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
     default-mysql-client zlib1g-dev libzip-dev unzip \
  && docker-php-ext-install pdo_mysql zip \
  && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --version=2.9.2  \
  && mv composer.phar /usr/local/bin/composer \
  && composer self-update

WORKDIR /var/www
