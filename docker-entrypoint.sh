#!/bin/bash
set -e

# Enable debugging
set -x

# Log all commands for debugging
exec > >(tee -a /var/log/startup.log) 2>&1

# Log important startup information
echo "===== STARTING CONTAINER ====="
echo "Environment: ${RAILWAY_ENVIRONMENT:-Not Railway}"
echo "Using PORT: ${PORT:-80}"
date

# Set environment variables for Apache
if [ -n "$RAILWAY_ENVIRONMENT" ]; then
    echo "===== RUNNING IN RAILWAY ====="
    # Update Apache port configuration for Railway
    sed -i "s/Listen 80/Listen ${PORT:-80}/g" /etc/apache2/ports.conf
    sed -i "s/:80>/:${PORT:-80}>/g" /etc/apache2/sites-enabled/000-default.conf
fi

# Set proper permissions
echo "Setting file permissions..."
chown -R www-data:www-data ${APACHE_DOCUMENT_ROOT}
find ${APACHE_DOCUMENT_ROOT} -type d -exec chmod 755 {} \;
find ${APACHE_DOCUMENT_ROOT} -type f -exec chmod 644 {} \;
chmod -R 777 ${APACHE_DOCUMENT_ROOT}/logs ${APACHE_DOCUMENT_ROOT}/uploads

# Ensure log directory exists and is writable
mkdir -p ${APACHE_DOCUMENT_ROOT}/logs
chown -R www-data:www-data ${APACHE_DOCUMENT_ROOT}/logs
chmod -R 755 ${APACHE_DOCUMENT_ROOT}/logs

# Ensure upload directories exist
mkdir -p ${APACHE_DOCUMENT_ROOT}/uploads/{events,logos,medias,players,profiles}
chown -R www-data:www-data ${APACHE_DOCUMENT_ROOT}/uploads

# Ensure cacert.pem has the right permissions
if [ -f "${APACHE_DOCUMENT_ROOT}/includes/cacert.pem" ]; then
    echo "Setting permissions for cacert.pem..."
    chmod 644 ${APACHE_DOCUMENT_ROOT}/includes/cacert.pem
    chown www-data:www-data ${APACHE_DOCUMENT_ROOT}/includes/cacert.pem
else
    echo "Warning: cacert.pem not found in ${APACHE_DOCUMENT_ROOT}/includes/"
    # Use system CA certificates as fallback
    if [ -f "/etc/ssl/certs/ca-certificates.crt" ]; then
        echo "Using system CA certificates..."
    else
        echo "Warning: No CA certificates found"
    fi
fi

# Configure Apache
echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Update Apache virtual host to use the correct port
cat > /etc/apache2/sites-available/000-default.conf <<EOL
<VirtualHost *:${PORT:-80}>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined

    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOL

# Enable required modules
a2enmod rewrite
a2enmod headers
a2enmod ssl

# Enable SSL configuration
a2ensite default-ssl

# Configure PHP to log errors
echo "Configuring PHP error logging..."
cat > /etc/php/8.2/apache2/conf.d/99-custom.ini << 'EOL'
error_reporting = E_ALL
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
log_errors_max_len = 1024
ignore_repeated_errors = Off
ignore_repeated_source = Off
report_memleaks = On
track_errors = On
html_errors = Off
EOL

# Ensure Apache log directory exists
echo "Creating Apache log directory..."
mkdir -p /var/log/apache2
chown -R www-data:www-data /var/log/apache2

# Verify configuration
echo "Verifying Apache configuration..."
if ! apache2ctl configtest; then
    echo "Error: Apache configuration test failed"
    exit 1
fi

# Verify .env file exists and is readable
echo "Verifying environment configuration..."
ENV_FILE="/var/www/html/.env"
if [ ! -f "$ENV_FILE" ]; then
    echo "ERROR: .env file not found at $ENV_FILE"
    exit 1
fi
if [ ! -r "$ENV_FILE" ]; then
    echo "ERROR: .env file not readable at $ENV_FILE"
    exit 1
fi

# Load and export environment variables
echo "Loading environment variables from .env file..."
export $(cat "$ENV_FILE" | grep -v '^#' | xargs)
echo "Loaded environment variables:"
cat "$ENV_FILE" | grep -v '^#' | sed 's/^/  /'

# Verify DATABASE_URL is set
if [ -z "$DATABASE_URL" ]; then
    echo "ERROR: DATABASE_URL is not set in .env"
    exit 1
fi

# Debug environment variables
echo "Current Environment:"
echo "  DATABASE_URL=${DATABASE_URL:0:20}...[redacted]"
echo "  PORT=${PORT:-8080}"

# Start Apache
echo "Starting Apache on port ${PORT:-8080}..."
exec apache2-foreground
