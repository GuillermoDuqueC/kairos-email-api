# Imagen base con PHP + Apache
FROM php:8.2-apache

# Copia el código al contenedor
COPY . /var/www/html/

# Habilita las extensiones necesarias
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Permisos (opcional, depende del hosting)
RUN chown -R www-data:www-data /var/www/html

# Puerto por defecto
EXPOSE 80
