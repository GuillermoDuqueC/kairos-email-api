# Imagen base con PHP + Apache
FROM php:8.2-apache

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y unzip git

# Copiar archivos del proyecto
COPY . /var/www/html/

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instalar PHPMailer
WORKDIR /var/www/html
RUN composer install --no-interaction --no-progress

# Dar permisos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
