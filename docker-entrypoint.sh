#!/bin/bash
set -e

# Log important startup information
echo "===== STARTING CONTAINER ====="
echo "Environment: Production"
echo "Using PORT: 80"
date

# Configure Apache port from environment variable
PORT=${PORT:-80}
echo "Configuring Apache to listen on port ${PORT}..."


# Create necessary directories with proper permissions
echo "Creating and setting permissions for required directories..."
mkdir -p /var/www/html/logs /var/www/html/uploads
chown -R www-data:www-data /var/www/html
chmod -R 775 /var/www/html/logs /var/www/html/uploads

# Create log directories and set permissions
echo "Configuring log directories..."
mkdir -p /var/log/apache2 /var/run/apache2 /var/lock/apache2
chown -R www-data:www-data /var/log/apache2 /var/run/apache2 /var/lock/apache2
chmod -R 775 /var/log/apache2 /var/run/apache2 /var/lock/apache2

# Configure Apache
echo "Configuring Apache..."
echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Enable required modules
echo "Enabling required Apache modules..."
a2enmod rewrite headers

# Create .env file if it doesn't exist
ENV_FILE="/var/www/html/.env"
if [ ! -f "$ENV_FILE" ] && [ -f "/var/www/html/.env.example" ]; then
    echo "Creating .env file..."
    cp /var/www/html/.env.example "$ENV_FILE"
    chown www-data:www-data "$ENV_FILE"
    chmod 644 "$ENV_FILE"
fi

# Configure PHP settings
echo "Configuring PHP settings..."
cat > /usr/local/etc/php/conf.d/99-custom.ini << 'EOL'
error_reporting = E_ALL
display_errors = On
display_startup_errors = On
log_errors = On
error_log = /var/log/php_errors.log
log_errors_max_len = 1024
ignore_repeated_errors = Off
ignore_repeated_source = Off
report_memleaks = On
track_errors = On
html_errors = Off
post_max_size = 64M
upload_max_filesize = 64M
memory_limit = 256M
max_execution_time = 300
EOL

# Set proper permissions for PHP error log
touch /var/log/php_errors.log
chown www-data:www-data /var/log/php_errors.log
chmod 666 /var/log/php_errors.log

# Verify Apache configuration
echo "Verifying Apache configuration..."
if ! apache2ctl -t; then
    echo "Error: Apache configuration test failed"
    sleep 5  # Give time to see the error message
    exit 1
fi

# Link Apache logs to stdout/stderr
ln -sf /dev/stdout /var/log/apache2/access.log
ln -sf /dev/stderr /var/log/apache2/error.log

# Start Apache in the foreground
echo "Starting Apache..."
exec apache2-foreground -D FOREGROUND