#!/bin/bash
set -e

# Configure Apache to use the PORT environment variable (default to 10000 if not set)
PORT=${PORT:-10000}

echo "Configuring Apache to use port ${PORT}..."
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Ensure proper permissions
chown -R www-data:www-data /var/www/html
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Start PHP-FPM in the background
echo "Starting PHP-FPM..."
service php8.2-fpm start

# Start Apache in the foreground
echo "Starting Apache service on port ${PORT}..."
service apache2 start

tail -f /var/log/apache2/error.log
