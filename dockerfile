FROM php:8.2-apache
WORKDIR /var/www/html
RUN a2enmod rewrite
#RUN apt-get update && apt-get install -y libzip-dev zip \
#    && docker-php-ext-install zip
EXPOSE 80
CMD ["apache2-foreground"]