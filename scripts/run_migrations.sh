#!/bin/bash
set -e

# Database connection details - using hardcoded values from db-config.php
DB_HOST="yamanote.proxy.rlwy.net"
DB_PORT="58372" 
DB_USER="root"
DB_PASS="lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu"
DB_NAME="railway"

# Run migrations
echo "=== Applying database migrations ==="
mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS $DB_NAME <<EOF
$(cat ../sql/fix_status_column.sql)
$(cat ../sql/update_score_defaults.sql)
EOF

echo "=== Migration complete ==="
echo "=== Verifying schema changes ==="

# Verify the migrations worked
SCHEMA_CHECK=$(mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS $DB_NAME -e "SHOW COLUMNS FROM matches LIKE 'status';")
SCORE_CHECK=$(mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS $DB_NAME -e "SHOW COLUMNS FROM matches WHERE Field IN ('score_home', 'score_away');")

if [[ $SCHEMA_CHECK == *"enum('pending','ongoing','completed','finished')"* ]]; then
    echo "SUCCESS: Status column updated successfully"
else
    echo "ERROR: Status column update failed"
    exit 1
fi

if [[ $SCORE_CHECK == *"DEFAULT '0'"* ]]; then
    echo "SUCCESS: Score defaults updated successfully"
    exit 0
else
    echo "ERROR: Score defaults update failed"
    exit 1
fi
