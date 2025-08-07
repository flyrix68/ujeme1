#!/bin/bash
set -e

# Configure Apache to use the PORT environment variable (default to 10000 if not set)
PORT=${PORT:-10000}

echo "===== STARTING CONTAINER ====="
echo "Configuring Apache to use port ${PORT}..."

# Ensure we have a clean configuration
rm -f /etc/apache2/ports.conf
rm -f /etc/apache2/sites-enabled/*.conf

# Create minimal ports configuration
echo "Listen ${PORT}" > /etc/apache2/ports.conf

# Enable only necessary modules
a2enmod rewrite headers

# Ensure proper permissions
echo "Setting file permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Verify Apache configuration
echo "Verifying Apache configuration..."
apache2ctl -t

# Start Apache in the foreground
echo "Starting Apache on port ${PORT}..."

# Print environment for debugging
echo "Environment:"
printenv | sort

exec apache2-foreground -DNO_DETACH
