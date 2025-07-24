#!/bin/bash
set -e

# Log important startup information
echo "===== STARTING CONTAINER ====="
date
echo "Environment:"
printenv | grep -E 'PORT|DATABASE|RAILWAY|APACHE|PHP|MYSQL'

echo "PHP version:"
php -v
echo "Apache version:"
apache2ctl -v

echo "===== STARTING CONTAINER ====="
date
echo "Environment:"
printenv
echo "PHP modules:"
php -m
echo "Apache version:"
apache2ctl -v

# Configure Apache
export PORT=${PORT:-80}
echo "Configuring Apache on port ${PORT}"

# Fix Apache configuration
echo "Listen ${PORT}" > /etc/apache2/ports.conf
echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Update Apache VirtualHost to use the PORT variable
cat > /etc/apache2/sites-available/000-default.conf <<EOL
<VirtualHost *:${PORT}>
    DocumentRoot /var/www/html
    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /dev/stderr
    CustomLog /dev/stdout combined
</VirtualHost>
EOL

# Database Connection
wait_for_db() {
  local db_host db_port timeout=60
  if [[ "${DATABASE_URL}" =~ mysql://([^:]+):([^@]+)@([^:]+):?([0-9]+)?/([^?]+) ]]; then
    db_host=${BASH_REMATCH[3]}
    db_port=${BASH_REMATCH[4]:-3306}
    
    echo "Waiting for MySQL at ${db_host}:${db_port}..."
    for i in $(seq 1 $timeout); do
      if nc -zw1 ${db_host} ${db_port}; then
        echo "✅ MySQL ready at ${db_host}:${db_port}"
        return 0
      fi
      echo -n "."
      sleep 1
    done
    echo "⚠️ MySQL connection timeout after ${timeout} seconds"
    return 1
  else
    echo "⚠️ Invalid DATABASE_URL format"
    return 1
  fi
}

# Run DB check in background to avoid blocking startup
wait_for_db || echo "Proceeding with degraded mode (DB not ready)" &

# Set permissions
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Set proper permissions
chown -R www-data:www-data /var/www/html /var/log/apache2
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Verify Apache configuration before starting
echo "Verifying Apache configuration..."
if ! apache2ctl configtest; then
    echo "Error: Apache configuration test failed"
    exit 1
fi

# Start Apache in the foreground
echo "Starting Apache on port ${PORT}..."
exec apache2-foreground
