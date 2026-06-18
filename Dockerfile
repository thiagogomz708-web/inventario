FROM php:8.2-apache

# Instalar extensiones MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Corregir MPM: deshabilitar event/worker y habilitar prefork
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# Permitir .htaccess en el directorio web
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Incluir configuración extra de Apache
COPY apache.conf /etc/apache2/conf-available/app.conf
RUN a2enconf app

# Script de inicio: Railway asigna PORT dinámicamente
RUN echo '#!/bin/bash\n\
sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf\n\
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf\n\
exec apache2-foreground' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

# Copiar archivos de la app
COPY . /var/www/html/

# Renombrar htaccess a .htaccess si no tiene punto
RUN [ -f /var/www/html/htaccess ] && mv /var/www/html/htaccess /var/www/html/.htaccess || true

RUN chown -R www-data:www-data /var/www/html

CMD ["/usr/local/bin/start.sh"]
