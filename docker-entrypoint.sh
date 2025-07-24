#!/bin/bash
set -e

# Log important startup information
echo "===== STARTING CONTAINER ====="
date

# Set proper permissions
chown -R www-data:www-data /var/www/html /var/log/apache2
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Configure Apache port
export PORT=${PORT:-80}
echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Update port in Apache configuration
echo "Listen ${PORT}" > /etc/apache2/ports.conf

# Verify Apache configuration before starting
echo "Verifying Apache configuration..."
if ! apache2ctl configtest; then
    echo "Error: Apache configuration test failed"
    exit 1
fi

# Start Apache in the foreground
echo "Starting Apache on port ${PORT}..."
exec apache2-foreground
