#!/bin/bash
# Export project as Docker image for Railway manual upload

# Build the Docker image
docker build -t ujem-app .

# Save to tar file
docker save ujem-app -o ujem-app.tar

echo "Image exported to ujem-app.tar"
echo "Upload this file to Railway Container Registry"
