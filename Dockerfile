FROM php:8.2-apache
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite
COPY . /var/www/html/
RUN a2enmod rewrite && chown -R www-data:www-data /var/www/html/database
EXPOSE 80
CMD ["apache2-foreground"]
