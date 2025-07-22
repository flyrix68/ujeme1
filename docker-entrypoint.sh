#!/bin/bash
# Enhanced debugging
set -exo
#!/bin/bash
# Enhanced Debug Mode - runs before anything else
set -ex
exec > >(tee /var/log/startup-debug.log) 2>&1
trap 'echo "[CRITICAL] Failed at line $LINENO"; tail -n 50 /var/log/startup-debug.log; exit 1' ERR

echo "===== STARTING DEBUG SESSION ====="
date
echo "Current user: $(whoami)"
echo "Working directory: $(pwd)"
echo "Disk space:"
df -h
echo "Memory:"
free -m
echo "Environment variables:"
printenv
echo "PHP version:"
php -v
echo "Apache version:"
apache2ctl -v
echo "Important binaries:"
which php apache2ctl mysql
echo "Directory contents:"
ls -la /var/www/html

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
sed -i "s/\${PORT}/${PORT}/g" /etc/apache2/sites-available/*.conf

# MySQL Connection
if [ -n "$DATABASE_URL" ]; then
  if [[ "$DATABASE_URL" =~ mysql://([^:]+):([^@]+)@([^:]+):?([0-9]+)?/([^?]+) ]]; then
    db_host=${BASH_REMATCH[3]}
    db_port=${BASH_REMATCH[4]:-3306}
    
    echo "Waiting for MySQL at ${db_host}:${db_port}..."
    
    # Install netcat if missing
    if ! command -v nc &> /dev/null; then
      apt-get update && apt-get install -y netcat-openbsd
    fi
    
    # Wait with timeout
    counter=0
    until nc -z ${db_host} ${db_port}; do
      sleep 1
      counter=$((counter+1))
      if [ $counter -ge 60 ]; then
        echo "❌ MySQL connection timeout"
        exit 1
      fi
    done
    echo "✅ MySQL ready at ${db_host}:${db_port}"
  else
    echo "⚠️ Invalid DATABASE_URL format"
  fi
fi

# Set permissions
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Enhanced startup logging
echo "🔍 Environment Variables:"
printenv | grep -E 'DATABASE|RAILWAY|APACHE|PHP'

echo "🔍 PHP Modules:"
php -m

echo "🚀 Starting Apache with debug logging..."
exec apache2ctl -e debug -D FOREGROUND
