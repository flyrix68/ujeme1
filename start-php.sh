#!/bin/bash
set -e

# Vérifier si le port est déjà utilisé
if lsof -i :10000 >/dev/null; then
  echo "Le port 10000 est déjà utilisé. Arrêt des processus en cours..."
  kill $(lsof -t -i:10000) 2>/dev/null || true
fi

# Démarrer le serveur PHP intégré
echo "Démarrage du serveur PHP sur le port 10000..."
exec php -S 0.0.0.0:10000 index.php
