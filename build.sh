#!/bin/bash
# Installation des dépendances
composer install --no-dev --optimize-autoloader

# Installation de l'extension PDO pour PostgreSQL
apt-get update && apt-get install -y libpq-dev

docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
docker-php-ext-install pdo pdo_pgsql pgsql

# Création des dossiers nécessaires
mkdir -p storage/logs storage/cache storage/sessions
mkdir -p bootstrap/cache

# Configuration des permissions
chmod -R 755 storage bootstrap/cache
chmod +x build.sh

# Création du fichier de configuration de la base de données
mkdir -p config
cat > config/database.php << 'EOL'
<?php
return [
    'default' => 'pgsql',
    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => getenv('DATABASE_URL'),
            'host' => getenv('DB_HOST'),
            'port' => getenv('DB_PORT', '5432'),
            'database' => getenv('DB_DATABASE'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],
    ],
];
?>
EOL

echo "Build terminé avec succès"
