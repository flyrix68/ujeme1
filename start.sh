#!/bin/bash
set -e

# Configure Apache to use the PORT environment variable (default to 10000 if not set)
PORT=${PORT:-10000}

echo "===== STARTING CONTAINER ====="

# Completely remove default SSL configuration
if [ -f /etc/apache2/sites-enabled/default-ssl.conf ]; then
    rm /etc/apache2/sites-enabled/default-ssl.conf
fi
if [ -f /etc/apache2/sites-available/default-ssl.conf ]; then
    rm /etc/apache2/sites-available/default-ssl.conf
fi

# Disable SSL module
a2dismod -f ssl

# Disable default sites
a2dissite -f 000-default default-ssl 2>/dev/null || true

# Enable necessary Apache modules
a2enmod rewrite headers

# Remove any Listen directives for port 443
sed -i '/Listen 443/d' /etc/apache2/ports.conf

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

# Start Apache in the foreground
exec apache2-foreground -DNO_DETACH
