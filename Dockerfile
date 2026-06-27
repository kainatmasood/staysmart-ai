FROM php:8.2-apache

RUN a2enmod rewrite
RUN docker-php-ext-install pdo_mysql

# Copy the ENTIRE repository to the web root
COPY . /var/www/html/

RUN chmod -R 755 /var/www/html
RUN echo '<?php echo "Backend is running!"; ?>' > /var/www/html/index.php

EXPOSE 80
