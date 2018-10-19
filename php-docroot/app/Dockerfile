FROM php:7.1-apache

RUN apt-get update && apt-get install -y \
       apt-utils \
       zlib1g-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-source extract \
    && docker-php-ext-install pdo pdo_mysql \
    && docker-php-ext-install zip \
    && docker-php-ext-install opcache
RUN pecl install apcu
RUN echo "extension=apcu.so" > /usr/local/etc/php/conf.d/apcu.ini

COPY $PWD/app/apache2.conf /etc/apache2/
