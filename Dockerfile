FROM php:8.3-apache
RUN docker-php-ext-install pdo_mysql mysqli && docker-php-ext-enable pdo_mysql mysqli