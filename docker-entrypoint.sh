#!/bin/bash
set -e

# Railway-specific configurations
if [ -n "$RAILWAY_ENVIRONMENT" ]; then
    echo "===== RUNNING IN RAILWAY ====="
    export PORT=${PORT:-8080} # Default to 8080 on Railway
    # Remove any existing Listen directives
    sed -i '/^Listen/d' /etc/apache2/ports.conf
    # Add new Listen directive
    echo "Listen $PORT" > /etc/apache2/ports.conf
fi

# Log important startup information
echo "===== STARTING CONTAINER ====="
echo "Environment: ${RAILWAY_ENVIRONMENT:-Not Railway}"
echo "Using PORT: $PORT"
date

# Set proper permissions
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

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
