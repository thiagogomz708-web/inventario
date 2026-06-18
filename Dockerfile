FROM php:8.2-apache

# Instalar extensiones MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar mod_rewrite y quitar MPM duplicado
RUN a2enmod rewrite \
    && a2dismod mpm_event \
    && a2enmod mpm_prefork

# Permitir .htaccess
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Railway usa PORT env var — Apache debe escuchar en ese puerto
RUN echo 'Listen ${PORT}' > /etc/apache2/ports.conf \
    && sed -i 's|<VirtualHost \*:80>|<VirtualHost *:${PORT}>|g' /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

# Railway inyecta PORT automáticamente
CMD ["apache2-foreground"]
