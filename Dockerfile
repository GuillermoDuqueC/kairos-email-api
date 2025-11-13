# Imagen base oficial con PHP y Apache
FROM php:8.2-apache

# Configurar ServerName para evitar el warning AH00558
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Instalar herramientas necesarias
RUN apt-get update && apt-get install -y unzip git \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilitar extensiones necesarias
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copiar Composer desde su imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar los archivos del proyecto
COPY . /var/www/html/

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Dar permisos adecuados
RUN chown -R www-data:www-data /var/www/html

# Instalar dependencias PHP (PHPMailer, etc.)
RUN composer install --no-interaction --no-progress --prefer-dist --optimize-autoloader

# Habilitar módulo de reescritura (por si usas rutas amigables)
RUN a2enmod rewrite

# 👉 Configurar Apache para usar el puerto dinámico que Railway asigna
RUN sed -i "s/80/${PORT}/g" /etc/apache2/sites-available/000-default.conf

# Exponer el puerto dinámico
EXPOSE ${PORT}

# Comando por defecto para ejecutar Apache en primer plano
CMD ["apache2-foreground"]
