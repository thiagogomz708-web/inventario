FROM php:8.2-cli

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY . /app

WORKDIR /app

RUN [ -f /app/htaccess ] && mv /app/htaccess /app/.htaccess || true

CMD php -S 0.0.0.0:${PORT:-8080} index.php
