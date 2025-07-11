#!/bin/bash

# Railway startup script for UJEM application

# Set default port if not provided
export PORT=${PORT:-8000}

# Update Apache configuration with the correct port
sed -i "s/\${PORT}/$PORT/g" /etc/apache2/sites-available/000-default.conf
sed -i "s/\${PORT}/$PORT/g" /etc/apache2/ports.conf

# Enable Apache modules
a2enmod rewrite
a2enmod headers

# Set proper permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Create upload directories if they don't exist
mkdir -p /var/www/html/uploads/{events,logos,medias,players,profiles}
chown -R www-data:www-data /var/www/html/uploads
chmod -R 755 /var/www/html/uploads

# Start Apache in foreground
echo "Starting Apache on port $PORT"
apache2-foreground
