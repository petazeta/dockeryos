FROM php:7.4.3-apache
RUN apt-get update && apt-get install -y libpq-dev && \
docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql