FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite

COPY . /var/www/html/
COPY apache.conf /etc/apache2/conf-available/custom.conf
RUN a2enconf custom

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
