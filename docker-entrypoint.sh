#!/bin/bash
set -e

# Wait for MySQL to be ready (only if DATABASE_URL is set)
if [ -n "$DATABASE_URL" ]; then
  host=$(echo $DATABASE_URL | cut -d'@' -f2 | cut -d':' -f1)
  echo "Waiting for MySQL at $host to be ready..."
  while ! nc -z $host 3306; do
    sleep 1
  done
fi

# Set permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Start Apache in the background
apache2-foreground &

# Execute the original command (e.g. running tests)
exec "$@"
