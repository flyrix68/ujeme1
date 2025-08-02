<?php
// Configuration de base
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php-errors.log');

// Définir le fuseau horaire
date_default_timezone_set('Europe/Paris');

// Inclure le fichier principal
require __DIR__.'/../accueil.php';
