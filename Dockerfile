FROM php:8.2-apache
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip

# Copy only required files (excluding node_modules, etc.)
COPY --chown=www-data:www-data . .

# Configure for Railway volumes
VOLUME /var/www/html
