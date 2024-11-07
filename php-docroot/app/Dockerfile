FROM php:7.1-apache
RUN apt-get update && apt-get install -yqq --no-install-recommends libpq-dev && docker-php-ext-install pdo_pgsql
COPY apache2.conf /etc/apache2/
