#!/bin/bash
set -e

# Configure Apache to use the PORT environment variable (default to 8080 if not set)
PORT=${PORT:-8080}

echo "Configuring Apache to use port ${PORT}..."

# Update Apache configuration to use the specified port
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Ensure proper permissions
chown -R www-data:www-data /var/www/html
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Start Apache in the foreground
echo "Starting Apache service on port ${PORT}..."

# Enable necessary Apache modules
a2enmod rewrite

# Start Apache in the foreground
apache2-foreground
