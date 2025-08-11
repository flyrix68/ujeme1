<?php
// Configuration du fuseau horaire
date_default_timezone_set('Europe/Paris');

// Configuration du rapport d'erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autres configurations globales peuvent être ajoutées ici
