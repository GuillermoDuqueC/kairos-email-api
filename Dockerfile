# Imagen base con PHP + Apache
FROM php:8.2-apache

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y unzip git

# Copiar el código fuente al contenedor
COPY . /var/www/html/

# Copiar Composer desde imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Instalar dependencias PHP (PHPMailer)
RUN composer install --no-interaction --no-progress --prefer-dist

# Exponer el puerto 80
EXPOSE 80
