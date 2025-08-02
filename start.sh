#!/bin/bash

# Simplified startup script for Render

# Just start Apache with the existing configuration
# Apache will automatically use the ${PORT} variable
echo "Starting Apache service..."
apache2-foreground
