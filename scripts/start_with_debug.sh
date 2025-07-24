#!/bin/bash
# Debug startup script

echo "=== Starting Apache with debug output ==="
echo "Current environment:"
printenv | sort

echo "=== Network interfaces ==="
ifconfig

echo "=== Listening ports ==="
netstat -tulnp

echo "=== Starting Apache..."
exec apache2-foreground
