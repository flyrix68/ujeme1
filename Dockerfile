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

# Copy only required files (excluding node_modules, etc.)
COPY --chown=www-data:www-data . .

# File permissions handled by Railway volumes configuration
