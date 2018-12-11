FROM php:fpm

RUN apt-get update \
  && apt-get install -y --no-install-recommends libpq-dev \
  && docker-php-ext-install pdo_pgsql pdo_mysql

RUN usermod -u 1002 www-data \
    && groupmod -g 1003 www-data
