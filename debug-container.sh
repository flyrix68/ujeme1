#!/bin/bash

# Afficher la structure des fichiers
echo "=== Structure du répertoire /var/www/html ==="
ls -la /var/www/html

# Vérifier les fichiers de configuration Apache
echo -e "\n=== Fichiers de configuration Apache ==="
ls -la /etc/apache2/sites-enabled/

# Afficher le contenu du fichier de configuration par défaut
echo -e "\n=== Contenu de /etc/apache2/sites-enabled/000-default.conf ==="
cat /etc/apache2/sites-enabled/000-default.conf

# Vérifier les modules Apache chargés
echo -e "\n=== Modules Apache chargés ==="
apache2ctl -M

# Vérifier le propriétaire des fichiers
echo -e "\n=== Propriétaire des fichiers ==="
ls -ld /var/www/html /var/www/html/*

# Vérifier si PHP fonctionne
echo -e "\n=== Test PHP ==="
echo "<?php phpinfo(); ?>" > /var/www/html/info.php
echo "Test PHP créé à /var/www/html/info.php"
