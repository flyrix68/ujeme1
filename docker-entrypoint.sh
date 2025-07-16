#!/bin/bash
set -e

# Set default permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Execute Docker CMD
exec "$@"
