FROM php:8.2-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Install PDO MySQL extension
RUN docker-php-ext-install pdo_mysql

# Copy all backend files directly
COPY backend/ /var/www/html/

# Set permissions
RUN chmod -R 755 /var/www/html

# Create index.php to avoid directory listing
RUN echo '<?php header("Location: api/properties.php"); ?>' > /var/www/html/index.php

EXPOSE 80
