# Imagen base oficial con PHP y Apache
FROM php:8.2-apache

# Evitar warning de ServerName
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y git unzip libzip-dev && docker-php-ext-install zip mysqli

# Habilitar mod_rewrite (útil si usas rutas limpias)
RUN a2enmod rewrite

# Copiar Composer desde la imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar los archivos del proyecto
COPY . /var/www/html/

# Establecer permisos adecuados
RUN chown -R www-data:www-data /var/www/html

# Instalar dependencias de PHP (PHPMailer, etc.)
WORKDIR /var/www/html
RUN composer install --no-interaction --no-progress --prefer-dist --optimize-autoloader

# Exponer puerto 80
EXPOSE 80

# Iniciar Apache en primer plano
CMD ["apache2-foreground"]
