FROM php:8.0-apache
WORKDIR /var/www/html
COPY . .
RUN a2enmod rewrite
RUN a2enmod headers
EXPOSE 80