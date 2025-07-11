FROM php:8.2-apache
EXPOSE 8000
WORKDIR /var/www/html

# Configure Apache
COPY apache-config.conf /etc/apache2/conf-available/000-default.conf
RUN a2enconf 000-default

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip

# Enable debug logging and copy files
RUN echo "error_log = /var/log/php_errors.log" >> /usr/local/etc/php/conf.d/error-logging.ini
COPY --chown=www-data:www-data . .
RUN chmod -R 755 /var/www/html && \
    mkdir -p /var/log/apache2 && \
    chown www-data:www-data /var/log/apache2

# File permissions handled by Railway volumes configuration
