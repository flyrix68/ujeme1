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
    
    // Requête pour obtenir les informations du match
    $stmt = $pdo->query("SELECT id, team_home, team_away, score_home, score_away, status, saison, competition, poule_id FROM matches WHERE id = 26");
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($match) {
        echo "Match ID: " . $match['id'] . "\n";
        echo "Équipe à domicile: " . $match['team_home'] . "\n";
        echo "Équipe à l'extérieur: " . $match['team_away'] . "\n";
        echo "Score domicile: " . ($match['score_home'] ?? 'Non défini') . "\n";
        echo "Score extérieur: " . ($match['score_away'] ?? 'Non défini') . "\n";
        echo "Statut: " . $match['status'] . "\n";
        echo "Saison: " . ($match['saison'] ?? 'Non défini') . "\n";
        echo "Compétition: " . ($match['competition'] ?? 'Non défini') . "\n";
        echo "ID de la poule: " . ($match['poule_id'] ?? 'Non défini') . "\n";
    } else {
        echo "Aucun match trouvé avec l'ID 26.\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur de base de données: " . $e->getMessage() . "\n";
}
?>
