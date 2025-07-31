<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la configuration de la base de données
require 'includes/db-config.php';

try {
    // Obtenir la connexion à la base de données
    $pdo = DatabaseConfig::getConnection();
    
    // Activer les exceptions PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Requête pour trouver un match en cours ou en attente
    $stmt = $pdo->query("
        SELECT id, team_home, team_away, score_home, score_away, status, saison, competition, poule_id 
        FROM matches 
        WHERE status = 'ongoing' OR status = 'pending' 
        ORDER BY id DESC 
        LIMIT 1
    ");
    
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($match) {
        echo "<h1>Match trouvé pour la finalisation</h1>";
        echo "<pre>" . print_r($match, true) . "</pre>";
        
        // Afficher un lien pour déboguer la finalisation de ce match
        $debugUrl = "debug_finalize.php?match_id=" . $match['id'];
        echo "<p><a href='$debugUrl' target='_blank'>Déboguer la finalisation de ce match</a></p>";
    } else {
        echo "<h1>Aucun match en cours ou en attente trouvé</h1>";
        echo "<p>Créez d'abord un match ou attendez qu'un match commence.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h1>Erreur de base de données</h1>";
    echo "<p><strong>Message d'erreur:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    if (isset($e->errorInfo) && is_array($e->errorInfo)) {
        echo "<p><strong>Détails de l'erreur SQL:</strong></p>";
        echo "<pre>" . print_r($e->errorInfo, true) . "</pre>";
    }
}

// Afficher un lien pour retourner au tableau de bord
echo "<p><a href='admin/dashboard.php'>&larr; Retour au tableau de bord</a></p>";
?>
