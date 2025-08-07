#!/bin/bash
set -e

# Configure Apache to use the PORT environment variable (default to 10000 if not set)
PORT=${PORT:-10000}

echo "===== STARTING CONTAINER ====="

echo "Configuring Apache to use port ${PORT}..."

# Update Apache configuration to use the specified port
cat > /etc/apache2/ports.conf <<EOL
Listen ${PORT}
<IfModule ssl_module>
    Listen 443
</IfModule>
<IfModule mod_gnutls.c>
    Listen 443
</IfModule>
EOL

# Disable SSL module if it exists
a2dismod -f ssl 2>/dev/null || true

# Enable necessary modules
a2enmod rewrite headers

# Disable default sites
a2dissite -f 000-default default-ssl 2>/dev/null || true

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
