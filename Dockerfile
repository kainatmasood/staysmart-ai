FROM php:8.2-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Install PostgreSQL driver
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql

# Copy backend folder to /var/www/html/
COPY backend/ /var/www/html/

# Set permissions
RUN chmod -R 755 /var/www/html

# Create index.php
RUN echo '<?php echo "Backend is running!"; ?>' > /var/www/html/index.php

EXPOSE 80
