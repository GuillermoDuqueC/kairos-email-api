# Imagen base de PHP con Apache
FROM php:8.2-apache

# Instalar dependencias del sistema necesarias
RUN apt-get update && apt-get install -y unzip git

# Copiar el código fuente
COPY . /var/www/html/

# Copiar Composer desde imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Instalar dependencias PHP
RUN composer install --no-interaction --no-progress --prefer-dist

# Permisos correctos
RUN chown -R www-data:www-data /var/www/html

# Exponer puerto
EXPOSE 80
