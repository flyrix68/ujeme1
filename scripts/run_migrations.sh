#!/bin/bash
set -e

# Load environment
if [ -f ../.env ]; then
    export $(grep -v '^#' ../.env | xargs)
fi

# Database connection details
if [ -z "$DATABASE_URL" ]; then
    echo "ERROR: DATABASE_URL not set"
    exit 1
fi

DB_USER=$(echo $DATABASE_URL | sed -e 's/mysql:\/\///' | cut -d: -f1)
DB_PASS=$(echo $DATABASE_URL | sed -e 's/mysql:\/\///' | cut -d: -f2 | cut -d@ -f1)
DB_HOST=$(echo $DATABASE_URL | cut -d@ -f2 | cut -d: -f1)
DB_PORT=$(echo $DATABASE_URL | cut -d@ -f2 | cut -d: -f2 | cut -d/ -f1)
DB_NAME=$(echo $DATABASE_URL | cut -d/ -f4)

# Run migrations
echo "=== Applying database migrations ==="
mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS $DB_NAME <<EOF
$(cat ../sql/fix_status_column.sql)
EOF

echo "=== Migration complete ==="
echo "=== Verifying schema changes ==="

# Verify the migration worked
SCHEMA_CHECK=$(mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS $DB_NAME -e "SHOW COLUMNS FROM matches LIKE 'status';")

if [[ $SCHEMA_CHECK == *"enum('pending','ongoing','completed','finished')"* ]]; then
    echo "SUCCESS: Status column updated successfully"
    exit 0
else
    echo "ERROR: Status column update failed"
    exit 1
fi
