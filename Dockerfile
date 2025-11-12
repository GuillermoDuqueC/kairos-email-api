# Imagen base oficial con PHP y Apache
FROM php:8.2-apache

# Instalar herramientas necesarias
RUN apt-get update && apt-get install -y unzip git

# Copiar Composer desde su imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar los archivos del proyecto
COPY . /var/www/html/

# Instalar dependencias PHP (PHPMailer, etc)
WORKDIR /var/www/html
RUN composer install --no-interaction --no-progress --prefer-dist

# Exponer el puerto 80
EXPOSE 80