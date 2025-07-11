FROM php:8.2-apache
WORKDIR /var/www/html

# Configure Apache
COPY apache-config.conf /etc/apache2/conf-available/000-default.conf
RUN a2enconf 000-default

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip

# Copy files and ensure correct permissions
COPY --chown=www-data:www-data . .
RUN chmod -R 755 /var/www/html

# File permissions handled by Railway volumes configuration
