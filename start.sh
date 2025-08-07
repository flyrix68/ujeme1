#!/bin/bash
set -e

# Configure Apache to use the PORT environment variable (default to 10000 if not set)
PORT=${PORT:-10000}

echo "===== STARTING CONTAINER ====="

# Disable any default SSL configuration
a2dismod ssl

# Disable default sites
a2dissite 000-default default-ssl 2>/dev/null || true

# Enable necessary Apache modules
a2enmod rewrite headers

# Update Apache configuration to use the specified port
echo "Configuring Apache to use port ${PORT}..."
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf

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

exec apache2-foreground
