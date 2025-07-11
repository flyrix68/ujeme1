FROM php:8.2-apache
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html
