#!/bin/bash
set -e

# Railway-specific configurations
if [ -n "$RAILWAY_ENVIRONMENT" ]; then
    echo "===== RUNNING IN RAILWAY ====="
    export PORT=${PORT:-8080} # Default to 8080 on Railway
    export DB_HOST=${DATABASE_URL##*@} # Extract host from DATABASE_URL
    export DB_PORT=${DB_HOST##*:}
    export DB_HOST=${DB_HOST%:*}
fi

# Log important startup information
echo "===== STARTING CONTAINER ====="
echo "Environment: ${RAILWAY_ENVIRONMENT:-Not Railway}"
echo "Using PORT: $PORT"
date

# Set proper permissions
chown -R www-data:www-data /var/www/html /var/log/apache2
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Configure Apache
echo "ServerName localhost" >> /etc/apache2/apache2.conf
echo "Listen $PORT" > /etc/apache2/ports.conf

# Update Apache virtual host to use the correct port
sed -i "s/80/$PORT/" /etc/apache2/sites-available/000-default.conf

# Verify configuration
echo "Verifying Apache configuration..."
if ! apache2ctl configtest; then
    echo "Error: Apache configuration test failed"
    exit 1
fi

# Start Apache
echo "Starting Apache on port $PORT..."
exec apache2-foreground
