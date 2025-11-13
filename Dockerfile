FROM php:8.2-apache

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

RUN apt-get update && apt-get install -y unzip git \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . /var/www/html/
WORKDIR /var/www/html/

RUN chown -R www-data:www-data /var/www/html
RUN composer install --no-interaction --no-progress --prefer-dist --optimize-autoloader
RUN a2enmod rewrite

EXPOSE 80
CMD ["apache2-foreground"]
